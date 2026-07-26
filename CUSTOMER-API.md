# Customer API Documentation

Base URL: `http://localhost:8000/api/customer`

## Language / Locale

All Customer API endpoints support both Arabic (`ar`) and English (`en`).  
The language is determined by (in order of priority):

1. **Route prefix**: `/api/ar/customer/...` or `/api/en/customer/...`
2. **Header**: `Accept-Language: ar` or `Accept-Language: en`
3. **Query param**: `?lang=ar` or `?lang=en`
4. **User locale**: Authenticated user's saved `locale` setting
5. **Default**: `ar` (Arabic)

All response messages (success, error, validation) are translated accordingly.  
Content fields (`name`, `description`, etc.) are returned in the appropriate language based on the detected locale.

**Examples:**
```
GET  /api/ar/customer/products    → Arabic messages
GET  /api/en/customer/cart        → English messages
POST /api/customer/orders         → Accept-Language or user locale
```

---

## 1. Authentication (OTP Flow)

No password login. Customers authenticate via email OTP (6 digits).

### Send OTP
```
POST /auth/send-otp
Content-Type: application/json

{
  "identifier": "user@example.com"
}
```
**Response (200):**
```json
{
  "message": "OTP sent successfully.",
  "identifier_type": "email"
}
```
> OTP expires in 5 minutes. A new OTP invalidates any previous unverified one.

### Verify OTP
```
POST /auth/verify-otp
Content-Type: application/json

{
  "identifier": "user@example.com",
  "otp": "4821"
}
```
**Response — existing user (login):**
```json
{
  "status": "login",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "username": "john",
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": null,
    "date_of_birth": null,
    "locale": "ar",
    "role": "customer"
  },
  "is_new": false
}
```
**Response — new user (requires registration):**
```json
{
  "status": "register_required",
  "identifier": "user@example.com",
  "identifier_type": "email",
  "temp_token": "abc123...60chars...",
  "expires_in": 900
}
```
> `temp_token` is valid for 15 minutes. Use it to complete registration.

### Complete Registration
```
POST /auth/complete-registration
Content-Type: application/json

{
  "temp_token": "abc123...60chars...",
  "first_name": "John",
  "last_name": "Doe"
}
```
**Response (201):**
```json
{
  "status": "registered",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "username": "john_4821",
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": null,
    "date_of_birth": null,
    "locale": "ar",
    "role": "customer"
  },
  "is_new": true
}
```
> `username` is auto-generated from email prefix + random suffix.

### Profile (Auth Required)
```
GET /profile
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "user": { "id": 1, "username": "john_4821", "email": "user@example.com", "first_name": "John", "last_name": "Doe", "phone": null, "date_of_birth": null, "locale": "ar", "role": "customer" }
}
```

### Update Profile (Auth Required)
```
PUT /profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "Johnny",
  "phone": "+971509876543"
}
```
**Response (200):**
```json
{
  "message": "Profile updated successfully.",
  "user": { "...": "..." }
}
```

### Merge Guest Cart (Auth Required)
```
POST /auth/merge-cart
Authorization: Bearer {token}
Content-Type: application/json

{
  "guest_token": "abc-123-def"
}
```
**Response (200):**
```json
{
  "message": "Cart merged.",
  "cart": { "... cart object ..." }
}
```

---

## 2. Public Endpoints

### Homepage
```
GET /home
```

**Response (200):**
```json
{
  "slider": [
    { "id": 1, "name": "منتج", "slug": "product", "image": "http://...", "regular_price": 100, "sale_price": 80 }
  ],
  "featured_products": [ "... ProductListItem ..." ],
  "new_arrivals": [ "... ProductListItem ..." ],
  "best_sellers": [ "... ProductListItem ..." ],
  "categories": [
    { "id": 1, "name": "إلكترونيات", "slug": "electronics", "image": null, "children": [], "products_count": 20 }
  ],
  "locale": "ar"
}
```

### Product Listing
```
GET /products?page=1&per_page=20&search=phone&category_id=1&min_price=10&max_price=1000&sort=price_asc
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Search in name, description |
| `category_id` | int | Filter by category |
| `min_price` | float | Minimum price |
| `max_price` | float | Maximum price |
| `sort` | string | `price_asc`, `price_desc`, `newest`, `oldest`, `name_asc`, `name_desc`, `rating` |
| `per_page` | int | Items per page (default: 20) |
| `page` | int | Page number |

**Response — ProductListItem (paginated):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "منتج",
      "slug": "product",
      "regular_price": 100.00,
      "sale_price": 80.00,
      "price_includes_tax": false,
      "first_image": "http://localhost/storage/products/abc.jpg",
      "is_featured": true,
      "stock_status": "in_stock",
      "max_per_order": null,
      "rating": 4.5,
      "review_count": 10,
      "categories": [
        { "id": 1, "name": "إلكترونيات", "slug": "electronics" }
      ]
    }
  ],
  "total": 50,
  "per_page": 20,
  "last_page": 3
}
```

### Product Detail
```
GET /products/{slug}/p{product}
```
> `{product}` is bound via route model binding (e.g. `/products/هيرش-محور/p123`)

**Response (200):**
```json
{
  "product": {
    "id": 1,
      "name": "...",
    "slug": "...",
    "description": "...",
    "sku": "SKU-001",
    "regular_price": 100.00,
    "sale_price": 80.00,
    "price_includes_tax": false,
    "quantity_in_stock": 50,
    "stock_status": "in_stock",
    "max_per_order": null,
    "low_stock_threshold": 0,
    "weight": 0.5,
    "dimensions": "10x10x5",
    "is_active": true,
    "is_featured": true,
    "meta_title": null,
    "meta_description": null,
    "images": [
      { "id": 1, "image_url": "http://localhost/storage/products/abc.jpg", "alt_text": "...", "is_main": true }
    ],
    "variants": [
      {
        "id": 1, "sku": "VAR-001", "regular_price": 100.00, "sale_price": 80.00,
        "stock_quantity": 20, "is_active": true,
        "images": [ { "image_url": "http://localhost/storage/variants/xyz.jpg", "alt_text": "...", "is_main": true } ],
        "attribute_values": [
          { "id": 1, "value": "أحمر", "attribute": { "id": 1, "name": "لون" } }
        ]
      }
    ],
    "reviews": [ { "id": 1, "rating": 5, "review_title": "Great", "review_text": "...", "user_name": "John", "created_at": "..." } ],
    "categories": [ "... product categories ..." ],
    "tags": [ "{ id, name, slug }" ],
    "attributes": [ { "id": 1, "name": "لون", "attribute_type": "select" } ]
  }
}
```

### Related Products
```
GET /products/{slug}/p{product}/related
```
**Response (200):**
```json
{
  "products": [ "... ProductListItem[] ..." ]
}
```

### Frequently Bought Together
```
GET /products/{slug}/p{product}/frequently-bought-together
```
**Response (200):**
```json
{
  "products": [ "... ProductListItem[] (max 4) ..." ]
}
```

### Categories
```
GET /categories
```

**Response (200):**
```json
{
  "categories": [
    {
      "id": 1, "name": "إلكترونيات", "slug": "electronics",
      "description": "...", "image": null,
      "parent_id": null, "display_order": 0,
      "children": [ { "id": 2, "name": "هواتف", "children": [], "products_count": 10 } ],
      "products_count": 30
    }
  ]
}
```

### Category Detail with Products
```
GET /categories/{id}?per_page=20&page=1
```
**Response (200):**
```json
{
  "category": { "...": "..." },
  "products": { "paginated": "ProductListItem" }
}
```

### Advanced Filters
```
GET /products/filters
```
**Response (200):**
```json
{
  "price_range": { "min": 10, "max": 5000 },
  "categories": [
    { "id": 1, "name": "إلكترونيات", "products_count": 20 }
  ],
  "attributes": [
    { "id": 1, "name": "لون", "values": [
      { "id": 1, "value": "أحمر" }
    ] }
  ]
}
```

### Search Suggestions
```
GET /products/search-suggestions?q=pro
```
**Response (200):**
```json
{
  "suggestions": [
    { "id": 1, "name": "منتج", "image": "http://..." }
  ]
}
```

### Validate Coupon
```
POST /coupons/validate
Content-Type: application/json

{
  "code": "SAVE10",
  "subtotal": 500.00,
  "cart_items": [
    { "product_id": 1, "price": 250.00, "quantity": 2 }
  ]
}
```

**Request fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `code` | string | ✅ | Coupon code |
| `subtotal` | float | ✅ | Cart subtotal (before discount) |
| `cart_items` | array | ❌ | Cart items for applicable_to validation |
| `cart_items[].product_id` | int | ❌ | Product ID |
| `cart_items[].variant_id` | int | ❌ | Variant ID (for variant-specific pricing; price is read from DB) |
| `cart_items[].quantity` | int | ❌ | Quantity |

