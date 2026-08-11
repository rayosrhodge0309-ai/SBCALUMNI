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
}
