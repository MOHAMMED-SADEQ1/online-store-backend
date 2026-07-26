<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function links(Request $request, Product $product): JsonResponse
    {
        $locale = app()->getLocale();
        $productUrl = url("/{$product->slug}/p{$product->id}");
        $productName = $product->{'name_' . $locale};
        $image = $product->main_image ? url($product->main_image) : null;

        $shareLinks = [
            'whatsapp' => [
                'url'   => "https://wa.me/?text=" . urlencode("{$productName} - {$productUrl}"),
                'label' => $locale === 'ar' ? 'واتساب' : 'WhatsApp',
                'icon'  => 'whatsapp',
            ],
            'facebook' => [
                'url'   => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($productUrl),
                'label' => $locale === 'ar' ? 'فيسبوك' : 'Facebook',
                'icon'  => 'facebook',
            ],
            'twitter' => [
                'url'   => "https://twitter.com/intent/tweet?text=" . urlencode("{$productName} - {$productUrl}"),
                'label' => $locale === 'ar' ? 'تويتر' : 'X (Twitter)',
                'icon'  => 'twitter',
            ],
            'email' => [
                'url'   => "mailto:?subject=" . urlencode($productName) . "&body=" . urlencode($productUrl),
                'label' => $locale === 'ar' ? 'البريد الإلكتروني' : 'Email',
                'icon'  => 'email',
            ],
            'copy_link' => [
                'url'   => $productUrl,
                'label' => $locale === 'ar' ? 'نسخ الرابط' : 'Copy Link',
                'icon'  => 'copy',
            ],
        ];

        return response()->json([
            'product' => [
                'id'   => $product->id,
                'name' => $productName,
                'slug' => $product->slug,
                'url'  => $productUrl,
                'image' => $image,
            ],
            'share_links' => $shareLinks,
        ]);
    }
}