**Response (200):**
```json
{
  "valid": true,
  "coupon": { "id": 1, "code": "SAVE10", "discount_type": "percentage", "discount_value": 10.00, "is_free_shipping": false },
  "discount_amount": 50.00,
  "is_free_shipping": false
}
```

**Special coupon types:**
| Scenario | Backend Setup | Example |
|----------|---------------|---------|
| General discount | `type: percentage, value: 10` | 10% off entire cart |
| Category-specific | `applicable_to: categories` + `coupon_categories` | 10% off Electronics only |
| Product-specific | `applicable_to: products` + `coupon_products` | 50 SAR off iPhone 15 only |
| Free shipping | `is_free_shipping: true, minimum_order_amount: 200` | Free shipping for orders over 200 SAR |
| User-specific | `user_id: 5` | Only John can use this code |
| Repeat purchase | `min_orders_count: 3` | Requires 3+ completed orders |
| Limited per user | `per_user_limit: 2` | Can only be used twice per user |

**Response (422):** `{ "valid": false, "message": "Coupon expired." }`

### Guest Cart
```
POST /guest/cart
Content-Type: application/json

{
  "items": [
    { "product_id": 1, "quantity": 2, "variant_id": null }
  ],
  "guest_token": "uuid-from-frontend"
}
```
**Response (200):**
```json
{
  "cart": { "... cart object ..." }
}
```

### Flash Sales
```
GET /flash-sales
```
**Response (200):**
```json
{
  "flash_sales": [
    {
      "id": 1,
      "title": "تخفيضات الصيف",
      "product_id": 1,
      "variant_id": null,
      "product_name": "منتج",
      "product_slug": "product",
      "product_image": "http://...",
      "regular_price": 100.00,
      "flash_price": 49.00,
      "discount_percent": 51,
      "remaining": 10,
      "sold_quantity": 40,
      "max_quantity": 50,
      "start_date": "2026-07-23T00:00:00+00:00",
      "end_date": "2026-07-25T23:59:59+00:00",
      "is_active": true
    }
  ]
}
```

### Share Product Links
```
GET /products/{slug}/p{id}/share
```
> Public endpoint, no auth required.

**Response (200):**
```json
{
  "product": {
    "id": 1, "name": "منتج", "slug": "product",
    "url": "http://localhost:8000/product/p1",
    "image": "http://..."
  },
  "share_links": {
    "whatsapp": { "url": "https://wa.me/?text=...", "label": "واتساب", "icon": "whatsapp" },
    "facebook": { "url": "https://www.facebook.com/sharer/...", "label": "فيسبوك", "icon": "facebook" },
    "twitter":  { "url": "https://twitter.com/intent/tweet?...", "label": "تويتر", "icon": "twitter" },
    "email":    { "url": "mailto:?...", "label": "البريد الإلكتروني", "icon": "email" },
    "copy_link": { "url": "http://...", "label": "نسخ الرابط", "icon": "copy" }
  }
}
```

### Compare Products

### Shipping Calculator
#### List Cities
```
GET /shipping/cities
```
**Response (200):**
```json
{
  "cities": [
    { "id": 1, "name": "الرياض" }
  ]
}
```

#### Calculate Shipping
```
POST /shipping/calculate
Content-Type: application/json

{
  "city_id": 1,
  "cart_subtotal": 500.00
}
```
**Response (200):**
```json
{
  "cost": 25.00,
  "estimated_days_min": 1,
  "estimated_days_max": 3,
  "provider": "Standard"
}
```

### Newsletter
```
POST /newsletter/subscribe
Content-Type: application/json

{
  "email": "user@example.com"
}
```
**Response (200):** `{ "message": "Subscribed successfully." }`

### Return Policy
```
GET /return-policy
```
**Response (200):**
```json
{
  "policy_text_ar": "يمكن إرجاع المنتجات خلال 14 يوم...",
  "policy_text_en": "Products can be returned within 14 days...",
  "default_days": 14,
  "conditions": [ "المنتج في حالته الأصلية", "العبوة الأصلية سليمة" ],
  "note": "قد تختلف مدة الإرجاع حسب المنتج. راجع صفحة المنتج للحصول على التفاصيل الدقيقة."
}
```

> **ملاحظة:** مدة الإرجاح الافتراضية هي 14 يوم، ولكن لكل منتج `return_period_days` خاص به.
> راجع حقل `return_period_days` في استجابة API المنتج لمعرفة المدة الدقيقة لكل منتج.

---

## 3. Customer Authenticated Endpoints

All require: `Authorization: Bearer {token}`

### Cart

#### Get Cart
```
GET /cart
```
**Response (200):**
```json
{
  "cart": {
    "id": 1, "coupon_code": null, "coupon_discount": 0,
    "subtotal": 500.00, "total": 500.00, "items_count": 2,
    "items": [
      {
        "id": 1, "product_id": 1, "variant_id": null, "quantity": 2,
        "product": { "id": 1, "name": "...", "slug": "...", "first_image": "http://..." },
        "variant": null, "unit_price": 100.00, "total_price": 200.00
      }
    ]
  }
}
```

#### Add to Cart
```
POST /cart
Body: { "product_id": 1, "variant_id": null, "quantity": 2 }
```
**Response (200):** Same cart structure.

#### Update Cart Item
```
PUT /cart/{itemId}
Body: { "quantity": 5 }
```
**Response (200):** Same cart structure.

#### Apply Coupon to Cart
```
POST /cart/apply-coupon
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "SAVE10"
}
```
**Response (200):**
```json
{
  "valid": true,
  "message": "Coupon applied successfully.",
  "discount_amount": 50.00,
  "is_free_shipping": false,
  "cart": { "... cart object ..." }
}
```
> The coupon is re-validated from DB before being stored. The discount amount is calculated server-side using DB prices.

**Response (422):** `{ "valid": false, "message": "Coupon expired." }`

#### Remove Coupon from Cart
```
DELETE /cart/coupon
Authorization: Bearer {token}
```
**Response (200):** `{ "message": "Coupon removed.", "cart": { "... cart object ..." } }`

#### Remove Cart Item
```
DELETE /cart/{itemId}
```
**Response (200):** `{ "message": "Item removed from cart.", "cart": {...} }`

### Orders

