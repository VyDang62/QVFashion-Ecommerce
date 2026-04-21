<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SingleOrderExport implements FromView, ShouldAutoSize
{
    protected $order;

    public function __construct($order) {
        $this->order = $order;
    }

    public function view(): View {
        return view('admin.exports.order_excel', [
            'order' => $this->order
        ]);
    }
}