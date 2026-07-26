<!DOCTYPE html>
<html dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ __('mail.order_shipped_subject') }}</title></head>
<body style="font-family: 'DejaVu Sans', Arial, sans-serif; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="background: #059669; color: #fff; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">{{ __('mail.order_shipped_title') }}</h1>
        </div>
        <div style="padding: 20px;">
            <p>{{ __('mail.hello', ['name' => $order->user->first_name]) }}</p>
            <p>{{ __('mail.order_shipped_body', ['number' => $order->order_number]) }}</p>
            @if($order->shipping && $order->shipping->tracking_number)
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td><strong>{{ __('mail.tracking_number') }}:</strong></td>
                    <td>{{ $order->shipping->tracking_number }}</td>
                </tr>
                <tr>
                    <td><strong>{{ __('mail.carrier') }}:</strong></td>
                    <td>{{ $order->shipping->carrier }}</td>
                </tr>
                @if($order->shipping->estimated_delivery)
                <tr>
                    <td><strong>{{ __('mail.estimated_delivery') }}:</strong></td>
                    <td>{{ $order->shipping->estimated_delivery->format('Y-m-d') }}</td>
                </tr>
                @endif
            </table>
            @endif
            <a href="{{ url('/customer/orders/' . $order->id) }}" style="display: inline-block; background: #059669; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px;">{{ __('mail.track_order') }}</a>
        </div>
        <div style="background: #f9fafb; padding: 15px; text-align: center; font-size: 12px; color: #9ca3af;">
            <p>{{ config('app.name') }} &mdash; {{ __('mail.thank_you') }}</p>
        </div>
    </div>
</body>
</html>