#### List Orders
```
GET /orders?per_page=15
```
**Response — paginated:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1, "order_number": "ORD-ABC123",
      "total_amount": 500.00, "tax_amount": 0, "shipping_amount": 0, "discount_amount": 0,
      "coupon_code": null, "final_amount": 500.00,
      "order_status": "pending", "payment_status": "pending",
      "notes": null, "created_at": "...",
      "items": [ "... item list ..." ],
      "shipping_address": { "...": "..." }
    }
  ],
  "total": 1, "per_page": 15, "last_page": 1
}
```

#### Create Order (لـ COD فقط — الطريقة القديمة)
```
POST /orders
```
> **ملاحظة:** للدفع الإلكتروني، استخدم `POST /checkout/pay` بدلًا من هذا الـ endpoint.  
> هذا الـ endpoint فقط لإنشاء طلبات الدفع عند الاستلام (COD) أو الطلبات المدفوعة مسبقًا إداريًا.

**Body:** `{ "shipping_address_id": 1, "notes": "Leave at door" }`

**Response (201):**
```json
{
  "message": "Order placed successfully.",
  "order": { "... order object ..." }
}
```

#### Order Detail
```
GET /orders/{id}
```
**Response (200):** Single order object (includes `cancelled_at` and `cancel_reason`).

#### Cancel Order

> **الإلغاء متاح فقط قبل الشحن (ما بين pending → confirmed → processing). بعد الشحن، يجب استخدام الإرجاع.**

```
POST /orders/{id}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Changed my mind"
}
```
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `reason` | string | no | Reason for cancellation |

**Availability by order status:**

| Order Status | Cancel Available? |
|--------------|:-----------------:|
| `pending`    | ✅ |
| `confirmed`  | ✅ |
| `processing` | ✅ |
| `shipped`    | ❌ ← use **Return** instead |
| `delivered`  | ❌ ← use **Return** instead |

**Auto actions on cancel:**
- Stock is automatically restored
- If payment was made (Moyasar, Sadad), refund is triggered automatically

**Product-level policy:** Some products may be marked as `is_cancellable = false` (e.g. digital goods, clearance items). In that case, cancellation is rejected with a per-product message even if the order status allows it.

**Response (200):**
```json
{
  "message": "Order cancelled successfully.",
  "order": { "... order object ..." }
}
```

**Response (422):** `{ "message": "This order cannot be cancelled at this stage." }`

#### Download Invoice (PDF)
```
GET /orders/{id}/invoice
Authorization: Bearer {token}
```
Returns PDF file: `invoice-ORD-ABC123.pdf`

#### Preview Invoice (HTML)
```
GET /orders/{id}/invoice-preview
Authorization: Bearer {token}
```
Returns HTML view of invoice (useful for printing).

#### Order Tracking Timeline
```
GET /orders/{id}/tracking
```
**Response (200):**
```json
{
  "current_status": "shipped",
  "estimated_delivery_date": "2026-06-10T00:00:00+00:00",
  "timeline": [
    { "status": "pending", "label_ar": "قيد الانتظار", "label_en": "Pending", "description": "", "timestamp": "...", "is_completed": true },
    { "status": "confirmed", "label_ar": "تم التأكيد", "label_en": "Confirmed", "description": "", "timestamp": "...", "is_completed": true },
    { "status": "shipped", "label_ar": "تم الشحن", "label_en": "Shipped", "description": "", "timestamp": "...", "is_completed": true },
    { "status": "in_transit", "label_ar": "في الطريق", "label_en": "In Transit", "description": "Carrier: Aramex / Track: AR12345", "timestamp": "...", "is_completed": false }
  ]
}
```

### Checkout / Payment (Moyasar) — الدفع أولًا ثم إنشاء الطلب

> **⚠️ مهم:** لا يتم إنشاء الطلب إلا بعد تأكيد الدفع.  
> للدفع الإلكتروني: `POST /checkout/pay` تنشئ `PendingCheckout`، ويتم إنشاء الطلب عبر Webhook عند نجاح الدفع.  
> للدفع عند الاستلام (COD): يتم إنشاء الطلب فورًا مع `payment_status: pending`.

---

#### List Payment Methods
```
GET /payment-methods
```
**Response (200):**
```json
{
  "payment_methods": [
    {
      "id": 1,
      "name_ar": "فيزا / ماستركارد (ميسر)",
      "name_en": "Visa / Mastercard (Moyasar)",
      "gateway": "moyasar",
      "is_online": true,
      "additional_fee": 0.00
    },
    {
      "id": 2,
      "name_ar": "الدفع عند الاستلام",
      "name_en": "Cash on Delivery",
      "gateway": "cod",
      "is_online": false,
      "additional_fee": 0.00
    }
  ]
}
```

---

#### Checkout & Pay (خطوة واحدة — بدون إنشاء طلب مسبق)
```
POST /checkout/pay
Authorization: Bearer {token}
```
**Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `shipping_address_id` | integer | yes | معرف عنوان الشحن |
| `billing_address_id` | integer | no | معرف عنوان الفوترة (افتراضيًا = shipping) |
| `payment_method_id` | integer | yes | طريقة الدفع (من GET /payment-methods) |
| `notes` | string | no | ملاحظات |
| `callback_url` | string | no | رابط يعود إليه العميل بعد 3DS |
| `token` | string | فقط للدفع الإلكتروني | Token من Moyasar.js (غير مطلوب لـ COD) |

**التدفق:**
1. **للدفع الإلكتروني (Moyasar):**
   - الفرونت إند ينشئ token عبر `POST /v1/tokens` (مع publishable key)
   - يُرسل token إلى `POST /checkout/pay`
   - **Backend يتحقق من حالة الـ token عبر `GET /v1/tokens/{id}` (secret key)**
     - إذا `active` → يكمل الدفع
     - إذا `initiated` ← يرجع `verification_url` للفرونت إند لتوثيق token
     - إذا `used`/`expired` ← خطأ
   - بعد توثيق الـ token → ينشئ `PendingCheckout` **(لا ينشئ طلب)**
   - يستدعي Moyasar API `POST /v1/payments` مع `3ds: true`
   - يرجع `payment_url` للتحقق 3DS للدفعة
   - السلة **لا تزال محتفظة** بمحتوياتها
   - بعد نجاح 3DS → ميسر ترسل Webhook → ينشئ الطلب + يخلي السلة
2. **للدفع عند الاستلام (COD):**
   - ينشئ الطلب فورًا + سجل الدفع
   - يخلي السلة
   - payment_status = pending (يُدفع عند التوصيل)

**Response (201) — COD (تم إنشاء الطلب فورًا):**
```json
{
  "message": "تم تأكيد الطلب.",
  "order": { "... order object ..." }
}
```

**Response (200) — Token يحتاج تفعيل 3DS (initiated):**
```json
{
  "status": "token_initiated",
  "token": "token_xxx",
  "verification_url": "https://api.moyasar.com/v1/tokens/.../verification",
  "message": "جاري تحويلك إلى صفحة الدفع الآمن."
}
```
→ الفرونت إند يُحول العميل إلى `verification_url` لتوثيق token عبر 3DS  
→ بعد العودة من 3DS، يُعيد إرسال نفس الـ token إلى `POST /checkout/pay` مرة أخرى

**Response (200) — Moyasar (3DS, الطلب لم ينشأ بعد):**
```json
{
  "checkout_id": 5,
  "payment_id": "pay_abc123",
  "payment_url": "https://api.moyasar.com/v1/3ds/...",
  "status": "initiated",
  "message": "جاري تحويلك إلى صفحة الدفع الآمن."
}
```

**Response (201) — Moyasar (مدفوع فورًا، تم إنشاء الطلب):**
```json
{
  "status": "paid",
  "order": { "... order object ..." },
  "message": "تم الدفع بنجاح."
}
```

---

#### Verify Payment Status
```
POST /checkout/{checkout_id}/verify
Authorization: Bearer {token}
```
يتحقق من حالة الدفع لجلسة الدفع. بعد نجاح Webhook، يعيد تفاصيل الطلب.

**Response (200) — مدفوع:**
```json
{
  "status": "paid",
  "message": "تم الدفع بنجاح.",
  "order": { "... order object ..." }
}
```

**Response (200) — معلق:**
```json
{
  "status": "pending",
  "message": "الدفع معلق."
}
```

**Status values:** `paid`, `pending`, `failed`, `no_checkout`

### Returns

#### List Returns
```
GET /orders/{id}/returns
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "returns": [
    {
      "id": 1, "order_id": 1, "return_type": "refund", "status": "pending",
      "notes": null, "created_at": "...",
      "items": [
        { "order_item_id": 1, "product_name": "Product", "quantity": 1, "reason": "Defective", "status": "pending" }
      ]
    }
  ]
}
```

#### Create Return
```
POST /orders/{id}/returns
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": [
    { "order_item_id": 1, "quantity": 1, "reason": "Defective" }
  ],
  "return_type": "refund",
  "notes": "Please refund"
}
```

**For exchange requests, include `exchange_items`:**
```json
{
  "items": [
    { "order_item_id": 1, "quantity": 1, "reason": "Wrong size" }
  ],
  "return_type": "exchange",
  "exchange_items": [
    { "product_id": 2, "variant_id": 3, "quantity": 1 }
  ],
  "notes": "Please exchange for size L"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `items` | array | ✅ | Items being returned |
| `items[].order_item_id` | int | ✅ | Order item ID |
| `items[].quantity` | int | ✅ | Quantity to return |
| `items[].reason` | string | no | Reason for return |
| `return_type` | string | ✅ | `refund` or `exchange` |
| `exchange_items` | array | only for exchange | Replacement products |
| `exchange_items[].product_id` | int | ✅ | Replacement product ID |
| `exchange_items[].variant_id` | int | no | Replacement variant ID |
| `exchange_items[].quantity` | int | ✅ | Replacement quantity |
| `notes` | string | no | Additional notes |

**Response (201):**
```json
{
  "message": "Return request submitted.",
  "return": { "... return object ..." }
}
```
> **Constraints:**
> - Only **delivered** orders can be returned.
> - Returns/exchanges must be requested within the product's `return_period_days` (default: 14 days).
> - **Product-level policy:** Each product has individual flags:
>   - `is_returnable` — if `false`, the product cannot be returned for refund
>   - `is_exchangeable` — if `false`, the product cannot be exchanged
>   - `return_period_days` — number of days allowed for return/exchange (may differ per product)
> - **Exchange flow:** When `return_type` is `exchange`, the system creates a return request with `exchange_items` and simultaneously places a **new exchange order** for the replacement products at no additional charge. The exchange order ID is stored in `exchange_order_id` on the return record.
>
> **Availability by order status:**
>
> | Order Status | Return (Refund) | Exchange |
> |--------------|:---------------:|:--------:|
> | `pending` | ❌ | ❌ |
> | `confirmed` | ❌ | ❌ |
> | `processing` | ❌ | ❌ |
> | `shipped` | ❌ | ❌ |
> | `delivered` | ✅ if `is_returnable = true` & within `return_period_days` | ✅ if `is_exchangeable = true` & within `return_period_days` |

### Addresses

#### List Addresses
```
GET /addresses
```
**Response:**
```json
{
  "addresses": [
    { "id": 1, "user_id": 1, "address_type": "home", "street_address": "123 Main St", "city": "Dubai", "state": null, "postal_code": null, "country": "UAE", "is_default": true, "latitude": null, "longitude": null, "building_number": null, "floor_number": null, "apartment_number": null, "additional_directions": null }
  ]
}
```

#### Create Address
```
POST /addresses
Body: {
  "address_type": "home",
  "street_address": "شارع الملك فهد",
  "city": "الرياض",
  "state": "الرياض",
  "country": "المملكة العربية السعودية",
  "is_default": true,
  "latitude": 24.7136,
  "longitude": 46.6753,
  "building_number": "12",
  "floor_number": "3",
  "apartment_number": "302",
  "additional_directions": "بجانب مسجد الفاروق"
}
```
**Response (201):** Address object (with all new fields).

> **Note:** `latitude` and `longitude` are **paired** (if one is sent, both are required). The frontend obtains these from the map (Leaflet + OpenStreetMap) via user pin placement or `navigator.geolocation`.

#### Update Address
```
PUT /addresses/{id}
Body: { "street_address": "456 New St", "floor_number": "5" }
```
**Response (200):** Updated address object.

#### Delete Address
```
DELETE /addresses/{id}
```
**Response (200):** `{ "message": "Address deleted successfully." }`

### Wishlist

#### List Wishlist
```
GET /wishlist
```
**Response:**
```json
{
  "wishlist": [
    {
      "id": 1, "product_id": 1, "variant_id": null,
      "product": { "id": 1, "name": "...", "slug": "...", "regular_price": 100.00, "sale_price": 80.00, "first_image": "http://..." }
    }
  ]
}
```

#### Add to Wishlist
```
POST /wishlist
Body: { "product_id": 1, "variant_id": null }
```
**Response (201):** `{ "message": "Added to wishlist.", "id": 1 }`

#### Remove from Wishlist
```
DELETE /wishlist/{id}
```
**Response (200):** `{ "message": "Removed from wishlist." }`

### Reviews

> **Constraint:** You can only review products you have purchased **and** received (order status = `delivered`). Duplicate reviews for the same product are not allowed.

#### List Products Available for Review
```
GET /reviews/purchasable
```
Returns products you've purchased & delivered but haven't reviewed yet.

**Response (200):**
```json
{
  "products": [
    {
      "product_id": 1, "product_name": "Product Name",
      "product_slug": "product-slug",
      "product_image": "http://...",
      "variant_id": null
    }
  ]
}
```

#### Add Review
```
POST /reviews
Body: { "product_id": 1, "rating": 5, "review_title": "Amazing!", "review_text": "Really loved this product." }
```
**Response (201):**
```json
{
  "message": "Review submitted and pending approval.",
  "review": { "id": 1, "rating": 5, "review_title": "Amazing!", "review_text": "...", "created_at": "..." }
}
```
**Error (422):** `{ "message": "You can only review products you have purchased and received." }`

### Recently Viewed

#### Log View
```
POST /recently-viewed
Body: { "product_id": 1, "variant_id": null }
```
**Response (200):** `{ "message": "View recorded." }`

### Notifications

> Notifications are **automatically created** when an order status changes to `confirmed`, `shipped`, `delivered`, or `cancelled`. The frontend can poll `GET /notifications` periodically (e.g. every 30s) or use the `unread_count` field to show a badge.

#### List Notifications
```
GET /notifications?page=1&per_page=20
```
**Response (200):**
```json
{
  "data": [
    {
      "id": 1, "type": "order_status", "title_ar": "تم تأكيد الطلب", "title_en": "Order Confirmed",
      "body_ar": "string | null", "body_en": "string | null",
      "data": { "order_id": 1 },
      "is_read": false, "created_at": "..."
    }
  ],
  "unread_count": 1,
  "total": 5
}
```

#### Mark as Read
```
PUT /notifications/{id}/read
```
**Response (200):** `{ "message": "Marked as read." }`

#### Mark All as Read
```
PUT /notifications/read-all
```
**Response (200):** `{ "message": "All marked as read." }`

---

## 4. Route Summary

### Public (No Auth) — 24 endpoints (+3 new)

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 1 | POST | `/auth/send-otp` | Send OTP to email identifier |
| 2 | POST | `/auth/verify-otp` | Verify OTP → login or request registration |
| 3 | POST | `/auth/complete-registration` | Create account after OTP verification |
| 4 | GET | `/home` | Homepage data |
| 5 | GET | `/flash-sales` | Active flash sales |
| 6 | GET | `/products` | Product listing with filters |
| 7 | GET | `/products/filters` | Advanced filter options |
| 8 | GET | `/products/search-suggestions` | Search autocomplete |
| 9 | GET | `/products/{slug}/p{product}` | Product detail |
| 10 | GET | `/products/{slug}/p{product}/related` | Related products |
| 11 | GET | `/products/{slug}/p{product}/frequently-bought-together` | Frequently bought together |
| 12 | GET | `/products/{slug}/p{product}/share` | Share product links (WhatsApp, Twitter, etc.) |
| 13 | GET | `/categories` | Category tree |
| 14 | GET | `/categories/{id}` | Category + products |
| 15 | POST | `/coupons/validate` | Validate coupon code (supports free shipping, user-specific, category/product) |
| 16 | POST | `/guest/cart` | Guest cart (session-based) |
| 17 | POST | `/compare` | Compare 2-4 products |
| 18 | GET | `/shipping/cities` | Available cities |
| 19 | POST | `/shipping/calculate` | Calculate shipping cost |
| 20 | POST | `/newsletter/subscribe` | Subscribe to newsletter |
| 21 | GET | `/return-policy` | Return policy text |
| **22** | **GET** | **`/search`** | **🧠 Full-text search with faceted filters, spell correction, sorting** |
| **23** | **GET** | **`/search/filters`** | **🔍 Faceted filter options (categories, brands, price, attributes)** |
| **24** | **GET** | **`/search/suggestions?q=...`** | **⚡ Auto-complete search suggestions (instant)** |
| **25** | **GET** | **`/recommendations/top-selling`** | **📈 Top-selling products** |

### Authenticated (Bearer Token) — 44 endpoints (+10 new)

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 1 | POST | `/auth/merge-cart` | Merge guest cart into user cart |
| 2 | GET | `/profile` | Customer profile |
| 3 | PUT | `/profile` | Update profile |
| 4 | GET | `/cart` | My cart |
| 5 | POST | `/cart` | Add to cart |
| 6 | PUT | `/cart/{id}` | Update cart item |
| 7 | DELETE | `/cart/{id}` | Remove from cart |
| 8 | POST | `/cart/apply-coupon` | Apply coupon to cart |
| 9 | DELETE | `/cart/coupon` | Remove coupon from cart |
| 10 | GET | `/payment-methods` | List payment methods |
| 11 | POST | `/checkout/pay` | Checkout & pay (online: PendingCheckout, COD: direct order) |
| 12 | POST | `/checkout/{id}/verify` | Verify payment status after 3DS |
| 13 | GET | `/orders` | My orders |
| 14 | POST | `/orders` | Place order (COD only) |
| 15 | GET | `/orders/{id}` | Order detail |
| 16 | POST | `/orders/{id}/cancel` | Cancel order (with optional reason, auto-refund) |
| 17 | GET | `/orders/{id}/tracking` | Order tracking timeline |
| 18 | GET | `/orders/{id}/invoice` | Download PDF invoice |
| 19 | GET | `/orders/{id}/invoice-preview` | Preview invoice HTML |
| 20 | GET | `/orders/{id}/returns` | List returns |
| 21 | POST | `/orders/{id}/returns` | Create return/exchange request |
| 22 | GET | `/addresses` | My addresses |
| 23 | POST | `/addresses` | Add address |
| 24 | PUT | `/addresses/{id}` | Update address |
| 25 | DELETE | `/addresses/{id}` | Delete address |
| 26 | GET | `/wishlist` | My wishlist |
| 27 | POST | `/wishlist` | Add to wishlist |
| 28 | DELETE | `/wishlist/{id}` | Remove from wishlist |
| 29 | GET | `/reviews/purchasable` | Products available for review |
| 30 | POST | `/reviews` | Add product review |
| 31 | POST | `/recently-viewed` | Log product view |
| 32 | GET | `/notifications` | List notifications |
| 33 | PUT | `/notifications/{id}/read` | Mark notification read |
| 34 | PUT | `/notifications/read-all` | Mark all read |
| **35** | **GET** | **`/loyalty/points`** | **⭐ Loyalty points balance & current tier** |
| **36** | **GET** | **`/loyalty/transactions`** | **📜 Points transaction history** |
| **37** | **GET** | **`/loyalty/tiers`** | **🏆 All available loyalty tiers** |
| **38** | **POST** | **`/loyalty/estimate`** | **💰 Estimate points redemption value** |
| **39** | **GET** | **`/loyalty/referral-code`** | **🔗 Get/share referral code** |
| **40** | **GET** | **`/loyalty/referral-history`** | **👥 Referral redemptions history** |
| **41** | **POST** | **`/loyalty/referral/register`** | **📝 Register with a referral code** |
| **42** | **POST** | **`/gift-cards/purchase`** | **🎁 Purchase a gift card** |
| **43** | **GET** | **`/gift-cards/purchased`** | **🎁 My purchased gift cards** |
| **44** | **POST** | **`/gift-cards/validate`** | **🎁 Validate a gift card code** |
| **45** | **POST** | **`/gift-cards/balance`** | **🎁 Check gift card balance** |

**Total: 70 endpoints** (25 public + 45 authenticated) — **+15 new**

---

## 5. Shared Types

### ProductListItem
> **Note:** `regular_price`, `sale_price`, and `first_image` reflect the **cheapest active variant**, not the base product. If the product has no variants, the base product fields are used.

```json
{
  "id": 1,
  "name": "...",
  "slug": "...",
  "regular_price": 100.00,
  "sale_price": 80.00 | null,
  "price_includes_tax": false,
  "first_image": "http://..." | null,
  "is_featured": true,
  "stock_status": "in_stock | low_stock | out_of_stock",
  "max_per_order": null | int,
  "is_returnable": true,
  "is_exchangeable": true,
  "return_period_days": 14,
  "is_cancellable": true,
  "rating": 4.5,
  "review_count": 10,
  "categories": [ "{ id, name, slug }" ]
}
```

### Product
> **Note:** `variants[]` are ordered by price ascending (cheapest first). The frontend should use `variants[0]` (cheapest) as the default selection for initial price/image display.

```json
{
  "id": 1, "name": "...", "slug": "...",
  "description": "...",
  "sku": "SKU", "regular_price": 100, "sale_price": 80 | null,
  "price_includes_tax": false,
  "quantity_in_stock": 50,
  "stock_status": "in_stock | low_stock | out_of_stock",
  "max_per_order": null | int,
  "low_stock_threshold": 0,
  "weight": 0.5, "dimensions": "10x10",
  "is_active": true, "is_featured": true,
  "is_returnable": true,
  "is_exchangeable": true,
  "return_period_days": 14,
  "is_cancellable": true,
  "meta_title": null | string,
  "meta_description": null | string,
  "images": [ "{ id, image_url, alt_text, is_main }" ],
  "variants": [ "{ id, sku, regular_price, sale_price, stock_quantity, is_active, images, attribute_values }" ],
  "reviews": [ "{ id, rating, review_title, review_text, user_name, created_at }" ],
  "categories": [ "{ id, name, slug }" ],
  "tags": [ "{ id, name, slug }" ],
  "attributes": [ "{ id, name, attribute_type }" ]
}
```

### Cart
```json
{
  "id": 1, "coupon_code": null, "coupon_discount": 0,
  "subtotal": 500, "total": 500, "items_count": 2,
  "items": [ "{ id, product_id, variant_id, quantity, product, variant, unit_price, total_price }" ]
}
```

### Order
```json
{
  "id": 1, "order_number": "ORD-ABC", "total_amount": 500,
  "tax_amount": 0, "shipping_amount": 0, "discount_amount": 0,
  "coupon_code": null, "final_amount": 500,
  "order_status": "pending", "payment_status": "pending",
  "notes": null, "cancel_reason": null, "cancelled_at": null,
  "created_at": "...",
  "items": [ "... OrderItem ..." ],
  "shipping_address": { "street_address": "...", "city": "...", "state": "...", "country": "...", "postal_code": "..." }
}
```

### Address
```json
{
  "id": 1, "user_id": 1, "address_type": "home",
  "street_address": "123 Main St", "city": "Dubai",
  "state": null, "postal_code": null, "country": "UAE",
  "is_default": true,
  "latitude": null, "longitude": null,
  "building_number": null,
  "floor_number": null,
  "apartment_number": null,
  "additional_directions": null
}
```

### ReturnRequest
```json
{
  "id": 1, "order_id": 1, "return_type": "refund",
  "status": "pending", "notes": null, "created_at": "...",
  "items": [
    { "order_item_id": 1, "product_name": "...", "quantity": 1, "reason": "...", "status": "pending" }
  ]
}
```

### ReturnRequest (Exchange)
```json
{
  "id": 2, "order_id": 1, "return_type": "exchange",
  "status": "pending", "notes": "Please exchange",
  "exchange_items": [
    { "product_id": 2, "variant_id": 3, "quantity": 1 }
  ],
  "exchange_order_id": null,
  "created_at": "...",
  "items": [ "... ReturnItem[] ..." ]
}
```

### ReturnPolicy
```json
{
  "policy_text_ar": "...",
  "policy_text_en": "...",
  "days_allowed": 14,
  "conditions": [ "string" ] | null
}
```

### Notification
```json
{
  "id": 1, "type": "order_status",
  "title_ar": "...", "title_en": "...",
  "body_ar": "string | null", "body_en": "string | null",
  "data": { "order_id": 1 },
  "is_read": false,
  "created_at": "..."
}
```

---

## 7. Advanced Search (Meilisearch) 🧠

> **Prerequisite:** Requires a running Meilisearch instance (`docker run -p 7700:7700 getmeili/meilisearch:v1.12`).  
> After Meilisearch is running, import products: `php artisan scout:import "App\Models\Product"`

The advanced search endpoint replaces the basic `GET /products` search with a powerful full-text search engine that provides:
- **Typo tolerance** – Finds products even with spelling mistakes
- **Faceted filtering** – Filter by category, brand, price range, attributes, stock status
- **Relevance-based sorting** – By price, date, rating, best-selling, or relevance
- **Instant auto-complete** – Search-as-you-type suggestions

### Full-Text Search
```
GET /search?q=هاتف&category_id=1&min_price=100&max_price=3000&in_stock=true&sort=price_asc&page=1&per_page=20
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `q` | string | Search query (with typo tolerance) |
| `category_id` | int/string | Filter by category ID(s). Comma-separated for multiple |
| `brand_id` | int/string | Filter by brand ID(s). Comma-separated for multiple |
| `min_price` | float | Minimum price filter |
| `max_price` | float | Maximum price filter |
| `in_stock` | bool | Only show in-stock products |
| `featured` | bool | Only show featured products |
| `min_rating` | float | Minimum average rating (0-5) |
| `attributes` | array | Filter by attribute values. E.g. `attributes[1]=أحمر,أزرق` (attribute_id=1, values=أحمر,أزرق) |
| `sort` | string | `relevance` (default), `price_asc`, `price_desc`, `newest`, `rating`, `best_selling` |
| `per_page` | int | Items per page (default: 20) |
| `page` | int | Page number |

**Response (200):** Same paginated `ProductListItem[]` structure as `GET /products`.

### Search Suggestions (Auto-Complete)
```
GET /search/suggestions?q=pro
```

**Response (200):**
```json
{
  "suggestions": [
    {
      "id": 1,
      "name": "منتج برو ماكس",
      "slug": "product-pro-max",
      "price": 1499.00,
      "image": "http://localhost/storage/products/abc.jpg"
    }
  ]
}
```

### Faceted Filter Options
```
GET /search/filters
```
Returns all available filter options for building dynamic filter UI.

**Response (200):**
```json
{
  "categories": [
    { "id": 1, "name": "إلكترونيات", "slug": "electronics", "products_count": 25 }
  ],
  "brands": [
    { "id": 1, "name": "سامسونج", "products_count": 10 }
  ],
  "price_range": {
    "min": 10.00,
    "max": 9999.00
  },
  "attributes": [
    {
      "id": 1,
      "name": "اللون",
      "type": "select",
      "values": [
        { "id": 1, "value": "أسود", "extra_data": null, "products_count": 15 }
      ]
    }
  ]
}
```

---

## 8. Loyalty Program ⭐

All loyalty endpoints require `Authorization: Bearer {token}`.

### Points & Tier Info
```
GET /loyalty/points
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "loyalty": {
    "balance": 1250,
    "lifetime_earned": 3200,
    "lifetime_spent": 1950,
    "points_value": 125.00,
    "tier": {
      "id": 2,
      "name": "فضي",
      "slug": "silver",
      "points_multiplier": 1.25,
      "discount_percent": 5.00,
      "free_shipping": false,
      "badge": null
    },
    "next_tier": {
      "name": "ذهبي",
      "points_needed": 1800
    }
  }
}
```

**Points rules:**
| Action | Points |
|--------|--------|
| Purchase | 1 point per 1 SAR spent (× tier multiplier) |
| Signup bonus | 200 points |
| Product review | 50 points |
| Referral (inviter) | 100 points |
| Referral (new user) | 100 points |

**Redemption:** 1 point = 0.10 SAR discount.

### Transaction History
```
GET /loyalty/transactions?per_page=20
Authorization: Bearer {token}
```
**Response (200):** Paginated list:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "type": "earned",
      "source": "purchase",
      "points": 500,
      "balance_after": 1500,
      "description_ar": "نقاط من الطلب #ORD-ABC123",
      "description_en": "Points from order #ORD-ABC123",
      "created_at": "2026-07-25T10:00:00+00:00"
    }
  ]
}
```

