<?php

namespace App\Application\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxReportService
{
    private const PRIMARY = 'FF2196F3';
    private const SECONDARY = 'FF673AB7';
    private const SECONDARY_LIGHT = 'FFEDE7F6';
    private const BACKGROUND = 'FFF8FAFC';
    private const BORDER = 'FFE3E8EF';
    private const TEXT = 'FF364152';
    private const WHITE = 'FFFFFFFF';

    private const TITLES = [
        'inventory' => 'Equipment Inventory Report',
        'available' => 'Available Equipment Report',
        'borrowed' => 'Borrowed Equipment Report',
        'overdue' => 'Overdue Equipment Report',
        'damaged' => 'Damaged Equipment Report',
        'maintenance' => 'Maintenance Report',
        'history' => 'Borrowing History Report',
    ];

    public function create(string $type, array $filters, array $rows): array
    {
        if (! class_exists(Spreadsheet::class)) {
            throw new \RuntimeException('Excel export dependency is missing. Run composer install after updating the project dependencies.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('BantayGamit')
            ->setTitle(self::TITLES[$type] ?? 'BantayGamit Report')
            ->setSubject('Barangay equipment monitoring report');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->getColor()->setARGB(self::TEXT);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');
        $sheet->setShowGridlines(false);

        $columns = $rows ? array_keys($rows[0]) : $this->columnsFor($type);
        $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($columns)));
        $headerRow = 6;
        $firstDataRow = $headerRow + 1;
        $lastDataRow = max($firstDataRow, $headerRow + count($rows));

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', self::TITLES[$type] ?? 'BantayGamit Report');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::SECONDARY]],
            'font' => ['bold' => true, 'size' => 18, 'color' => ['argb' => self::WHITE]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', 'BantayGamit — Web-Based Barangay Equipment Monitoring System');
        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::SECONDARY_LIGHT]],
            'font' => ['bold' => true, 'color' => ['argb' => self::TEXT]],
        ]);

        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->setCellValue('A3', 'Generated: ' . date('F j, Y g:i A'));
        $sheet->getStyle("A3:{$lastColumn}3")->getFont()->setItalic(true)->getColor()->setARGB('FF697586');

        $sheet->mergeCells("A4:{$lastColumn}4");
        $sheet->setCellValue('A4', $this->filterSummary($filters));
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BACKGROUND]],
            'font' => ['color' => ['argb' => 'FF697586']],
            'alignment' => ['wrapText' => true],
        ]);

        $headers = array_map([$this, 'label'], $columns);
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::PRIMARY]],
            'font' => ['bold' => true, 'color' => ['argb' => self::WHITE]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDER]]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        if ($rows) {
            $matrix = [];
            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $column) {
                    $line[] = $this->normalizeValue($column, $row[$column] ?? null);
                }
                $matrix[] = $line;
            }
            $sheet->fromArray($matrix, null, "A{$firstDataRow}");

            $sheet->getStyle("A{$firstDataRow}:{$lastColumn}{$lastDataRow}")->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => self::BORDER]]],
            ]);

            for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                if (($row - $firstDataRow) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::BACKGROUND);
                }
            }

            $this->styleStatusCells($sheet, $columns, $firstDataRow, $lastDataRow);
            $this->applyNumberFormats($sheet, $columns, $firstDataRow, $lastDataRow);
        } else {
            $sheet->mergeCells("A{$firstDataRow}:{$lastColumn}{$firstDataRow}");
            $sheet->setCellValue("A{$firstDataRow}", 'No report data matches the selected filters.');
            $sheet->getStyle("A{$firstDataRow}:{$lastColumn}{$firstDataRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BACKGROUND]],
                'font' => ['italic' => true, 'color' => ['argb' => 'FF697586']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        foreach ($columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            if (in_array($column, ['description', 'purpose', 'message', 'damage_notes', 'rejection_reason'], true)) {
                $sheet->getColumnDimension($letter)->setAutoSize(false)->setWidth(32);
            }
        }

        $sheet->freezePane("A{$firstDataRow}");
        if ($rows) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastDataRow}");
        }
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.4)->setBottom(0.5)->setLeft(0.4);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'bantaygamit-xlsx-');
        if ($tempFile === false) {
            throw new \RuntimeException('Could not create the Excel export file.');
        }

        $contents = '';
        try {
            $writer->save($tempFile);
            $contents = file_get_contents($tempFile);
            if ($contents === false) {
                throw new \RuntimeException('Could not read the generated Excel report.');
            }
        } finally {
            @unlink($tempFile);
            $spreadsheet->disconnectWorksheets();
        }

        return [
            'filename' => 'bantaygamit-' . $type . '-' . date('Ymd-His') . '.xlsx',
            'content' => $contents,
        ];
    }

    private function columnsFor(string $type): array
    {
        return match ($type) {
            'available' => ['asset_code', 'name', 'category', 'location', 'total_quantity', 'condition', 'status', 'available_quantity'],
            'inventory', 'damaged' => ['asset_code', 'name', 'category', 'location', 'total_quantity', 'condition', 'status'],
            'maintenance' => ['id', 'asset_code', 'equipment_name', 'maintenance_type', 'quantity', 'status', 'start_date', 'completion_date', 'cost', 'reported_by_name'],
            default => ['request_number', 'borrower', 'purpose', 'requested_date', 'expected_return_date', 'status', 'created_at'],
        };
    }

    private function label(string $column): string
    {
        $special = [
            'asset_code' => 'Asset Code',
            'equipment_name' => 'Equipment',
            'reported_by_name' => 'Reported By',
            'request_number' => 'Request Number',
            'created_at' => 'Created At',
        ];

        return $special[$column] ?? ucwords(str_replace('_', ' ', $column));
    }

    private function normalizeValue(string $column, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (in_array($column, ['id', 'total_quantity', 'available_quantity', 'quantity'], true)) {
            return (int) $value;
        }
        if ($column === 'cost') {
            return (float) $value;
        }

        return (string) $value;
    }

    private function applyNumberFormats($sheet, array $columns, int $firstRow, int $lastRow): void
    {
        foreach ($columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            if ($column === 'cost') {
                $sheet->getStyle("{$letter}{$firstRow}:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode('₱#,##0.00');
            } elseif (in_array($column, ['id', 'total_quantity', 'available_quantity', 'quantity'], true)) {
                $sheet->getStyle("{$letter}{$firstRow}:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode('0');
            }
        }
    }

    private function styleStatusCells($sheet, array $columns, int $firstRow, int $lastRow): void
    {
        $palette = [
            'available' => ['FFE8F5E9', 'FF1B5E20'],
            'excellent' => ['FFE8F5E9', 'FF1B5E20'],
            'good' => ['FFE3F2FD', 'FF1565C0'],
            'fair' => ['FFFFF8E1', 'FF8D6E00'],
            'approved' => ['FFE8F5E9', 'FF1B5E20'],
            'returned' => ['FFE8F5E9', 'FF1B5E20'],
            'completed' => ['FFE8F5E9', 'FF1B5E20'],
            'pending' => ['FFFFF8E1', 'FF8D6E00'],
            'scheduled' => ['FFFFF8E1', 'FF8D6E00'],
            'reported' => ['FFFFF8E1', 'FF8D6E00'],
            'released' => ['FFE3F2FD', 'FF1565C0'],
            'in_progress' => ['FFE3F2FD', 'FF1565C0'],
            'overdue' => ['FFFFEBEE', 'FFB71C1C'],
            'rejected' => ['FFFFEBEE', 'FFB71C1C'],
            'damaged' => ['FFFFEBEE', 'FFB71C1C'],
            'cancelled' => ['FFF3E5F5', 'FF6A1B9A'],
            'maintenance' => ['FFF3E5F5', 'FF6A1B9A'],
            'unavailable' => ['FFF1F5F9', 'FF475569'],
            'retired' => ['FFF1F5F9', 'FF475569'],
        ];

        foreach ($columns as $index => $column) {
            if (! in_array($column, ['status', 'condition'], true)) {
                continue;
            }
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            for ($row = $firstRow; $row <= $lastRow; $row++) {
                $value = strtolower((string) $sheet->getCell("{$letter}{$row}")->getValue());
                if (! isset($palette[$value])) {
                    continue;
                }
                [$fill, $font] = $palette[$value];
                $sheet->getStyle("{$letter}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fill]],
                    'font' => ['bold' => true, 'color' => ['argb' => $font]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
    }

    private function filterSummary(array $filters): string
    {
        $parts = [];
        if (! empty($filters['from'])) $parts[] = 'From: ' . $filters['from'];
        if (! empty($filters['to'])) $parts[] = 'To: ' . $filters['to'];
        if (! empty($filters['category_id'])) $parts[] = 'Category ID: ' . (int) $filters['category_id'];
        if (! empty($filters['equipment_id'])) $parts[] = 'Equipment ID: ' . (int) $filters['equipment_id'];
        if (! empty($filters['borrower_id'])) $parts[] = 'Borrower ID: ' . (int) $filters['borrower_id'];
        if (! empty($filters['status'])) $parts[] = 'Status: ' . $this->label((string) $filters['status']);

        return $parts ? 'Filters — ' . implode(' | ', $parts) : 'Filters — All records';
    }
}
