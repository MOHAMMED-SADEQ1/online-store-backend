<!DOCTYPE html>
<html dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ __('mail.order_confirmed_subject') }}</title></head>
<body style="font-family: 'DejaVu Sans', Arial, sans-serif; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="background: #2563eb; color: #fff; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">{{ __('mail.order_confirmed_title') }}</h1>
        </div>
        <div style="padding: 20px;">
            <p>{{ __('mail.hello', ['name' => $order->user->first_name]) }}</p>
            <p>{{ __('mail.order_confirmed_body', ['number' => $order->order_number]) }}</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td><strong>{{ __('mail.order_number') }}:</strong></td>
                    <td>{{ $order->order_number }}</td>
                </tr>
                <tr>
                    <td><strong>{{ __('mail.order_total') }}:</strong></td>
                    <td>{{ number_format($order->final_amount, 2) }} SAR</td>
                </tr>
                <tr>
                    <td><strong>{{ __('mail.order_date') }}:</strong></td>
                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            </table>
            <a href="{{ url('/customer/orders/' . $order->id) }}" style="display: inline-block; background: #2563eb; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px;">{{ __('mail.view_order') }}</a>
        </div>
        <div style="background: #f9fafb; padding: 15px; text-align: center; font-size: 12px; color: #9ca3af;">
            <p>{{ config('app.name') }} &mdash; {{ __('mail.thank_you') }}</p>
        </div>
    </div>
</body>
</html>