### Loyalty Tiers
```
GET /loyalty/tiers
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "tiers": [
    {
      "id": 1,
      "name": "برونزي",
      "slug": "bronze",
      "min_points": 0,
      "max_points": 999,
      "points_multiplier": 1.00,
      "discount_percent": 0,
      "free_shipping": false,
      "priority_support": false,
      "badge": null
    },
    {
      "id": 2,
      "name": "فضي",
      "slug": "silver",
      "min_points": 1000,
      "max_points": 4999,
      "points_multiplier": 1.25,
      "discount_percent": 5.00,
      "free_shipping": false,
      "priority_support": false,
      "badge": null
    }
  ]
}
```

### Estimate Points Redemption
```
POST /loyalty/estimate
Authorization: Bearer {token}
Content-Type: application/json

{
  "points": 500
}
```
**Response (200):**
```json
{
  "valid": true,
  "points": 500,
  "discount_value": 50.00
}
```
**Error (422):** `{ "valid": false, "message": "Insufficient points balance." }`

### Referral Code
```
GET /loyalty/referral-code
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "referral_code": "john_482",
  "share_url": "http://localhost:8000/register?ref=john_482",
  "total_referred": 3,
  "total_earned": 30.00,
  "share_links": {
    "whatsapp": "https://wa.me/?text=...",
    "facebook": "https://www.facebook.com/sharer/...",
    "twitter": "https://twitter.com/intent/tweet?..."
  }
}
```

