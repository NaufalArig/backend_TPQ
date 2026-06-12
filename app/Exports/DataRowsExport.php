<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataRowsExport extends DefaultValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $textColumns = []
    ) {
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $columnIndex = $cell->getColumn();

        if (in_array($columnIndex, $this->textColumns, true) && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return collect($this->textColumns)
            ->mapWithKeys(fn (string $column) => [$column => NumberFormat::FORMAT_TEXT])
            ->all();
    }

    public function styles(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$highestColumn}1");

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '111827'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
            ],
        ];
    }
}
