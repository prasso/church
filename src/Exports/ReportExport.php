<?php

namespace Prasso\Church\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * The report data.
     *
     * @var array
     */
    protected $data;

    /**
     * The report columns.
     *
     * @var array
     */
    protected $columns;

    /**
     * Create a new export instance.
     *
     * @param  array  $data
     * @param  array  $columns
     * @return void
     */
    public function __construct(array $data, array $columns = [])
    {
        $this->data = $data;
        $this->columns = $columns;
    }

    /**
     * Get the array representation of the data.
     *
     * @return array
     */
    public function array(): array
    {
        return $this->data;
    }

    /**
     * Get the headings for the export.
     *
     * @return array
     */
    public function headings(): array
    {
        if (!empty($this->columns)) {
            return array_values($this->columns);
        }

        if (empty($this->data)) {
            return [];
        }

        // Use the keys of the first item as column headers
        $firstRow = $this->data[0] ?? [];
        return array_map(function ($key) {
            return ucwords(str_replace('_', ' ', $key));
        }, array_keys($firstRow));
    }

    /**
     * Map the data for the export.
     *
     * @param  mixed  $row
     * @return array
     */
    public function map($row): array
    {
        if (empty($this->columns)) {
            return array_values((array) $row);
        }

        $mapped = [];
        foreach ($this->columns as $key => $label) {
            $mapped[] = $row[$key] ?? '';
        }

        return $mapped;
    }

    /**
     * Apply styles to the worksheet.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'f5f5f5']
                ]
            ],
            // Set text wrap for all cells
            'A:Z' => [
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => 'top',
                ],
            ],
        ];
    }

    /**
     * Get the column formats for the export.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            // Format dates, numbers, etc.
            // Example: 'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