### Referral History
```
GET /loyalty/referral-history
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "redemptions": [
    {
      "id": 1,
      "referred_user": "Ahmed Ali",
      "referred_email": "ahmed@example.com",
      "status": "completed",
      "reward_amount": 10.00,
      "completed_at": "2026-07-24T15:00:00+00:00",
      "created_at": "2026-07-01T10:00:00+00:00"
    }
  ]
}
```

### Register Referral
```
POST /loyalty/referral/register
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "john_482"
}
```
**Response (200):** `{ "message": "Referral code registered successfully. You'll earn a reward on your first order." }`
**Error (422):** `{ "message": "Invalid or expired referral code." }`

---

## 9. Gift Cards 🎁

All gift card endpoints require `Authorization: Bearer {token}`.

### Purchase a Gift Card
```
POST /gift-cards/purchase
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 100,
  "recipient_email": "friend@example.com",
  "recipient_name": "Ahmed",
  "message": "Happy birthday!",
  "expires_at": "2027-07-25T00:00:00+00:00"
}
```

**Request fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | float | ✅ | Gift card value (min: 10, max: 10000) |
| `recipient_email` | string | ❌ | Send gift card via email to this address |
| `recipient_name` | string | ❌ | Recipient's name (for personalized email) |
| `message` | string | ❌ | Personal message (max 500 chars) |
| `expires_at` | date | ❌ | Expiry date (default: 1 year from purchase) |

