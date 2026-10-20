<?php
/**
 * Kasora - Pembuatan file XLSX tanpa dependensi paket eksternal.
 */
function buat_file_excel_transaksi(array $transactions, float $totalMasuk, float $totalKeluar, bool $filtered): string
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif.');
    }

    $xmlEscape = static function ($value): string {
        $text = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value);
        return htmlspecialchars($text === null ? '' : $text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };
    $cell = static function (string $reference, $value, bool $numeric = false) use ($xmlEscape): string {
        if ($numeric) {
            return '<c r="' . $reference . '" t="n"><v>' . $xmlEscape($value) . '</v></c>';
        }

        return '<c r="' . $reference . '" t="inlineStr"><is><t xml:space="preserve">'
            . $xmlEscape($value) . '</t></is></c>';
    };
    $sheetRows = [];
    $appendRow = static function (int $rowNumber, array $values) use (&$sheetRows, $cell): void {
        $cells = [];
        foreach ($values as $columnIndex => $value) {
            $column = chr(ord('A') + $columnIndex);
            $numeric = is_array($value);
            $cells[] = $numeric
                ? $cell($column . $rowNumber, $value['value'], true)
                : $cell($column . $rowNumber, $value);
        }
        $sheetRows[] = '<row r="' . $rowNumber . '">' . implode('', $cells) . '</row>';
    };

    $appendRow(1, ['Laporan Keuangan Pribadi - Kasora']);
    $appendRow(2, ['Tanggal ekspor', date('Y-m-d H:i:s')]);
    $appendRow(3, ['Filter', $filtered ? 'Filter riwayat yang dipilih' : 'Semua transaksi pribadi']);
    $appendRow(4, [
        'Total pemasukan',
        ['value' => number_format($totalMasuk, 2, '.', '')],
        'Total pengeluaran',
        ['value' => number_format($totalKeluar, 2, '.', '')],
        'Saldo',
        ['value' => number_format($totalMasuk - $totalKeluar, 2, '.', '')],
    ]);
    $appendRow(6, ['Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Nominal (Rp)']);

    foreach ($transactions as $index => $transaction) {
        $appendRow(7 + $index, [
            $transaction['tanggal'],
            $transaction['jenis'] === 'masuk' ? 'Pemasukan' : 'Pengeluaran',
            $transaction['kategori'],
            $transaction['keterangan'] ?? '',
            ['value' => $transaction['nominal']],
        ]);
    }

    $lastRow = max(6, 6 + count($transactions));
    $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<dimension ref="A1:F' . $lastRow . '"/>'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>'
        . '<cols><col min="1" max="1" width="16" customWidth="1"/>'
        . '<col min="2" max="3" width="18" customWidth="1"/>'
        . '<col min="4" max="4" width="36" customWidth="1"/>'
        . '<col min="5" max="6" width="18" customWidth="1"/>'
        . '</cols><sheetData>' . implode('', $sheetRows) . '</sheetData>'
        . '<autoFilter ref="A6:E' . $lastRow . '"/>'
        . '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
        . '</worksheet>';

    $files = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Transaksi" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>',
        'xl/worksheets/sheet1.xml' => $worksheet,
    ];

    $temporaryFile = tempnam(sys_get_temp_dir(), 'kasora-xlsx-');
    if ($temporaryFile === false) {
        throw new RuntimeException('File Excel tidak dapat disiapkan.');
    }

    $archive = new ZipArchive();
    $opened = $archive->open($temporaryFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($opened !== true) {
        unlink($temporaryFile);
        throw new RuntimeException('File Excel tidak dapat dibuat oleh ZipArchive.');
    }

    foreach ($files as $path => $contents) {
        if (!$archive->addFromString($path, $contents)) {
            $archive->close();
            unlink($temporaryFile);
            throw new RuntimeException('Data laporan gagal ditulis ke file Excel.');
        }
    }

    if (!$archive->close()) {
        unlink($temporaryFile);
        throw new RuntimeException('File Excel gagal diselesaikan.');
    }

    return $temporaryFile;
}
