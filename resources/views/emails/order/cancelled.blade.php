<!DOCTYPE html>
<html dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ __('mail.order_cancelled_subject') }}</title></head>
<body style="font-family: 'DejaVu Sans', Arial, sans-serif; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="background: #dc2626; color: #fff; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">{{ __('mail.order_cancelled_title') }}</h1>
        </div>
        <div style="padding: 20px;">
            <p>{{ __('mail.hello', ['name' => $order->user->first_name]) }}</p>
            <p>{{ __('mail.order_cancelled_body', ['number' => $order->order_number]) }}</p>
            @if($order->cancel_reason)
            <p><strong>{{ __('mail.cancel_reason') }}:</strong> {{ $order->cancel_reason }}</p>
            @endif
            <p>{{ __('mail.order_cancelled_refund') }}</p>
        </div>
        <div style="background: #f9fafb; padding: 15px; text-align: center; font-size: 12px; color: #9ca3af;">
            <p>{{ config('app.name') }} &mdash; {{ __('mail.thank_you') }}</p>
        </div>
    </div>
</body>
</html>
