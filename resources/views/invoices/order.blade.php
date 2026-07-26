<!DOCTYPE html>
<html dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; padding: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #2563eb; }
        .header .meta { text-align: {{ $locale === 'ar' ? 'left' : 'right' }}; }
        .header .meta p { margin: 2px 0; }
        .info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info .box { width: 45%; }
        .info .box h3 { margin: 0 0 8px; font-size: 14px; color: #555; text-transform: uppercase; }
        .info .box p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #2563eb; color: #fff; padding: 10px 12px; text-align: {{ $locale === 'ar' ? 'right' : 'left' }}; font-size: 12px; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        .total-table { width: 300px; margin-{{ $locale === 'ar' ? 'left' : 'right' }}: 0; margin-{{ $locale === 'ar' ? 'right' : 'left' }}: auto; }
        .total-table td { border: none; padding: 6px 12px; }
        .total-table .final td { font-weight: bold; font-size: 16px; color: #2563eb; border-top: 2px solid #2563eb; padding-top: 10px; }
        .footer { text-align: center; margin-top: 40px; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $company['name'] }}</h1>
            <p>{{ $company['email'] }}</p>
        </div>
        <div class="meta">
            <p><strong>{{ $locale === 'ar' ? 'رقم الفاتورة' : 'Invoice' }}:</strong> {{ $order->order_number }}</p>
            <p><strong>{{ $locale === 'ar' ? 'التاريخ' : 'Date' }}:</strong> {{ $order->created_at->format('Y-m-d') }}</p>
            <p>
                <strong>{{ $locale === 'ar' ? 'الحالة' : 'Status' }}:</strong>
                <span class="badge badge-{{ $order->payment_status === 'paid' ? 'paid' : 'pending' }}">
                    {{ $order->payment_status === 'paid' ? ($locale === 'ar' ? 'مدفوع' : 'Paid') : ($locale === 'ar' ? 'معلق' : 'Pending') }}
                </span>
            </p>
        </div>
    </div>

    <div class="info">
        <div class="box">
            <h3>{{ $locale === 'ar' ? 'فاتورة إلى' : 'Bill To' }}</h3>
            <p>{{ $order->user->first_name }} {{ $order->user->last_name }}</p>
            <p>{{ $order->user->email }}</p>
            @if($order->shippingAddress)
                <p>{{ $order->shippingAddress->street_address }}</p>
                <p>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }}</p>
                <p>{{ $order->shippingAddress->country }} {{ $order->shippingAddress->postal_code }}</p>
            @endif
        </div>
        <div class="box">
            <h3>{{ $locale === 'ar' ? 'الشحن إلى' : 'Ship To' }}</h3>
            @if($order->shippingAddress)
                <p>{{ $order->shippingAddress->street_address }}</p>
                <p>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }}</p>
                <p>{{ $order->shippingAddress->country }} {{ $order->shippingAddress->postal_code }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ $locale === 'ar' ? 'المنتج' : 'Product' }}</th>
                <th>{{ $locale === 'ar' ? 'الكمية' : 'Qty' }}</th>
                <th>{{ $locale === 'ar' ? 'السعر' : 'Price' }}</th>
                <th>{{ $locale === 'ar' ? 'المجموع' : 'Total' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->{'product_name_' . $locale} ?: ($item->product?->{'name_' . $locale} ?? '') }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }} {{ $locale === 'ar' ? 'ر.س' : 'SAR' }}</td>
                <td>{{ number_format($item->total_price, 2) }} {{ $locale === 'ar' ? 'ر.س' : 'SAR' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total-table">
        <tr>
            <td>{{ $locale === 'ar' ? 'المجموع الفرعي' : 'Subtotal' }}</td>
            <td style="text-align: {{ $locale === 'ar' ? 'left' : 'right' }}">{{ number_format($order->total_amount, 2) }} SAR</td>
        </tr>
        @if((float)$order->discount_amount > 0)
        <tr>
            <td>{{ $locale === 'ar' ? 'الخصم' : 'Discount' }} @if($order->coupon_code)({{ $order->coupon_code }})@endif</td>
            <td style="text-align: {{ $locale === 'ar' ? 'left' : 'right' }}">-{{ number_format($order->discount_amount, 2) }} SAR</td>
        </tr>
        @endif
        @if((float)$order->shipping_amount > 0)
        <tr>
            <td>{{ $locale === 'ar' ? 'الشحن' : 'Shipping' }}</td>
            <td style="text-align: {{ $locale === 'ar' ? 'left' : 'right' }}">{{ number_format($order->shipping_amount, 2) }} SAR</td>
        </tr>
        @endif
        @if((float)$order->tax_amount > 0)
        <tr>
            <td>{{ $locale === 'ar' ? 'الضريبة' : 'Tax' }}</td>
            <td style="text-align: {{ $locale === 'ar' ? 'left' : 'right' }}">{{ number_format($order->tax_amount, 2) }} SAR</td>
        </tr>
        @endif
        <tr class="final">
            <td>{{ $locale === 'ar' ? 'الإجمالي' : 'Total' }}</td>
            <td style="text-align: {{ $locale === 'ar' ? 'left' : 'right' }}">{{ number_format($order->final_amount, 2) }} SAR</td>
        </tr>
    </table>

    @if($order->notes)
    <div style="margin-top: 20px;">
        <h3>{{ $locale === 'ar' ? 'ملاحظات' : 'Notes' }}</h3>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>{{ $locale === 'ar' ? 'شكراً لتسوقك معنا' : 'Thank you for your business' }}</p>
        <p>{{ $company['name'] }} | {{ $company['email'] }}</p>
    </div>
</body>
</html>
