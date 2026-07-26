<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function download(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('order.unauthorized')], 403);
        }

        $order->load(['items.product', 'shippingAddress', 'payments']);
        $locale = app()->getLocale();

        $data = [
            'order'   => $order,
            'locale'  => $locale,
            'company' => [
                'name'   => config('app.name', 'Online Store'),
                'email'  => config('mail.from.address'),
            ],
        ];

        $pdf = Pdf::loadView('invoices.order', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    public function preview(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('order.unauthorized')], 403);
        }

        $order->load(['items.product', 'shippingAddress', 'payments']);
        $locale = app()->getLocale();

        return view('invoices.order', [
            'order'   => $order,
            'locale'  => $locale,
            'company' => [
                'name'   => config('app.name', 'Online Store'),
                'email'  => config('mail.from.address'),
            ],
        ]);
    }
}