**Response (201):**
```json
{
  "message": "Gift card created successfully.",
  "gift_card": {
    "id": 1,
    "code": "GIFT-A1B2C3D4",
    "original_balance": 100.00,
    "recipient_email": "friend@example.com",
    "expires_at": "2027-07-25T00:00:00+00:00"
  }
}
```
> If `recipient_email` is provided, an email will be sent to the recipient with the gift card code and a personalized message.

### My Purchased Gift Cards
```
GET /gift-cards/purchased
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "gift_cards": [
    {
      "id": 1,
      "code": "GIFT-A1B2C3D4",
      "original_balance": 100.00,
      "current_balance": 100.00,
      "recipient_email": "friend@example.com",
      "recipient_name": "Ahmed",
      "sent_at": "2026-07-25T10:00:00+00:00",
      "expires_at": "2027-07-25T00:00:00+00:00",
      "is_active": true,
      "created_at": "2026-07-25T10:00:00+00:00"
    }
  ]
}
```

### Validate Gift Card
```
POST /gift-cards/validate
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "GIFT-A1B2C3D4"
}
```
**Response (200):**
```json
{
  "valid": true,
  "gift_card": {
    "id": 1,
    "code": "GIFT-A1B2C3D4",
    "current_balance": 100.00,
    "original_balance": 100.00
  }
}
```
**Error (422):** `{ "valid": false, "message": "Invalid or expired gift card." }`

### Check Balance
```
POST /gift-cards/balance
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "GIFT-A1B2C3D4"
}
```
**Response (200):**
```json
{
  "code": "GIFT-A1B2C3D4",
  "current_balance": 75.00,
  "original_balance": 100.00,
  "expires_at": "2027-07-25T00:00:00+00:00"
}
```

---

## 10. Business Flows

Complete interaction flows between Frontend and Backend for every major user journey.

---

### 6.1 Authentication Flow (OTP)

```
┌──────────────┐          ┌──────────────┐
│   Frontend   │          │   Backend    │
└──────┬───────┘          └──────┬───────┘
       │                         │
       │  POST /auth/send-otp    │
       │  { "identifier": "email"│
       ├────────────────────────>│  Generate & store OTP
       │                         │  Send OTP via email
       │  { "message": "OTP sent"│
       │<────────────────────────┤
       │                         │
       │  (User checks email)    │
       │                         │
       │  POST /auth/verify-otp  │
       │  { identifier, otp }    │
       ├────────────────────────>│
       │                         ├── User exists? ──→ status: login + token
       │                         └── New user? ────→ status: register_required
       │  { status, token/temp } │                  + temp_token
       │<────────────────────────┤
       │                         │
       │  [if new user]          │
       │  POST /auth/complete-registration
       │  { temp_token,          │
       │    first_name,          │
       │    last_name }          │
       ├────────────────────────>│  Create user + generate token
       │  { status: registered,  │
       │    token, user,         │
       │    is_new: true }       │
       │<────────────────────────┤
```

**Frontend logic:**
```javascript
// Step 1: Send OTP
await fetch('/auth/send-otp', {
  method: 'POST',
  body: JSON.stringify({ identifier: 'user@example.com' })
});

// Step 2: Verify OTP
const result = await fetch('/auth/verify-otp', {
  method: 'POST',
  body: JSON.stringify({ identifier: 'user@example.com', otp: '4821' })
}).then(r => r.json());

if (result.status === 'login') {
  localStorage.setItem('token', result.token);
  // redirect to homepage
} else if (result.status === 'register_required') {
  // show first_name + last_name form
  // then call completeRegistration
  const regResult = await fetch('/auth/complete-registration', {
    method: 'POST',
    body: JSON.stringify({
      temp_token: result.temp_token,
      first_name: 'John',
      last_name: 'Doe'
    })
  }).then(r => r.json());
  localStorage.setItem('token', regResult.token);
}
```

---

### 6.2 Product Browsing + Variant Selection + Add to Cart

> **Price & Image in listings:** `ProductListItem` shows the **cheapest variant's price** and its **first image**. This matches how Amazon/Noon display products with variants.

```
┌──────────────┐                    ┌──────────────┐
│   Frontend   │                    │   Backend    │
└──────┬───────┘                    └──────┬───────┘
       │                                   │
       │  GET /products?category=1&sort=newest
       ├──────────────────────────────────>│  Return paginated ProductListItems
       │  { data: [ ProductListItem[] ] }  │  (cheapest variant price + image)
       │<──────────────────────────────────┤
       │                                   │
       │  User clicks product              │
       │                                   │
       │  GET /products/{slug}/p{id}       │
       ├──────────────────────────────────>│  Return Product with all variants
       │  { product: { variants,           │  (sorted by price ASC — cheapest first)
       │      attributes, images, ... } }  │
       │<──────────────────────────────────┤
       │                                   │
       │  Frontend uses variants[0]        │
       │  as default display (cheapest):   │
       │  - price ← variants[0].sale_price │
       │  - images ← variants[0].images    │
       │                                   │
       │  Frontend builds variant map:     │
       │  variantMap = {                   │
       │    "101-201": { id: 10, price,    │
       │                 stock, images },  │
       │    "102-201": { id: 11, ... },    │
       │  }                                │
       │                                   │
       │  User selects attribute values    │
       │  (e.g. اللون: أزرق = 101,        │
       │   سعة التخزين: 256GB = 201)       │
       │                                   │
       │  Frontend looks up variant:       │
       │  key = "101-201"                  │
       │  variant = variantMap[key]        │
       │                                   │
       │  Update UI:                       │
       │  - price ← variant.sale_price     │
       │  - images ← variant.images        │
       │  - stock ← variant.stock_quantity │
       │                                   │
       │  User clicks "أضف للسلة"          │
       │                                   │
       │  POST /cart                       │
       │  { product_id: 5,                 │
       │    variant_id: 10,                │
       │    quantity: 1 }                  │
       ├──────────────────────────────────>│  Add item to cart
       │  { cart: { ... } }               │
       │<──────────────────────────────────┤
```

