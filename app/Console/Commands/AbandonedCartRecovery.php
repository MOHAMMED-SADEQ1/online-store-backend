<?php

namespace App\Console\Commands;

use App\Mail\Order\AbandonedCart;
use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AbandonedCartRecovery extends Command
{
    protected $signature = 'orders:recover-abandoned-carts {--hours=24 : Hours since last activity}';
    protected $description = 'Send recovery emails for abandoned carts';

    public function handle(): void
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $carts = Cart::whereHas('user')
            ->whereHas('items')
            ->where('updated_at', '<=', $cutoff)
            ->whereDoesntHave('user.orders', fn($q) => $q->where('created_at', '>=', $cutoff))
            ->with(['user', 'items.product'])
            ->get();

        $count = 0;
        foreach ($carts as $cart) {
            try {
                Mail::to($cart->user->email)->send(new AbandonedCart($cart));
                Log::info('Abandoned cart email sent', ['cart_id' => $cart->id, 'user' => $cart->user->email]);
                $count++;
            } catch (\Exception $e) {
                Log::warning('Failed to send abandoned cart email', [
                    'cart_id' => $cart->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$count} abandoned cart recovery emails.");
    }
}
