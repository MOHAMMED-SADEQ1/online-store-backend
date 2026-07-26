<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('gift_card.email_subject') }}</title>
    <style>
        body { font-family: 'Tahoma', 'Arial', sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 28px; }
        .header p { color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px; }
        .body { padding: 30px; }
        .gift-card-code { background: #f8f9fa; border: 2px dashed #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
        .gift-card-code .code { font-size: 32px; font-weight: bold; color: #764ba2; letter-spacing: 4px; font-family: 'Courier New', monospace; }
        .gift-card-code .label { font-size: 14px; color: #666; margin-bottom: 10px; }
        .amount { text-align: center; font-size: 48px; font-weight: bold; color: #667eea; margin: 20px 0; }
        .message { background: #fff3cd; border-radius: 8px; padding: 15px; margin: 20px 0; font-style: italic; color: #856404; }
        .details { color: #666; font-size: 14px; line-height: 1.8; }
        .footer { text-align: center; padding: 20px; color: #999; font-size: 12px; border-top: 1px solid #eee; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: #fff; text-decoration: none; border-radius: 6px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 {{ __('gift_card.email_title') }}</h1>
            @if($giftCard->recipient_name)
                <p>{{ __('gift_card.email_greeting', ['name' => $giftCard->recipient_name]) }}</p>
            @endif
        </div>
        <div class="body">
            <div class="amount">
                {{ number_format($giftCard->original_balance, 2) }} {{ __('common.sar') }}
            </div>

            <div class="gift-card-code">
                <div class="label">{{ __('gift_card.your_code') }}</div>
                <div class="code">{{ $giftCard->code }}</div>
            </div>

            @if($giftCard->message)
                <div class="message">
                    💬 {{ $giftCard->message }}
                </div>
            @endif

            <div class="details">
                <p>{{ __('gift_card.email_instructions') }}</p>
                @if($giftCard->expires_at)
                    <p>{{ __('gift_card.expires_at') }}: {{ $giftCard->expires_at->format('Y-m-d') }}</p>
                @endif
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="btn">{{ __('gift_card.shop_now') }}</a>
            </div>
        </div>
        <div class="footer">
            <p>{{ config('app.name') }} &copy; {{ date('Y') }}</p>
            <p>{{ __('gift_card.email_footer') }}</p>
        </div>
    </div>
</body>
</html>
