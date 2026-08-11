<?php

namespace App\Services;

use App\Models\Alumni;
use App\Support\StudentIdFormatter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AlumniImportService
{
    public function __construct(
        private readonly LinkedAccountSyncService $linkedAccountSyncService
    ) {}

    /**
     * @return array{created:int, updated:int, deleted:int, skipped:int}
     */
    public function import(UploadedFile $file, bool $replaceExisting = true): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'txt', 'tsv', 'xlsx'], true)) {
            throw new InvalidArgumentException('Import currently supports CSV, TSV, TXT, and XLSX files exported from Excel.');
        }

        $rows = $extension === 'xlsx'
            ? $this->parseXlsxFile($file->getRealPath())
            : $this->parseDelimitedFile($file->getRealPath());

        $headerRowIndex = $this->findHeaderRowIndex($rows);

        if ($headerRowIndex === null) {
            throw new InvalidArgumentException('The import file must include a recognizable header row.');
        }

        $headers = array_map([$this, 'normalizeHeader'], $rows[$headerRowIndex]);
        $summary = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ];

        $defaultCourse = $this->inferDefaultCourseFromTitleRows($rows, $headerRowIndex);
        $importedRecordIds = [];

        DB::transaction(function () use ($rows, $headerRowIndex, $headers, $defaultCourse, $replaceExisting, &$summary, &$importedRecordIds): void {
            foreach (array_slice($rows, $headerRowIndex + 1) as $row) {
                if ($this->rowIsBlank($row)) {
                    $summary['skipped']++;
                    continue;
                }

                $transformedRow = $this->transformRow($headers, $row, $defaultCourse);

                if ($transformedRow === null) {
                    $summary['skipped']++;
                    continue;
                }

                $payload = $transformedRow['payload'];

                $alumnus = $this->findAlumnusByStudentId((string) $payload['student_id'])
                    ?? new Alumni([
                        'student_id' => $payload['student_id'],
                    ]);

                $wasExistingRecord = $alumnus->exists;

                $alumnus->fill($this->payloadForPersistence($alumnus, $payload, $transformedRow['present_fields']));
                $alumnus->save();
                $importedRecordIds[] = $alumnus->id;

                if (! $wasExistingRecord) {
                    $summary['created']++;
                } else {
                    $summary['updated']++;
                }

                if ($this->shouldSyncPortalAccount($alumnus)) {
                    $this->linkedAccountSyncService->syncOrCreateUserFromAlumni($alumnus->fresh(), true);
                }
            }

            if ($importedRecordIds === []) {
                throw new InvalidArgumentException('The selected file does not contain any importable student rows.');
            }

            if ($replaceExisting && $importedRecordIds !== []) {
                $recordsToDelete = Alumni::query()
                    ->whereNotIn('id', array_values(array_unique($importedRecordIds)))
                    ->get();

                foreach ($recordsToDelete as $recordToDelete) {
                    $recordToDelete->delete();
                    $summary['deleted']++;
                }
            }
        });

        return $summary;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function parseDelimitedFile(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new InvalidArgumentException('The selected file could not be read.');
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(function ($value) {
                if ($value === null) {
                    return null;
                }

                $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

                return trim((string) $value);
            }, $row);
        }

        fclose($handle);

        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $winner = ',';
        $highestCount = -1;

        foreach ($delimiters as $delimiter) {
            $count = count(str_getcsv($line, $delimiter));

            if ($count > $highestCount) {
                $highestCount = $count;
                $winner = $delimiter;
            }
        }

        return $winner;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function parseXlsxFile(string $path): array
    {
        $extractedDirectory = $this->extractXlsxArchive($path);

        try {
            $sharedStrings = $this->loadSharedStringsFromDirectory($extractedDirectory);
            $numberFormats = $this->loadNumberFormatsFromDirectory($extractedDirectory);
            $sheetPath = $this->resolvePrimaryWorksheetPath($extractedDirectory);

            if (! is_file($sheetPath)) {
                throw new InvalidArgumentException('The selected Excel file does not contain a readable worksheet.');
            }

            $sheetXml = file_get_contents($sheetPath);

            if ($sheetXml === false) {
                throw new InvalidArgumentException('The selected Excel file could not be read.');
            }

            $xml = simplexml_load_string($sheetXml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            if ($xml === false) {
                throw new InvalidArgumentException('The selected Excel file could not be parsed.');
            }

            $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];
            $sheetRows = $xml->xpath('//a:sheetData/a:row') ?: [];

            foreach ($sheetRows as $row) {
                $row->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $cells = $row->xpath('./a:c') ?: [];
                $rowValues = [];
                $highestIndex = -1;

                foreach ($cells as $cell) {
                    $cellReference = (string) $cell['r'];
                    $columnIndex = $this->columnIndexFromCellReference($cellReference);
                    $highestIndex = max($highestIndex, $columnIndex);
                    $rowValues[$columnIndex] = $this->readXlsxCellValue($cell, $sharedStrings, $numberFormats);
                }

                if ($highestIndex < 0) {
                    $rows[] = [];
                    continue;
                }

                $normalizedRow = array_fill(0, $highestIndex + 1, null);

                foreach ($rowValues as $index => $value) {
                    $normalizedRow[$index] = $value;
                }

                $rows[] = $normalizedRow;
            }

            return $rows;
        } finally {
            $this->deleteDirectoryRecursively($extractedDirectory);
        }
    }

    /**
     * @return array<int, string>
     */
    private function loadSharedStringsFromDirectory(string $extractedDirectory): array
    {
        $sharedStringsPath = $this->normalizeExtractedPath($extractedDirectory, 'xl/sharedStrings.xml');

        if (! is_file($sharedStringsPath)) {
            return [];
        }

        $sharedStringsXml = file_get_contents($sharedStringsPath);

        if ($sharedStringsXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sharedStringsXml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sharedStrings = [];
        $items = $xml->xpath('//a:si') ?: [];

        foreach ($items as $item) {
            $item->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $textNodes = $item->xpath('.//a:t') ?: [];
            $sharedStrings[] = trim(implode('', array_map('strval', $textNodes)));
        }

        return $sharedStrings;
    }

    /**
     * @return array<int, string|null>
     */
    private function loadNumberFormatsFromDirectory(string $extractedDirectory): array
    {
        $stylesPath = $this->normalizeExtractedPath($extractedDirectory, 'xl/styles.xml');

        if (! is_file($stylesPath)) {
            return [];
        }

        $stylesXml = file_get_contents($stylesPath);

        if ($stylesXml === false) {
            return [];
        }

        $xml = simplexml_load_string($stylesXml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $customFormats = [];
        foreach ($xml->xpath('//a:numFmts/a:numFmt') ?: [] as $format) {
            $customFormats[(int) $format['numFmtId']] = (string) $format['formatCode'];
        }

        $formatsByStyleIndex = [];
        foreach ($xml->xpath('//a:cellXfs/a:xf') ?: [] as $styleIndex => $style) {
            $formatId = (int) ($style['numFmtId'] ?? 0);
            $formatsByStyleIndex[$styleIndex] = $customFormats[$formatId] ?? $this->builtInXlsxNumberFormat($formatId);
        }

        return $formatsByStyleIndex;
    }

    private function extractXlsxArchive(string $archivePath): string
    {
        $destination = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'alumni-xlsx-' . bin2hex(random_bytes(8));

        if (! mkdir($destination, 0777, true) && ! is_dir($destination)) {
            throw new InvalidArgumentException('The selected Excel file could not be prepared for import.');
        }

        if (class_exists(\ZipArchive::class)) {
            $archive = new \ZipArchive();
            $opened = $archive->open($archivePath);

            if ($opened === true) {
                $archive->extractTo($destination);
                $archive->close();

                return $destination;
            }
        }

        if (class_exists(\PharData::class)) {
            try {
                $archive = new \PharData($archivePath);
                $archive->extractTo($destination, null, true);

                return $destination;
            } catch (\Throwable) {
                $this->deleteDirectoryRecursively($destination);
            }
        }

        if (function_exists('proc_open') && stripos(PHP_OS_FAMILY, 'Windows') === 0) {
            $command = sprintf(
                'Expand-Archive -LiteralPath %s -DestinationPath %s -Force',
                $this->quoteForPowerShell($archivePath),
                $this->quoteForPowerShell($destination)
            );

            $process = proc_open(
                ['powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', $command],
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
                null,
                null,
                ['bypass_shell' => true]
            );

            if (is_resource($process)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);

                if ($exitCode === 0 && is_file($this->normalizeExtractedPath($destination, 'xl/workbook.xml'))) {
                    return $destination;
                }
            }
        }

        $this->deleteDirectoryRecursively($destination);

        throw new InvalidArgumentException('The selected Excel file could not be extracted.');
    }

    /**
     * @param  \SimpleXMLElement  $cell
     * @param  array<int, string>  $sharedStrings
     * @param  array<int, string|null>  $numberFormats
     */
    private function readXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings, array $numberFormats): ?string
    {
        $cellType = (string) $cell['t'];

        if ($cellType === 'inlineStr') {
            $inlineText = $cell->is?->t;

            return $inlineText !== null ? trim((string) $inlineText) : '';
        }

        if ($cellType === 's') {
            $sharedStringIndex = (int) ($cell->v ?? 0);

            return $sharedStrings[$sharedStringIndex] ?? '';
        }

        if ($cellType === 'b') {
            return ((string) ($cell->v ?? '0')) === '1' ? '1' : '0';
        }

        if (! isset($cell->v)) {
            return '';
        }

        $rawValue = trim((string) $cell->v);
        $styleIndex = isset($cell['s']) ? (int) $cell['s'] : null;
        $numberFormat = $styleIndex !== null ? ($numberFormats[$styleIndex] ?? null) : null;

        return $this->formatXlsxNumericValue($rawValue, $numberFormat);
    }

    private function builtInXlsxNumberFormat(int $formatId): ?string
    {
        return match ($formatId) {
            1 => '0',
            2 => '0.00',
            3 => '#,##0',
            4 => '#,##0.00',
            9 => '0%',
            10 => '0.00%',
            11 => '0.00E+00',
            12 => '# ?/?',
            13 => '# ??/??',
            14 => 'm/d/yy',
            15 => 'd-mmm-yy',
            16 => 'd-mmm',
            17 => 'mmm-yy',
            18 => 'h:mm AM/PM',
            19 => 'h:mm:ss AM/PM',
            20 => 'h:mm',
            21 => 'h:mm:ss',
            22 => 'm/d/yy h:mm',
            37 => '#,##0 ;(#,##0)',
            38 => '#,##0 ;[Red](#,##0)',
            39 => '#,##0.00;(#,##0.00)',
            40 => '#,##0.00;[Red](#,##0.00)',
            45 => 'mm:ss',
            46 => '[h]:mm:ss',
            47 => 'mmss.0',
            48 => '##0.0E+0',
            49 => '@',
            default => null,
        };
    }

    private function formatXlsxNumericValue(string $rawValue, ?string $numberFormat): string
    {
        if ($numberFormat === null || trim($numberFormat) === '' || strcasecmp(trim($numberFormat), 'General') === 0) {
            return $rawValue;
        }

        if (! preg_match('/^-?\d+(?:\.\d+)?(?:E[+-]?\d+)?$/i', $rawValue)) {
            return $rawValue;
        }

        $format = $this->parseXlsxNumberFormat($numberFormat);

        if ($format === null || $format['has_date_tokens'] || ! $format['has_placeholders']) {
            return $rawValue;
        }

        if ($format['has_percent']) {
            return $rawValue;
        }

        $numericValue = (float) $rawValue;
        $decimalPlaces = $format['decimal_places'];

        if ($decimalPlaces > 0) {
            return number_format($numericValue, $decimalPlaces, '.', '');
        }

        $digits = $this->xlsxIntegerDigits($rawValue);
        $formatted = '';
        $digitIndex = strlen($digits) - 1;
        $firstPlaceholder = null;
        $tokens = $format['tokens'];

        for ($index = count($tokens) - 1; $index >= 0; $index--) {
            $token = $tokens[$index];
            $character = $token['value'];

            if ($token['type'] === 'placeholder') {
                $firstPlaceholder = $index;

                if ($digitIndex >= 0) {
                    $formatted = $digits[$digitIndex].$formatted;
                    $digitIndex--;
                    continue;
                }

                if ($character === '0') {
                    $formatted = '0'.$formatted;
                } elseif ($character === '?') {
                    $formatted = ' '.$formatted;
                }

                continue;
            }

            $formatted = $character.$formatted;
        }

        if ($digitIndex >= 0 && $firstPlaceholder !== null) {
            $formatted = substr($digits, 0, $digitIndex + 1).$formatted;
        }

        return trim($formatted);
    }

    /**
     * @return array{tokens: array<int, array{type:string,value:string}>, has_placeholders: bool, has_date_tokens: bool, has_percent: bool, decimal_places: int}|null
     */
    private function parseXlsxNumberFormat(string $format): ?array
    {
        $format = explode(';', $format, 2)[0];
        $tokens = [];
        $hasPlaceholders = false;
        $hasDateTokens = false;
        $hasPercent = false;
        $decimalPlaces = 0;
        $isDecimalSection = false;

        for ($index = 0; $index < strlen($format); $index++) {
            $character = $format[$index];

            if ($character === '[') {
                $end = strpos($format, ']', $index);

                if ($end === false) {
                    return null;
                }

                $index = $end;
                continue;
            }

            if ($character === '"') {
                $end = strpos($format, '"', $index + 1);

                if ($end === false) {
                    return null;
                }

                foreach (str_split(substr($format, $index + 1, $end - $index - 1)) as $literal) {
                    $tokens[] = ['type' => 'literal', 'value' => $literal];
                }

                $index = $end;
                continue;
            }

            if ($character === '\\') {
                if (isset($format[$index + 1])) {
                    $tokens[] = ['type' => 'literal', 'value' => $format[$index + 1]];
                    $index++;
                }

                continue;
            }

            if ($character === '_' || $character === '*') {
                $index++;
                continue;
            }

            if ($character === ',') {
                continue;
            }

            if ($character === '.') {
                $isDecimalSection = true;
                $tokens[] = ['type' => 'literal', 'value' => $character];
                continue;
            }

            if ($character === '%') {
                $hasPercent = true;
                continue;
            }

            if (in_array($character, ['0', '#', '?'], true)) {
                $hasPlaceholders = true;

                if ($isDecimalSection) {
                    $decimalPlaces++;
                }

                $tokens[] = ['type' => 'placeholder', 'value' => $character];
                continue;
            }

            if (preg_match('/[ymdhsa]/i', $character)) {
                $hasDateTokens = true;
            }

            $tokens[] = ['type' => 'literal', 'value' => $character];
        }

        return [
            'tokens' => $tokens,
            'has_placeholders' => $hasPlaceholders,
            'has_date_tokens' => $hasDateTokens,
            'has_percent' => $hasPercent,
            'decimal_places' => $decimalPlaces,
        ];
    }

    private function xlsxIntegerDigits(string $rawValue): string
    {
        if (stripos($rawValue, 'E') !== false) {
            return number_format((float) $rawValue, 0, '', '');
        }

        return ltrim(explode('.', $rawValue, 2)[0], '+');
    }

    private function columnIndexFromCellReference(string $cellReference): int
    {
        if (! preg_match('/^([A-Z]+)\d+$/i', $cellReference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        for ($position = 0; $position < strlen($letters); $position++) {
            $index = ($index * 26) + (ord($letters[$position]) - 64);
        }

        return $index - 1;
    }

    private function normalizeHeader(?string $value): string
    {
        $normalized = Str::of((string) $value)
            ->lower()
            ->replace(['#', '-', '/', '\\'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->__toString();

        return match ($normalized) {
            'id', 'id no', 'id no.', 'student id', 'student id number', 'student id no', 'student id no.', 'student number', 'student no', 'student no.', 'id number', 'lrn' => 'student_id',
            'first name', 'firstname', 'first', 'fname', 'given name' => 'first_name',
            'middle name', 'middlename', 'middle', 'mi' => 'middle_name',
            'last name', 'lastname', 'last', 'lname', 'surname', 'family name' => 'last_name',
            'full name', 'name', 'student', 'students', 'names', 'student name', 'students name', 'student names', 'alumni name', 'complete name', 'name of student', 'name of the student', 'student full name' => 'full_name',
            'birthday', 'birth day', 'birth date', 'birthdate', 'date of birth', 'dob' => 'birthday',
            'education level', 'level', 'school level', 'department level', 'classification' => 'education_level',
            'course', 'program', 'degree program', 'course program', 'program or course', 'program course', 'program grade', 'program grade course', 'program and grade', 'program and course', 'track', 'strand', 'grade', 'grade level', 'section' => 'course',
            'year graduated', 'year of graduation', 'graduated year', 'graduation year', 'batch year', 'batch', 'school year', 'year' => 'year_graduated',
            'email', 'email address', 'email portal account', 'portal account', 'portal email' => 'email',
            'contact number', 'contact no', 'contact no.', 'mobile number', 'phone number', 'contact' => 'contact_number',
            'address', 'home address', 'residential address', 'present address', 'current address', 'mailing address', 'student address' => 'address',
            default => Str::snake($normalized),
        };
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array{payload: array<string, mixed>, present_fields: array<string, bool>}|null
     */
    private function transformRow(array $headers, array $row, ?string $defaultCourse = null): ?array
    {
        $data = [];
        $presentFields = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $data[$header] = $row[$index] ?? null;
            $presentFields[$header] = true;
        }

        $studentId = $this->nullableTrim($data['student_id'] ?? null);
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $firstName = $this->nullableTrim($data['first_name'] ?? null);
        $middleName = $this->nullableTrim($data['middle_name'] ?? null);
        $lastName = $this->nullableTrim($data['last_name'] ?? null);

        if ($studentId === null && $fullName === '' && $firstName === null && $lastName === null && ! $this->hasAnyRelevantValue($data)) {
            return null;
        }

        if ($fullName !== '' && ($firstName === null || $lastName === null)) {
            [$firstName, $lastName] = $this->splitFullName($fullName);
            $presentFields['first_name'] = true;
            $presentFields['last_name'] = true;
        }

        if ($firstName === null || $lastName === null) {
            $inferredFullName = $this->inferFullNameFromRow($headers, $row);

            if ($inferredFullName !== null) {
                [$inferredFirstName, $inferredLastName] = $this->splitFullName($inferredFullName);

                $firstName ??= $inferredFirstName;
                $lastName ??= $inferredLastName;
                $presentFields['first_name'] = true;
                $presentFields['last_name'] = true;
            }
        }

        if ($fullName === '' && $firstName !== null && $middleName !== null) {
            $firstName = trim($firstName.' '.$middleName);
            $presentFields['first_name'] = true;
        }

        $studentId ??= $this->generateTemporaryStudentId();
        $firstName ??= 'Imported';
        $lastName ??= 'Record';

        $course = $this->nullableTrim($data['course'] ?? null) ?? $this->nullableTrim($defaultCourse);
        $year = trim((string) ($data['year_graduated'] ?? ''));

        $yearValue = $this->extractGraduationYear($year);

        $yearValue = $yearValue !== null && (
            ($yearValue >= 1 && $yearValue <= 12)
            || ($yearValue >= 1900 && $yearValue <= now()->year + 1)
        )
            ? $yearValue
            : 0;

        $email = trim((string) ($data['email'] ?? ''));
        $courseWasProvided = $this->nullableTrim($data['course'] ?? null) !== null || $this->nullableTrim($defaultCourse) !== null;
        $yearWasProvided = $this->extractGraduationYear($year) !== null;
        $educationLevelWasProvided = $this->nullableTrim($data['education_level'] ?? null) !== null;

        if ($courseWasProvided) {
            $presentFields['course'] = true;
        }

        if ($yearWasProvided) {
            $presentFields['year_graduated'] = true;
        }

        if ($educationLevelWasProvided || $courseWasProvided || $yearWasProvided) {
            $presentFields['education_level'] = true;
        }

        $educationLevel = $this->inferEducationLevel(
            $this->nullableTrim($data['education_level'] ?? null),
            $course,
            $yearValue
        );

        if (array_key_exists('birthday', $data)) {
            $presentFields['birthday'] = true;
        }

        if ($this->nullableTrim($data['email'] ?? null) !== null || array_key_exists('email', $data)) {
            $presentFields['email'] = true;
        }

        if ($this->nullableTrim($data['contact_number'] ?? null) !== null || array_key_exists('contact_number', $data)) {
            $presentFields['contact_number'] = true;
        }

        if ($this->nullableTrim($data['address'] ?? null) !== null || array_key_exists('address', $data)) {
            $presentFields['address'] = true;
        }

        return [
            'payload' => [
                'student_id' => $studentId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birthday' => $this->parseBirthday($data['birthday'] ?? null),
                'education_level' => $educationLevel,
                'course' => $course !== null ? $course : 'Pending',
                'year_graduated' => $yearValue,
                'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? Str::lower($email) : null,
                'contact_number' => $this->nullableTrim($data['contact_number'] ?? null),
                'address' => $this->nullableTrim($data['address'] ?? null),
            ],
            'present_fields' => $presentFields,
        ];
    }

    /**
     * @param  array<int, array<int, string|null>>  $rows
     */
    private function inferDefaultCourseFromTitleRows(array $rows, int $headerRowIndex): ?string
    {
        for ($rowIndex = 0; $rowIndex < $headerRowIndex; $rowIndex++) {
            $row = $rows[$rowIndex] ?? [];
            $values = array_values(array_filter(array_map([$this, 'nullableTrim'], $row)));

            if (count($values) !== 1) {
                continue;
            }

            $candidate = $values[0];

            if ($this->looksLikeCourseTitle($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function looksLikeCourseTitle(string $value): bool
    {
        $normalized = Str::lower(trim($value));

        if ($normalized === '' || filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (preg_match('/^\d+([-\s]\d+)*$/', $normalized)) {
            return false;
        }

        if (! preg_match('/[A-Za-z]/', $normalized)) {
            return false;
        }

        $words = preg_split('/\s+/', $normalized) ?: [];

        if (count($words) === 1) {
            return preg_match('/^(stem|humss|abm|gas|tvl|bs[a-z]+|b[a-z]{2,})$/i', $normalized) === 1;
        }

        return Str::contains($normalized, [
            'bachelor',
            'associate',
            'degree',
            'program',
            'course',
            'education',
            'elementary',
            'secondary',
            'junior high',
            'senior high',
            'strand',
            'track',
            'grade',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, bool>  $presentFields
     * @return array<string, mixed>
     */
    private function payloadForPersistence(Alumni $alumnus, array $payload, array $presentFields): array
    {
        if (! $alumnus->exists) {
            return $payload;
        }

        foreach (array_keys($payload) as $field) {
            if (! array_key_exists($field, $presentFields)) {
                unset($payload[$field]);
            }
        }

        return $payload;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitFullName(string $fullName): array
    {
        if (str_contains($fullName, ',')) {
            [$lastName, $firstName] = array_map('trim', explode(',', $fullName, 2));

            return [$firstName, $lastName];
        }

        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return [$fullName, $fullName];
        }

        $lastName = array_pop($parts);

        return [implode(' ', $parts), (string) $lastName];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     */
    private function inferFullNameFromRow(array $headers, array $row): ?string
    {
        foreach ($headers as $index => $header) {
            if (! Str::contains($header, 'name')) {
                continue;
            }

            $candidate = $this->nullableTrim($row[$index] ?? null);

            if ($candidate !== null && $this->looksLikePersonName($candidate)) {
                return $candidate;
            }
        }

        foreach ($row as $value) {
            $candidate = $this->nullableTrim($value);

            if ($candidate !== null && $this->looksLikePersonName($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function looksLikePersonName(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (preg_match('/^\d+([-\s]\d+)*$/', $value)) {
            return false;
        }

        if (! preg_match('/[A-Za-z]/', $value)) {
            return false;
        }

        $words = preg_split('/\s+/', trim($value)) ?: [];

        if (count($words) >= 2) {
            return true;
        }

        return str_contains($value, ',') && str_contains($value, ' ');
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function parseBirthday(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        if (preg_match('/^\d{5,}(\.\d+)?$/', $trimmed)) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $trimmed))->format('Y-m-d');
        }

        $formats = [
            'Y-m-d',
            'Y-m-d H:i:s',
            'Y/m/d',
            'Y.m.d',
            'm/d/Y',
            'd/m/Y',
            'm-d-Y',
            'd-m-Y',
            'F j, Y',
            'j F Y',
            'F j Y',
            'M j, Y',
            'j M Y',
            'j F, Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $trimmed);
            } catch (\Throwable) {
                continue;
            }

            if ($date !== false) {
                return $date->startOfDay()->format('Y-m-d');
            }
        }

        return null;
    }

    private function extractGraduationYear(string $value): ?int
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            return (int) $trimmed;
        }

        if (preg_match('/\b(19\d{2}|20\d{2})\b/', $trimmed, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/\b(?:grade|year)\s*(1[0-2]|[1-9])\b/i', $trimmed, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function inferEducationLevel(?string $educationLevel, ?string $course, int $yearValue): string
    {
        $normalizedEducationLevel = trim((string) $educationLevel);

        if ($normalizedEducationLevel !== '') {
            return $normalizedEducationLevel;
        }

        $normalizedCourse = Str::lower(trim((string) $course));

        if ($normalizedCourse === '' && $yearValue === 0) {
            return 'College';
        }

        if (
            ($yearValue > 0 && $yearValue <= 6)
            || Str::contains($normalizedCourse, ['elementary', 'grade 1', 'grade 2', 'grade 3', 'grade 4', 'grade 5', 'grade 6'])
        ) {
            return 'Elementary';
        }

        if (
            ($yearValue >= 7 && $yearValue <= 10)
            || Str::contains($normalizedCourse, ['junior high', 'grade 7', 'grade 8', 'grade 9', 'grade 10'])
        ) {
            return 'Junior High School';
        }

        if (
            ($yearValue >= 11 && $yearValue <= 12)
            || Str::contains($normalizedCourse, ['senior high', 'grade 11', 'grade 12', 'stem', 'humss', 'abm', 'gas', 'tvl'])
        ) {
            return 'Senior High School';
        }

        return 'College';
    }

    /**
     * @param  array<int, array<int, string|null>>  $rows
     */
    private function findHeaderRowIndex(array $rows): ?int
    {
        $maxScanRows = min(count($rows), 10);
        $knownHeaders = [
            'student_id',
            'first_name',
            'middle_name',
            'last_name',
            'full_name',
            'birthday',
            'education_level',
            'course',
            'year_graduated',
            'email',
            'contact_number',
            'address',
        ];

        for ($rowIndex = 0; $rowIndex < $maxScanRows; $rowIndex++) {
            $mappedHeaders = array_map([$this, 'normalizeHeader'], $rows[$rowIndex]);
            $recognizedHeaders = array_values(array_intersect($mappedHeaders, $knownHeaders));

            if (count($recognizedHeaders) < 1) {
                continue;
            }

            return $rowIndex;
        }

        return null;
    }

    private function resolvePrimaryWorksheetPath(string $extractedDirectory): string
    {
        $workbookPath = $this->normalizeExtractedPath($extractedDirectory, 'xl/workbook.xml');

        if (! is_file($workbookPath)) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $workbookXml = file_get_contents($workbookPath);

        if ($workbookXml === false) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $xml = simplexml_load_string($workbookXml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $workbookView = $xml->xpath('//a:bookViews/a:workbookView[1]')[0] ?? null;
        $activeTab = $workbookView !== null ? (int) ($workbookView['activeTab'] ?? 0) : 0;
        $sheetPosition = max(1, $activeTab + 1);
        $sheet = $xml->xpath(sprintf('//a:sheets/a:sheet[%d]', $sheetPosition))[0]
            ?? $xml->xpath('//a:sheets/a:sheet[1]')[0]
            ?? null;

        if (! $sheet) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $relationshipId = (string) ($sheet->attributes('r', true)['id'] ?? '');

        if ($relationshipId === '') {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $relationshipsPath = $this->normalizeExtractedPath($extractedDirectory, 'xl/_rels/workbook.xml.rels');

        if (! is_file($relationshipsPath)) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $relationshipsXml = file_get_contents($relationshipsPath);

        if ($relationshipsXml === false) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $relationships = simplexml_load_string($relationshipsXml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($relationships === false) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $relationships->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relationship = $relationships->xpath(sprintf('//a:Relationship[@Id="%s"]', $relationshipId))[0] ?? null;

        if (! $relationship) {
            return $this->normalizeExtractedPath($extractedDirectory, 'xl/worksheets/sheet1.xml');
        }

        $target = str_replace('\\', '/', (string) $relationship['Target']);
        $target = ltrim($target, '/');

        if (! str_starts_with($target, 'xl/')) {
            $target = 'xl/' . $target;
        }

        return $this->normalizeExtractedPath($extractedDirectory, $target);
    }

    private function normalizeExtractedPath(string $baseDirectory, string $relativePath): string
    {
        return rtrim($baseDirectory, '\\/') . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), '\\/');
    }

    private function quoteForPowerShell(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function generateTemporaryStudentId(): string
    {
        return 'TEMP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function findAlumnusByStudentId(string $studentId): ?Alumni
    {
        $variants = StudentIdFormatter::variants($studentId);

        if ($variants === []) {
            return null;
        }

        $alumnus = Alumni::query()
            ->where(function ($query) use ($variants) {
                foreach ($variants as $variant) {
                    $query->orWhere('student_id', $variant);
                }
            })
            ->first();

        return $alumnus instanceof Alumni ? $alumnus : null;
    }

    private function shouldSyncPortalAccount(Alumni $alumnus): bool
    {
        return $alumnus->email !== null
            && ! Str::startsWith((string) $alumnus->student_id, 'TEMP-')
            && trim((string) $alumnus->first_name) !== 'Imported'
            && trim((string) $alumnus->last_name) !== 'Record'
            && trim((string) $alumnus->course) !== 'Pending'
            && (int) $alumnus->year_graduated > 0;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasAnyRelevantValue(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function deleteDirectoryRecursively(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }

}