**Frontend variant selection logic:**
```javascript
// On page load — build variant lookup map
const variants = product.variants; // from API
const variantMap = {};

variants.forEach(v => {
  const valueIds = v.attribute_values
    .map(av => av.id)
    .sort((a, b) => a - b)
    .join('-');
  variantMap[valueIds] = v;
});

// On attribute value selection
let selectedValueIds = []; // e.g. [101, 201]
const key = selectedValueIds.sort().join('-');
const currentVariant = variantMap[key];

if (currentVariant) {
  displayPrice(currentVariant.sale_price || currentVariant.regular_price);
  displayImages(currentVariant.images);
  enableAddToCart(currentVariant.stock_quantity > 0);
} else {
  showMessage('هذه التركيبة غير متوفرة');
  disableAddToCart();
}
```

---

### 6.3 Cart → Coupon → Order

```
┌──────────────┐                    ┌──────────────┐
│   Frontend   │                    │   Backend    │
└──────┬───────┘                    └──────┬───────┘
       │                                   │
       │  GET /cart                        │
       ├──────────────────────────────────>│
       │  { cart: { items, subtotal } }    │
       │<──────────────────────────────────┤
       │                                   │
        │  User enters coupon code          │
        │                                   │
        │  POST /cart/apply-coupon          │
        │  { code: "SAVE10" }               │
        ├──────────────────────────────────>│  Validate coupon from DB
        │  { valid: true,                   │  Store on cart (forceFill)
        │    discount_amount: 50,           │
        │    cart: { ... } }                │
        │<──────────────────────────────────┤
        │                                   │
        │  Frontend reads discount from     │
        │  cart.coupon_discount:            │
        │  Subtotal: 500                    │
        │  Discount: -50                    │
        │  Total: 450                       │
        │                                   │
        │  (Optional) User removes coupon   │
        │  DELETE /cart/coupon              │
        │                                   │
        │  User clicks "إتمام الطلب"        │
       │                                   │
        │  POST /orders                     │
        │  { shipping_address_id: 1,        │
        │    notes: "اتركه عند الباب" }      │
       ├──────────────────────────────────>│  Validate coupon again
       │                                   │  Apply discount
       │                                   │  Decrease stock
       │                                   │  Clear cart
       │                                   │  Record coupon_usage
       │  { message: "Order placed.",      │
       │    order: { ... } }               │
       │<──────────────────────────────────┤
       │                                   │
       │  Redirect to order tracking       │
       │  GET /orders/{id}/tracking        │
       ├──────────────────────────────────>│
       │  { current_status, timeline }     │
       │<──────────────────────────────────┤
```

**Frontend checkout logic:**
```javascript
// Apply coupon to cart (stores on server)
const couponResult = await fetch('/cart/apply-coupon', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ code: 'SAVE10' })
}).then(r => r.json());

if (couponResult.valid) {
  setDiscount(couponResult.discount_amount);
  // coupon is now stored on the cart server-side
}

// Place order — coupon is read from cart automatically
const orderResult = await fetch('/orders', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    shipping_address_id: 1,
    notes: 'اتركه عند الباب'
  })
}).then(r => r.json());

// Navigate to tracking
window.location.href = `/order/${orderResult.order.id}`;
```

---

### 6.4 Complete Checkout Flow (Full Sequence)

```
1. GET  /auth/send-otp               → Login/Register
2. POST /auth/verify-otp             →
3. POST /auth/complete-registration  →
                                       │
4. GET  /home                        → Browse homepage
5. GET  /products                    → Browse/search products
6. GET  /products/{slug}/p{id}       → View product detail
                                       │
7. POST /cart                        → Add to cart (with variant_id)
8. GET  /cart                        → View cart
9. POST /coupons/validate            → Apply coupon
                                       │
10. GET  /addresses                  → Select shipping address
11. POST /addresses                  → Add new address if needed
                                       │
12. POST /orders                     → Place order (coupon from cart, if any)
13. GET  /orders/{id}                → View order detail
14. GET  /orders/{id}/tracking       → Track order status
                                       │
15. GET  /orders/{id}/returns        → View returns
16. POST /orders/{id}/returns        → Submit return
```

---

### 6.5 Guest → Authenticated Cart Merge

```
┌──────────────┐                    ┌──────────────┐
│   Frontend   │                    │   Backend    │
└──────┬───────┘                    └──────┬───────┘
       │                                   │
       │  (Guest browses, adds to cart)    │
       │  POST /guest/cart                 │
       │  { items: [...],                  │
       │    guest_token: "uuid" }          │
       ├──────────────────────────────────>│
       │                                   │
       │  (User decides to login)          │
       │                                   │
       │  POST /auth/verify-otp           │
       │  → Login successful               │
       │                                   │
       │  POST /auth/merge-cart            │
       │  { guest_token: "uuid" }          │
       ├──────────────────────────────────>│  Move guest items
       │  { cart: merged cart }            │  to user's cart
       │<──────────────────────────────────┤
```

**Frontend merge logic:**
```javascript
const guestToken = localStorage.getItem('guest_token');

if (guestToken) {
  await fetch('/auth/merge-cart', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ guest_token: guestToken })
  });
  localStorage.removeItem('guest_token');
}
```

---

### 6.6 Returns Flow

```
┌──────────────┐                    ┌──────────────┐
│   Frontend   │                    │   Backend    │
└──────┬───────┘                    └──────┬───────┘
       │                                   │
       │  GET /return-policy               │
       ├──────────────────────────────────>│
       │  { policy_text_ar,                │
       │    days_allowed,                  │
       │    conditions }                   │
       │<──────────────────────────────────┤
       │                                   │
       │  GET /orders/{id}                 │
       ├──────────────────────────────────>│
       │  { order with items }             │
       │<──────────────────────────────────┤
       │                                   │
       │  User selects items to return     │
       │                                   │
       │  POST /orders/{id}/returns        │
       │  { items: [                       │
       │      { order_item_id: 1,          │
       │        quantity: 1,               │
       │        reason: "معطل" },          │
       │    ],                             │
       │    return_type: "refund",         │
       │    notes: "المنتج لا يعمل" }      │
       ├──────────────────────────────────>│  Only for delivered orders
       │  { message: "Return submitted.",  │
       │    return: { ... } }              │
       │<──────────────────────────────────┤
       │                                   │
       │  GET /orders/{id}/returns         │
       ├──────────────────────────────────>│  Track return status
       │  { returns: [...] }               │
       │<──────────────────────────────────┤
```

---

### 6.7 Coupon Usage Scenarios

This flow builds on the Cart → Coupon → Order flow (6.3) and details all supported coupon types.

```
┌──────────────┐                         ┌──────────────┐
│   Frontend   │                         │   Backend    │
└──────┬───────┘                         └──────┬───────┘
       │                                        │
       │  User enters "FREESHIP"                │
       │                                        │
       │  POST /coupons/validate                │
       │  {                                     │
       │    code: "FREESHIP",                   │
       │    subtotal: 300,                      │
       │    cart_items: [                       │
       │      { product_id: 1, price: 100,      │
       │        quantity: 3 }                   │
       │    ]                                   │
       │  }                                     │
       ├───────────────────────────────────────>│
       │                                        │
       │  Backend checks:                       │
       │  ✅ is_active                          │
       │  ✅ end_date not passed                │
       │  ✅ usage_limit not reached            │
       │  ✅ user_id matches (if set)           │
       │  ✅ per_user_limit (if set)            │
       │  ✅ min_orders_count (if set)          │
       │  ✅ subtotal >= minimum_order_amount   │
       │  ✅ cart items match categories/       │
       │     products (if applicable_to != all) │
       │                                        │
       │  { valid: true,                        │
       │    discount_amount: 0,                 │
       │    is_free_shipping: true,             │
       │    coupon: {                           │
       │      code: "FREESHIP",                 │
       │      is_free_shipping: true } }        │
       │<───────────────────────────────────────┤
       │                                        │
       │  Frontend displays:                    │
       │    Subtotal: 300                       │
       │    Discount: -0                        │
       │    Free Shipping: ✅ (saves 25 SAR)   │
       │    Total: 300                          │
```

