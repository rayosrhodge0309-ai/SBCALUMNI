<?php

namespace App\Services;

use App\Models\Alumni;
use RuntimeException;
use Throwable;
use XMLWriter;
use ZipArchive;

class AlumniExportService
{
    private const HEADERS = [
        'Student ID',
        'First Name',
        'Last Name',
        'Full Name',
        'Birthday',
        'Education Level',
        'Program / Course',
        'Year Graduated',
        'Email',
        'Contact Number',
        'Address',
        'Portal Account Status',
        'Portal User Email',
    ];

    private const COLUMN_WIDTHS = [16, 20, 20, 30, 14, 22, 38, 16, 32, 18, 45, 22, 32];

    /**
     * Create an Excel workbook and return its temporary path.
     *
     * @param  iterable<int, Alumni>  $alumni
     */
    public function create(iterable $alumni): string
    {
        $workbookPath = null;
        $worksheetPath = null;
        $completed = false;

        try {
            $workbookPath = $this->temporaryFile('alumni-export-');
            $worksheetPath = $this->temporaryFile('alumni-sheet-');
            $this->writeWorksheet($worksheetPath, $alumni);

            $archive = new ZipArchive;
            $opened = $archive->open($workbookPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($opened !== true) {
                throw new RuntimeException('Unable to create the alumni Excel workbook.');
            }

            try {
                foreach ($this->workbookParts() as $path => $contents) {
                    if (! $archive->addFromString($path, $contents)) {
                        throw new RuntimeException("Unable to add {$path} to the alumni Excel workbook.");
                    }
                }

                if (! $archive->addFile($worksheetPath, 'xl/worksheets/sheet1.xml')) {
                    throw new RuntimeException('Unable to add the alumni records to the Excel workbook.');
                }
            } finally {
                $closed = $archive->close();
            }

            if (! $closed || ! is_file($workbookPath) || filesize($workbookPath) === 0) {
                throw new RuntimeException('The alumni Excel workbook could not be finalized.');
            }

            $completed = true;

            return $workbookPath;
        } catch (Throwable $exception) {
            throw new RuntimeException('The alumni Excel export could not be created.', 0, $exception);
        } finally {
            if ($worksheetPath !== null && is_file($worksheetPath)) {
                @unlink($worksheetPath);
            }

            if (! $completed && $workbookPath !== null && is_file($workbookPath)) {
                @unlink($workbookPath);
            }
        }
    }

    /**
     * @param  iterable<int, Alumni>  $alumni
     */
    private function writeWorksheet(string $path, iterable $alumni): void
    {
        $writer = new XMLWriter;

        if (! $writer->openUri($path)) {
            throw new RuntimeException('Unable to prepare the alumni Excel worksheet.');
        }

        $writer->startDocument('1.0', 'UTF-8', 'yes');
        $writer->startElement('worksheet');
        $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $writer->startElement('sheetViews');
        $writer->startElement('sheetView');
        $writer->writeAttribute('workbookViewId', '0');
        $writer->startElement('pane');
        $writer->writeAttribute('ySplit', '1');
        $writer->writeAttribute('topLeftCell', 'A2');
        $writer->writeAttribute('activePane', 'bottomLeft');
        $writer->writeAttribute('state', 'frozen');
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();

        $writer->startElement('sheetFormatPr');
        $writer->writeAttribute('defaultRowHeight', '15');
        $writer->endElement();

        $writer->startElement('cols');
        foreach (self::COLUMN_WIDTHS as $index => $width) {
            $writer->startElement('col');
            $writer->writeAttribute('min', (string) ($index + 1));
            $writer->writeAttribute('max', (string) ($index + 1));
            $writer->writeAttribute('width', (string) $width);
            $writer->writeAttribute('customWidth', '1');
            $writer->endElement();
        }
        $writer->endElement();

        $writer->startElement('sheetData');
        $this->writeRow($writer, 1, self::HEADERS, 1);

        $rowNumber = 1;
        foreach ($alumni as $alumnus) {
            $rowNumber++;
            $this->writeRow($writer, $rowNumber, $this->recordValues($alumnus));
        }

        $writer->endElement();

        $writer->startElement('autoFilter');
        $writer->writeAttribute('ref', 'A1:M'.max(1, $rowNumber));
        $writer->endElement();

        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    /**
     * @param  array<int, string>  $values
     */
    private function writeRow(XMLWriter $writer, int $rowNumber, array $values, int $style = 0): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $rowNumber);

        if ($style === 1) {
            $writer->writeAttribute('ht', '22');
            $writer->writeAttribute('customHeight', '1');
        }

        foreach ($values as $columnIndex => $value) {
            $writer->startElement('c');
            $writer->writeAttribute('r', $this->cellReference($columnIndex, $rowNumber));
            $writer->writeAttribute('t', 'inlineStr');

            if ($style !== 0) {
                $writer->writeAttribute('s', (string) $style);
            }

            $writer->startElement('is');
            $writer->startElement('t');
            $writer->writeAttributeNs('xml', 'space', 'http://www.w3.org/XML/1998/namespace', 'preserve');
            $writer->text($this->sanitize($value));
            $writer->endElement();
            $writer->endElement();
            $writer->endElement();
        }

        $writer->endElement();
    }

    /**
     * @return array<int, string>
     */
    private function recordValues(Alumni $alumnus): array
    {
        $studentId = $alumnus->student_id_display === '-' ? '' : $alumnus->student_id_display;
        $accountStatus = $alumnus->user
            ? ucwords(str_replace('_', ' ', (string) $alumnus->user->account_status))
            : 'Not claimed';

        return [
            $studentId,
            (string) $alumnus->first_name,
            (string) $alumnus->last_name,
            $alumnus->full_name,
            $alumnus->birthday?->format('Y-m-d') ?? '',
            (string) $alumnus->education_level,
            (string) $alumnus->course,
            (int) $alumnus->year_graduated > 0 ? (string) $alumnus->year_graduated : '',
            (string) ($alumnus->email ?? ''),
            (string) ($alumnus->contact_number ?? ''),
            (string) ($alumnus->address ?? ''),
            $accountStatus,
            (string) ($alumnus->user?->email ?? ''),
        ];
    }

    private function cellReference(int $columnIndex, int $rowNumber): string
    {
        $letters = '';
        $index = $columnIndex + 1;

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters.$rowNumber;
    }

    private function sanitize(string $value): string
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
    }

    private function temporaryFile(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        if ($path === false) {
            throw new RuntimeException('Unable to allocate a temporary file for the alumni Excel export.');
        }

        return $path;
    }

    /**
     * @return array<string, string>
     */
    private function workbookParts(): array
    {
        $createdAt = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
                .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
                .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
                .'</Relationships>',
            'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<dc:title>Alumni Records</dc:title><dc:creator>Alumni Link</dc:creator>'
                .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:created>'
                .'</cp:coreProperties>',
            'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
                .'<Application>Alumni Link</Application><AppVersion>1.0</AppVersion>'
                .'</Properties>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Alumni Records" sheetId="1" r:id="rId1"/></sheets>'
                .'</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/><family val="2"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
                .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0B45B8"/><bgColor indexed="64"/></patternFill></fill></fills>'
                .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top/><bottom style="thin"><color rgb="FF083B8F"/></bottom><diagonal/></border></borders>'
                .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>'
                .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
                .'</styleSheet>',
        ];
    }
}
