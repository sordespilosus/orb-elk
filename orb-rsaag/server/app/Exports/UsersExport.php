<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    Exportable,
    RegistersEventListeners,
    ShouldAutoSize,
    WithColumnWidths,
    WithEvents,
    WithHeadings,
    WithMapping
};
use Maatwebsite\Excel\Events\AfterSheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithColumnWidths, WithEvents
{
    use Exportable, RegistersEventListeners;

    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Заголовки
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'id',
            'email',
            'fio',
            'gender',
            'age',
            'area_name',
            'admin',
            'blocked',
            'email_verified'
        ];
    }

    /**
     * Маппинг столбцов
     * @param $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->email,
            $row->fio,
            $row->gender == 'male' ? 'М' : ($row->gender == 'female' ? 'Ж' : ''),
            date_diff(date_create($row->birthday), date_create('today'))->y,
            $row->area_name,
            (bool)$row->admin,
            $row->blocked,
            (bool)$row->email_verified
        ];
    }

    public function query()
    {
        return $this->query;
    }

    public function columnWidths(): array
    {
        return [
            'G' => 12,
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $stylesArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '00000']
                ],
                'inside' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '00000']
                ]
            ]
        ];
        $sheet = $event->sheet;
        $sheet->getStyle('A1:I' . $sheet->getHighestRow())->applyFromArray($stylesArray);
    }
}