**Validation — applicable_to categories/products example:**
```javascript
// Coupon "ELECTRO10" → 10% off Electronics only
// Cart items: [iPhone (electronics), T-Shirt (clothing)]
// Only iPhone gets the discount

await fetch('/coupons/validate', {
  method: 'POST',
  body: JSON.stringify({
    code: 'ELECTRO10',
    subtotal: 800,
    cart_items: [
      { product_id: 1, price: 500, quantity: 1 },  // iPhone
      { product_id: 2, price: 300, quantity: 1 }, // T-Shirt
    ]
  })
});
// → discount_amount: 50 (10% of 500, not 10% of 800)
```

**Frontend — Available coupon types:**

| Coupon Type | Backend Setup | Frontend Display |
|------------|--------------|------------------|
| Percentage off all | `discount_type: percentage` | "10% off" |
| Fixed amount | `discount_type: fixed, value: 50` | "50 SAR off" |
| Category-specific | `applicable_to: categories` | "10% off Electronics" |
| Product-specific | `applicable_to: products` | "50 SAR off iPhone" |
| Free shipping | `is_free_shipping: true` | "Free Shipping" badge |
| User-specific | `user_id: 5` | Auto-applied (no code entry) |
| Repeat purchase | `min_orders_count: 3` | "For loyal customers" |

---

### 6.8 Address with Map (OpenStreetMap + Leaflet)

```
┌──────────────┐                         ┌──────────────┐
│   Frontend   │                         │   Backend    │
└──────┬───────┘                         └──────┬───────┘
       │                                        │
       │  User clicks "إضافة عنوان"             │
       │                                        │
       │  1. Browser requests location          │
       │     navigator.geolocation              │
       │     .getCurrentPosition()              │
       │                                        │
       │  2. Display Leaflet map                │
       │     centered on user's location        │
       │     (or Riyadh if denied)              │
       │                                        │
       │  3. User drags pin to exact spot       │
       │                                        │
       │  4. Reverse geocode (Nominatim):       │
       │     fetch("https://nominatim.          │
       │       openstreetmap.org/reverse?       │
       │       lat={lat}&lon={lng}&             │
       │       format=json&                     │
       │       accept-language=ar")             │
       │     → { display_name,                  │
       │         address: { city, road, ... } } │
       │                                        │
       │  5. Fill street_address + city         │
       │     automatically from Nominatim       │
       │                                        │
       │  6. User fills remaining fields:           │
       │     - address_type (home/work/other)   │
       │     - building_number                   │
       │     - floor_number                     │
       │     - apartment_number                 │
       │     - additional_directions            │
       │                                        │
       │  POST /addresses                       │
       │  {                                     │
       │    address_type: "home",               │
       │    street_address: "..."               │
       │    city: "الرياض",                      │
       │    country: "المملكة العربية السعودية", │
       │    latitude: 24.7136,                  │
       │    longitude: 46.6753,                 │
       │    building_number: "12",            │
       │    floor_number: "3",                  │
       │    apartment_number: "302",            │
       │    additional_directions: "بجانب       │
       │                            مسجد",      │
       │    is_default: true                    │
       │  }                                     │
       ├───────────────────────────────────────>│  Store address
       │  { message, address }                  │
       │<───────────────────────────────────────┤
```

**Frontend — Reverse Geocoding via Nominatim:**
```javascript
async function reverseGeocode(lat, lng) {
  const res = await fetch(
    `https://nominatim.openstreetmap.org/reverse?` +
    `lat=${lat}&lon=${lng}&format=json&accept-language=ar`
  );
  const data = await res.json();

  return {
    street_address: data.display_name || '',
    city: data.address?.city
       || data.address?.town
       || data.address?.village
       || data.address?.county
       || '',
  };
}

// After user places pin on map:
const { street_address, city } = await reverseGeocode(pinLat, pinLng);
document.getElementById('street_address').value = street_address;
document.getElementById('city').value = city;
```

**Frontend — Leaflet Map Setup:**
```javascript
// Initialize map
const map = L.map('map').setView([24.7136, 46.6753], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(map);

// Draggable pin
const marker = L.marker([24.7136, 46.6753], { draggable: true }).addTo(map);

marker.on('dragend', async (e) => {
  const { lat, lng } = e.target.getLatLng();
  document.getElementById('latitude').value = lat;
  document.getElementById('longitude').value = lng;

  const { street_address, city } = await reverseGeocode(lat, lng);
  document.getElementById('street_address').value = street_address;
  document.getElementById('city').value = city;
});

// Get user's current location
if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition((pos) => {
    const { latitude, longitude } = pos.coords;
    map.setView([latitude, longitude], 15);
    marker.setLatLng([latitude, longitude]);
    document.getElementById('latitude').value = latitude;
    document.getElementById('longitude').value = longitude;
    // Optionally auto-fill street_address + city here
  });
}
```

> **Important:** `latitude` and `longitude` must be sent together (if one is provided, both are required).

---

### 6.9 Wishlist + Recently Viewed Flow

```
// Wishlist
GET  /wishlist              → List all wishlisted products
POST /wishlist              → Add { product_id, variant_id? }
DELETE /wishlist/{id}       → Remove from wishlist

// Recently Viewed
POST /recently-viewed       → Log { product_id, variant_id? }
                            → (Called every time user views a product detail)

// Flow: Product Detail → Log View → Show in "Recently Viewed"
1. User opens product page
2. Frontend calls POST /recently-viewed (background, no UI change)
3. On homepage or sidebar, fetch GET /products?sort=newest
   (Recently viewed is tracked per user, not exposed via separate endpoint)
```

---

### 6.10 Compare Flow

```
┌──────────────┐                    ┌──────────────┐
│   Frontend   │                    │   Backend    │
└──────┬───────┘                    └──────┬───────┘
       │                                   │
       │  User selects 2-4 products        │
       │  to compare                       │
       │                                   │
       │  POST /compare                    │
       │  { product_ids: [1, 2, 3] }      │
       ├──────────────────────────────────>│
       │  { products: [ Product[] ] }      │
       │<──────────────────────────────────┤
       │                                   │
       │  Frontend renders comparison      │
       │  table with all attributes        │
       │  side by side                     │
```

**Frontend comparison logic:**
```javascript
const productIds = selectedProducts.map(p => p.id); // max 4

const result = await fetch('/compare', {
  method: 'POST',
  body: JSON.stringify({ product_ids: productIds })
}).then(r => r.json());

// Display comparison table
const table = result.products.map(product => ({
  name: product.name,
  price: product.sale_price || product.regular_price,
  attributes: product.attributes.map(a => ({
    name: a.name,
    values: getAttributeValues(product, a.id) // from product.variants
  })),
  rating: product.reviews_avg_rating,
  availability: product.stock_status
}));
```

---

### 6.11 Notifications Flow

```
┌──────────────┐                    ┌──────────────┐
│   Frontend   │                    │   Backend    │
└──────┬───────┘                    └──────┬───────┘
       │                                   │
       │  GET /notifications               │
       ├──────────────────────────────────>│
       │  { data: [...],                   │
       │    unread_count: 3 }              │
       │<──────────────────────────────────┤
       │                                   │
       │  (User opens notification)        │
       │  PUT /notifications/{id}/read     │
       ├──────────────────────────────────>│
       │  { message: "Marked as read." }   │
       │<──────────────────────────────────┤
       │                                   │
       │  (User marks all as read)         │
       │  PUT /notifications/read-all      │
       ├──────────────────────────────────>│
       │  { message: "All marked as read."}│
       │<──────────────────────────────────┤
```

**Frontend polling/push logic:**
```javascript
// Option A: Poll every 30 seconds
setInterval(async () => {
  const res = await fetch('/notifications?per_page=1', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  badgeCount = res.unread_count;
  updateBadge(badgeCount);
}, 30000);

// Option B: Laravel Echo + WebSockets (future)
// Echo.private('notifications.user.1')
//   .listen('NotificationCreated', (e) => {
//     addNotification(e.notification);
//   });
```

---

### 6.12 Product Slug Validation

When using the `{slug}/p{product}` URL pattern, the backend validates both the slug and the product ID. This prevents:

```
❌ /products/wrong-slug/p123     → 404 (slug doesn't match)
✅ /products/correct-slug/p123   → 200 (slug matches)
❌ /products/correct-slug/p999   → 404 (product not found)
```

**Frontend URL building:**
```javascript
const productUrl = `/${product.slug}/p${product.id}`;
// → "/baseball-cap/p3"

// API call
const res = await fetch(`/products/${product.slug}/p${product.id}`);
```
