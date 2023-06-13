<?php
/**
 * User: kpistech2
 * Date: 2020-11-13
 * Time: 22:59
 */

namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerCommissionExport extends BaseExport implements FromView, WithColumnFormatting, WithEvents, WithStyles
{
    protected $_name;
    protected $_header;
    protected $_from;
    protected $_to;
    protected $_body;
    protected $_format;
    protected $_chars;

    /**
     * HeaderExport constructor.
     *
     * @param string $name
     * @param array $header
     * @param null $from
     * @param null $to
     */
    public function __construct(string $name, array $header, $from = null, $to = null)
    {
        $this->_name = $name;
        $this->_header = $header;
        $this->_from = $from ?? date("m/d/Y");
        $this->_to = $to ?? date("m/d/Y");
        $this->_body = [];

        $this->_chars = config("constants.EXCEL.CHAR");
        parent::__construct();
    }

    public function download($fileName)
    {
        return Excel::download($this, $fileName);
    }

    /**
     * @return View
     */
    public function view(): View
    {

        return view('exports.report_v3.table-normal', [
            'reportName' => $this->_name,
            'from'       => $this->_from,
            'to'         => $this->_to,
            'dataHeader' => $this->_header,
            'dataBody'   => $this->_body,
        ]);
    }

    public function setFormatNumber($format)
    {
        $this->_format = $format;
    }

    public function columnFormats(): array
    {
        return $this->_format;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color'       => ['argb' => '#777777'],
                        ],
                    ],
                ];
                if (!empty($this->_body[0])) {
                    $cellRange = 'A3:' . ($this->_chars[count($this->_body[0])] . (count($this->_body) + 3));
                    $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray($styleArray);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Wrap Text
        foreach ($this->_header as $i => $col) {
            if (!empty($col['wrap'])) {
                $cellRange = $this->_chars[$i + 1] . "4:" . $this->_chars[$i + 1] . (count($this->_body) + 3);
                $sheet->getStyle($cellRange)->getAlignment()->setWrapText(true);
            }
        }
    }
}