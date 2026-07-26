<!DOCTYPE html>
<html dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ __('mail.abandoned_cart_subject') }}</title></head>
<body style="font-family: 'DejaVu Sans', Arial, sans-serif; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="background: #f59e0b; color: #fff; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">{{ __('mail.abandoned_cart_title') }}</h1>
        </div>
        <div style="padding: 20px;">
            <p>{{ __('mail.hello', ['name' => $user->first_name]) }}</p>
            <p>{{ __('mail.abandoned_cart_body') }}</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr style="background: #f3f4f6;">
                    <th style="padding: 10px; text-align: {{ $locale === 'ar' ? 'right' : 'left' }};">{{ __('mail.product') }}</th>
                    <th style="padding: 10px; text-align: center;">{{ __('mail.qty') }}</th>
                    <th style="padding: 10px; text-align: {{ $locale === 'ar' ? 'left' : 'right' }};">{{ __('mail.price') }}</th>
                </tr>
                @foreach($cart->items as $item)
                <tr>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #eee;">{{ $item->product?->{'name_' . $locale} }}</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #eee; text-align: center;">{{ $item->quantity }}</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #eee; text-align: {{ $locale === 'ar' ? 'left' : 'right' }};">{{ number_format($item->product?->sale_price ?? $item->product?->regular_price ?? 0, 2) }} SAR</td>
                </tr>
                @endforeach
            </table>
            <a href="{{ url('/customer/cart') }}" style="display: inline-block; background: #f59e0b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px;">{{ __('mail.complete_order') }}</a>
        </div>
        <div style="background: #f9fafb; padding: 15px; text-align: center; font-size: 12px; color: #9ca3af;">
            <p>{{ config('app.name') }} &mdash; {{ __('mail.thank_you') }}</p>
        </div>
    </div>
</body>
</html>
