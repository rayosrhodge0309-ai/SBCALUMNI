<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Support\StudentIdFormatter;
use Illuminate\Support\Str;

class Alumni extends Model
{
    protected $table = 'alumni';

    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'birthday',
        'education_level',
        'course',
        'year_graduated',
        'email',
        'contact_number',
        'address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'year_graduated' => 'integer',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(RecordRequest::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getStudentIdDisplayAttribute(): string
    {
        return StudentIdFormatter::display((string) $this->student_id);
    }

    public function getYearLabelAttribute(): string
    {
        $year = (int) $this->year_graduated;

        if ($year <= 0) {
            return '';
        }

        return $year <= 12
            ? 'Grade '.$year
            : 'Year '.$year;
    }

    public function getAcademicLabelAttribute(): string
    {
        $segments = array_filter([
            trim((string) $this->course),
            trim((string) $this->education_level),
            $this->year_label,
        ], static fn (string $segment): bool => $segment !== '');

        if (count($segments) === 0) {
            return 'Unspecified Academic Record';
        }

        $label = implode(' - ', $segments);

        if ($this->year_label !== '' && Str::contains(Str::lower((string) $this->course), Str::lower($this->year_label))) {
            $label = trim(implode(' - ', array_filter([
                trim((string) $this->course),
                trim((string) $this->education_level),
            ], static fn (string $segment): bool => $segment !== '')));
        }

        return $label;
    }

    public function getProgramGroupLabelAttribute(): string
    {
        [$program, $yearLevel] = $this->normalizedProgramGroup();

        if ($program === '') {
            return 'Unspecified Course';
        }

        return $yearLevel !== null
            ? "{$program} - {$yearLevel}"
            : $program;
    }

    public function getProgramGroupSortKeyAttribute(): string
    {
        [$program, $yearLevel] = $this->normalizedProgramGroup();

        $program = Str::lower($program !== '' ? $program : 'Unspecified Course');
        $year = $yearLevel !== null ? (string) $yearLevel : '0';

        return $program.'|'.str_pad($year, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function normalizedProgramGroup(): array
    {
        $course = trim((string) $this->course);

        if ($course === '') {
            return ['', null];
        }

        $yearLevel = $this->extractCourseYearLevel($course);
        $courseWithoutYear = $this->removeCourseYearLevel($course);
        $normalized = Str::upper(trim(preg_replace('/[^A-Za-z0-9]+/', ' ', $courseWithoutYear) ?? $courseWithoutYear));

        $programMap = [
            'BSIT' => '/\bB\s*S\s*I\s*T\b|\bBSIT\b|\bINFORMATION\s+TECHNOLOGY\b|\bINFO\s+TECH\b/',
            'EDUC' => '/\bB\s*S\s*E\s*D\b|\bBSED\b|\bB\s*E\s*E\s*D\b|\bBEED\b|\bEDUC(?:ATION)?\b|\bSECONDARY\s+EDUCATION\b|\bELEMENTARY\s+EDUCATION\b/',
            'BA' => '/\bB\s*A\b|\bBA\b|\bBACHELOR\s+OF\s+ARTS\b/',
            'BS PSYCHOLOGY' => '/\bPSYCHOLOGY\b|\bB\s*S\s*PSYCH(?:OLOGY)?\b/',
        ];

        foreach ($programMap as $label => $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return [$label, $yearLevel];
            }
        }

        $acronym = $this->extractProgramAcronym($normalized);

        if ($acronym !== '') {
            return [$acronym, $yearLevel];
        }

        return [Str::title(Str::lower(trim($courseWithoutYear))), $yearLevel];
    }

    private function extractProgramAcronym(string $normalized): string
    {
        if (preg_match('/\b(?:BS|AB|BA|BSE?D|BEED|BSBA|BSA|BSTM|BSHM)[A-Z]*\b/', $normalized, $matches) === 1) {
            return $matches[0];
        }

        return '';
    }

    private function extractCourseYearLevel(string $course): ?int
    {
        $patterns = [
            '/\b(?:YEAR|YR|LEVEL|GRADE)\s*([1-4])(?:ST|ND|RD|TH)?\b/i',
            '/\b(?:BSIT|BSED|BEED|EDUC|BA|BSBA|BSA|BSTM|BSHM|PSYCH(?:OLOGY)?)\s*[- ]+\s*([1-4])(?:ST|ND|RD|TH)?\b/i',
            '/(?:^|[\s\-])([1-4])(?:ST|ND|RD|TH)?(?:\s*YEAR)?\s*$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $course, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function removeCourseYearLevel(string $course): string
    {
        $withoutYearLevel = preg_replace([
            '/\b(?:YEAR|YR|LEVEL|GRADE)\s*[1-4](?:ST|ND|RD|TH)?\b/i',
            '/\b(BSIT|BSED|BEED|EDUC|BA|BSBA|BSA|BSTM|BSHM|PSYCH(?:OLOGY)?)\s*[- ]+\s*[1-4](?:ST|ND|RD|TH)?\b/i',
            '/(?:^|[\s\-])(?:YEAR|YR|LEVEL|GRADE)?\s*[1-4](?:ST|ND|RD|TH)?(?:\s*YEAR)?\s*$/i',
        ], ['', '$1', ''], $course);

        return trim($withoutYearLevel ?? $course, " \t\n\r\0\x0B-");
    }
}
