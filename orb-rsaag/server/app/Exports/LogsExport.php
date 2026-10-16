<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    Exportable,
    FromQuery,
    RegistersEventListeners,
    ShouldAutoSize,
    WithEvents,
    WithHeadings,
    WithMapping
};
use Maatwebsite\Excel\Events\AfterSheet;

class LogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use Exportable, RegistersEventListeners;

    protected $orderBy;
    protected $isAsc;

    public function __construct($orderBy, $isAsc)
    {
        $this->orderBy = $orderBy;
        $this->isAsc = $isAsc ? 'asc' : 'desc';
    }

    /**
     * Заголовки
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Дата',
            'Время',
            'IP-адрес',
            'Тип запроса',
            'Запрос'
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
            date('d.m.Y', strtotime($row->date_created)),
            date('H:i', strtotime($row->date_created)),
            $row->ip,
            $row->method,
            $row->url
        ];
    }

    public function query()
    {
        $collection = DB::table('logs.log_route');
        return $collection->orderBy($this->orderBy, $this->isAsc);
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
        $sheet->getStyle('A1:E' . $sheet->getHighestRow())->applyFromArray($stylesArray);
    }
}
