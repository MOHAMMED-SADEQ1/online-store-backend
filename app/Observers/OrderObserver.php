<?php

namespace App\Observers;

use App\Mail\Order\OrderCancelled;
use App\Mail\Order\OrderConfirmed;
use App\Mail\Order\OrderDelivered;
use App\Mail\Order\OrderShipped;
use App\Models\Notification;
use App\Models\Order;
use App\Services\AuditService;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    public function __construct(
        protected AuditService $auditService,
        protected LoyaltyService $loyaltyService,
    ) {}

    public function created(Order $order): void
    {
        $this->auditService->log('created', $order, null, $order);

        if ($order->payment_status === 'paid' && $order->order_status === 'confirmed') {
            $this->sendNotifications($order);
            $this->awardLoyaltyPoints($order);
        }
    }

    public function updated(Order $order): void
    {
        $before = new Order();
        $before->setRawAttributes($order->getOriginal());
        $this->auditService->log('updated', $order, $before, $order);

        $changed = $order->getChanges();

        if (isset($changed['order_status'])) {
            $this->sendNotifications($order);

            // Award loyalty points when order is confirmed (paid)
            if ($order->order_status === 'confirmed' && $order->payment_status === 'paid') {
                $this->awardLoyaltyPoints($order);
            }

            // Process referral rewards when first order is delivered
            if ($order->order_status === 'delivered') {
                $this->processReferralReward($order);
            }
        }
    }

    public function deleted(Order $order): void
    {
        $this->auditService->log('deleted', $order, $order, null);
    }

    private function sendNotifications(Order $order): void
    {
        $locale = $order->user?->locale ?? 'ar';
        $this->createDbNotification($order, $locale);
        $this->sendStatusEmail($order, $locale);
    }

    /**
     * Award loyalty points to the customer for this order.
     */
    private function awardLoyaltyPoints(Order $order): void
    {
        try {
            $this->loyaltyService->earnPointsForPurchase($order);
            Log::info('Loyalty points awarded', [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
                'amount'   => $order->final_amount,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to award loyalty points', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process referral reward when referred user completes first order.
     */
    private function processReferralReward(Order $order): void
    {
        try {
            $user = $order->user;
            if (!$user) return;

            // Check if this user was referred
            $referralRedemption = \App\Models\ReferralRedemption::where('referred_user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($referralRedemption) {
                $this->loyaltyService->processReferralReward(
                    $referralRedemption->referralCode,
                    $user,
                    $order
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to process referral reward', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function createDbNotification(Order $order, string $locale): void
    {
        $notificationData = match ($order->order_status) {
            'confirmed' => [
                'type'     => 'order_status',
                'title_ar' => 'تم تأكيد الطلب #' . $order->order_number,
                'title_en' => 'Order #' . $order->order_number . ' Confirmed',
                'body_ar'  => 'تم تأكيد طلبك رقم ' . $order->order_number . ' وجاري تجهيزه.',
                'body_en'  => 'Your order #' . $order->order_number . ' has been confirmed and is being processed.',
            ],
            'shipped'   => [
                'type'     => 'order_status',
                'title_ar' => 'تم شحن الطلب #' . $order->order_number,
                'title_en' => 'Order #' . $order->order_number . ' Shipped',
                'body_ar'  => 'تم شحن طلبك رقم ' . $order->order_number . ' وهو في طريقه إليك.',
                'body_en'  => 'Your order #' . $order->order_number . ' has been shipped.',
            ],
            'delivered' => [
                'type'     => 'order_status',
                'title_ar' => 'تم توصيل الطلب #' . $order->order_number,
                'title_en' => 'Order #' . $order->order_number . ' Delivered',
                'body_ar'  => 'تم توصيل طلبك رقم ' . $order->order_number . '. قيّم المنتجات الآن!',
                'body_en'  => 'Your order #' . $order->order_number . ' has been delivered. Rate your products now!',
            ],
            'cancelled' => [
                'type'     => 'order_status',
                'title_ar' => 'تم إلغاء الطلب #' . $order->order_number,
                'title_en' => 'Order #' . $order->order_number . ' Cancelled',
                'body_ar'  => 'تم إلغاء طلبك رقم ' . $order->order_number . '.',
                'body_en'  => 'Your order #' . $order->order_number . ' has been cancelled.',
            ],
            default     => null,
        };

        if ($notificationData && $order->user_id) {
            try {
                Notification::create([
                    'user_id'  => $order->user_id,
                    'type'     => $notificationData['type'],
                    'title_ar' => $notificationData['title_ar'],
                    'title_en' => $notificationData['title_en'],
                    'body_ar'  => $notificationData['body_ar'],
                    'body_en'  => $notificationData['body_en'],
                    'data'     => ['order_id' => $order->id, 'order_number' => $order->order_number],
                    'is_read'  => false,
                ]);
                Log::info('Order notification created', [
                    'order_id' => $order->id,
                    'status'   => $order->order_status,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create order notification', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendStatusEmail(Order $order, string $locale): void
    {
        try {
            $mailable = match ($order->order_status) {
                'confirmed' => new OrderConfirmed($order),
                'shipped'   => new OrderShipped($order),
                'delivered' => new OrderDelivered($order),
                'cancelled' => new OrderCancelled($order),
                default     => null,
            };

            if ($mailable && $order->user && $order->user->email) {
                Mail::to($order->user->email)->send($mailable);
                Log::info('Order status email sent', [
                    'order_id' => $order->id,
                    'status'   => $order->order_status,
                    'email'    => $order->user->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send order status email', [
                'order_id' => $order->id,
                'status'   => $order->order_status,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
