# Admin Panel API Documentation

## Base URL
```
http://localhost:8000/api/admin
```

## Authentication

### Headers
All protected endpoints require:
```
Authorization: Bearer {token}
Accept: application/json
```

### Rate Limiting
- `POST /auth/login`: 5 requests per minute
- All other endpoints: no global rate limit

---

## 1. Authentication (AuthController)

### POST /auth/login — تسجيل الدخول
**Access:** Public (no token required)

**Validation:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `email` | string | conditional | `required_without:username`, `email` |
| `username` | string | conditional | `required_without:email`, `string` |
| `password` | string | yes | `required`, `string` |

**Response (200):**
```json
{
  "token": "1|abc123def456...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@admin.com",
    "first_name": "Admin",
    "last_name": "User",
    "phone": "0555555555",
    "role": "admin",
    "locale": "ar",
    "is_active": true,
    "last_login": "2026-07-16T10:00:00.000000Z",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-07-16T10:00:00.000000Z"
  }
}
```

**Error (403):** `{ "message": "Account is deactivated.", "code": 403 }`

### POST /auth/logout — تسجيل الخروج
**Response (200):** `{ "message": "Logged out successfully." }`

### GET /auth/me — المستخدم الحالي
**Response (200):** `{ "user": { id, username, email, first_name, last_name, phone, role, locale, is_active, last_login, created_at, updated_at } }`

---

## 2. Dashboard (DashboardController)

### GET /dashboard/stats — إحصائيات لوحة التحكم

**Query Parameters:** لا شيء

**Response (200):**
```json
{
  "total_revenue": 15000.00,
  "today_revenue": 1200.00,
  "this_month_revenue": 45000.00,
  "last_month_revenue": 38000.00,
  "revenue_growth_percent": 18.42,
  "average_order_value": 312.50,

  "total_orders": 120,
  "today_orders": 8,
  "this_month_orders": 145,
  "pending_orders_count": 10,
  "orders_by_status": { "pending": 10, "confirmed": 5, "processing": 8, "shipped": 12, "delivered": 75, "cancelled": 10 },
  "orders_by_payment_status": { "pending": 15, "paid": 90, "failed": 5, "refunded": 10 },
  "recent_orders": [
    { "id": 1, "user": { "id": 1, "first_name": "...", "last_name": "..." }, "final_amount": 250.00, "order_status": "delivered", "created_at": "..." }
  ],

  "total_products": 45,
  "active_products": 40,
  "out_of_stock_products": 3,
  "featured_products_count": 12,
  "low_stock_products": [
    { "id": 1, "name_ar": "منتج", "name_en": "Product", "sku": "PRD-001", "quantity_in_stock": 2, "low_stock_threshold": 5 }
  ],
  "top_selling_products": [
    { "product_id": 1, "total_sold": 150, "revenue": 15000.00, "product": { "id": 1, "name_ar": "منتج 1", "name_en": "Product 1" } }
  ],
  "products_by_category": [
    { "category_id": 1, "category_name_ar": "إلكترونيات", "category_name_en": "Electronics", "count": 20 }
  ],

  "total_customers": 80,
  "new_customers_this_month": 12,
  "customers_by_role": { "customer": 70, "vendor": 8, "admin": 2 },
  "top_customers": [
    { "id": 5, "username": "ahmed", "first_name": "Ahmed", "last_name": "Ali", "total_orders": 15, "total_spent": 8500.00 }
  ],

  "total_tax_collected": 2250.00,
  "total_shipping_revenue": 1800.00,
  "total_discounts_given": 3200.00,
  "payment_method_distribution": [
    { "payment_method": "Visa", "count": 60, "total": 45000.00 }
  ],

  "pending_reviews_count": 7,
  "active_coupons_count": 5,
  "price_alerts_active": 23,
  "stock_alerts_active": 15,
  "today_visitors": 45,
  "total_wishlist_items": 89
}
```

**Field Groups:**

| Group | Fields |
|-------|--------|
| Revenue | `total_revenue`, `today_revenue`, `this_month_revenue`, `last_month_revenue`, `revenue_growth_percent`, `average_order_value` |
| Orders | `total_orders`, `today_orders`, `this_month_orders`, `pending_orders_count`, `orders_by_status` (object), `orders_by_payment_status` (object), `recent_orders` (array) |
| Products | `total_products`, `active_products`, `out_of_stock_products`, `featured_products_count`, `low_stock_products` (array), `top_selling_products` (array), `products_by_category` (array) |
| Customers | `total_customers`, `new_customers_this_month`, `customers_by_role` (object), `top_customers` (array) |
| Financial | `total_tax_collected`, `total_shipping_revenue`, `total_discounts_given`, `payment_method_distribution` (array) |
| Miscellaneous | `pending_reviews_count`, `active_coupons_count`, `price_alerts_active`, `stock_alerts_active`, `today_visitors`, `total_wishlist_items` |

---

## 3. Products (EAV System)

### Products — `products` table (ProductController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/products` | List (paginated, filterable) |
| POST | `/products` | Create |
| GET | `/products/{product}` | Show with variants, images, categories, tags |
| PUT | `/products/{product}` | Update |
| DELETE | `/products/{product}` | Delete |

#### GET /products — قائمة المنتجات

**Query Parameters (Filters):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | — | بحث في `name_ar`, `name_en`, `sku` |
| `category_id` | integer | — | تصفية حسب التصنيف |
| `is_active` | boolean | — | المنتجات النشطة فقط |
| `is_featured` | boolean | — | المنتجات المميزة فقط |
| `low_stock` | boolean | — | المنتجات تحت حد المخزون |
| `sort` | string | `created_at` | حقل الترتيب |
| `order` | string | `desc` | `asc` أو `desc` |
| `per_page` | integer | `15` | عدد العناصر لكل صفحة |

#### POST /products — إنشاء منتج

**Validation & Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name_ar` | string | yes | `max:500` |
| `name_en` | string | yes | `max:500` |
| `slug` | string | no | `max:255`, `unique:products,slug` |
| `description_ar` | string | no | — |
| `description_en` | string | no | — |
| `sku` | string | yes | `max:100`, `unique:products,sku` |
| `regular_price` | numeric | yes | `min:0` |
| `sale_price` | numeric | no | `min:0`, `lte:regular_price` |
| `cost_price` | numeric | no | `min:0` |
| `tax_rate_id` | integer | no | `exists:tax_rates,id` |
| `quantity_in_stock` | integer | no | `min:0` |
| `low_stock_threshold` | integer | no | `min:0` |
| `weight` | numeric | no | `min:0` |
| `dimensions` | string | no | `max:100` |
| `main_image` | string | no | `max:255` |
| `is_active` | boolean | no | — |
| `is_featured` | boolean | no | — |
| `is_returnable` | boolean | no | هل المنتج قابل للإرجاع (استرداد مبلغ) |
| `is_exchangeable` | boolean | no | هل المنتج قابل للاستبدال |
| `return_period_days` | integer | no | `min:0`, مدة الإرجاع بالأيام (default: 14) |
| `is_cancellable` | boolean | no | هل المنتج قابل للإلغاء قبل الشحن |
| `max_per_order` | integer | no | `min:1` |
| `price_includes_tax` | boolean | no | — |
| `meta_title` | string | no | `max:255` |
| `meta_description` | string | no | `max:500` |
| `categories[]` | array | no | `exists:categories,id` |
| `tags[]` | array | no | `exists:tags,id` |
| `attributes[]` | array | no | `exists:attributes,id` |

**Response (201):** `{ "message": "Product created successfully.", "product": AdminProductResource }`

#### GET /products/{product} — عرض منتج

**Response (200):** `{ "product": AdminProductResource }`

محمل بالعلاقات: `categories`, `tags`, `attributes` (مع `pivot.is_variation`, `pivot.display_order`), `variants` (مع `attributeValues.attribute` و `images`), `images` (product-level فقط)

#### PUT /products/{product} — تحديث منتج

**Validation:** نفس `store` لكن مع `sometimes` بدلاً من `required` و `unique:products,slug,{id}` للـ slug و `unique:products,sku,{id}` للـ sku.

**Response:** `{ "message": "Product updated successfully.", "product": AdminProductResource }`

#### DELETE /products/{product} — حذف منتج

**Response:** `{ "message": "Product deleted successfully." }`

---

### Product Variants — `product_variants` table (VariantController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/products/{product}/variants` | List variants |
| POST | `/products/{product}/variants` | Create variant |
| PUT | `/products/{product}/variants/{variant}` | Update variant |
| DELETE | `/products/{product}/variants/{variant}` | Delete variant |

**Validation & Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `sku` | string | no | `max:100`, `unique:product_variants,sku` |
| `regular_price` | numeric | yes | `min:0` |
| `sale_price` | numeric | no | `min:0`, `lte:regular_price` |
| `cost_price` | numeric | no | `min:0` |
| `stock_quantity` | integer | no | `min:0` |
| `barcode` | string | no | `max:100` |
| `is_active` | boolean | no | — |
| `attribute_values[]` | array | no | `exists:attribute_values,id` |

**Response (201) store:** `{ "message": "Variant created successfully.", "variant": AdminVariantResource }`

AdminVariantResource: `{ id, product_id, sku, regular_price, sale_price, cost_price, stock_quantity, barcode, is_active, attribute_values (مع العلاقات), images, created_at, updated_at }`

---

### Product Images — `product_images` table (ImageController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/products/{product}/images` | List images (filter by `?variant_id=`) |
| POST | `/products/{product}/images` | Create image (multipart) |
| GET | `/products/{product}/images/{image}` | Show single image |
| PUT | `/products/{product}/images/{image}` | Update image (multipart) |
| DELETE | `/products/{product}/images/{image}` | Delete image |
| GET | `/products/{product}/variants/{variant}/images` | Variant-specific images |

**Query Parameters (index):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `variant_id` | integer | تصفية حسب المتغير (بدونها تعيد كل الصور) |

**Body (multipart/form-data):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `image` | file | conditional | `required_without:image_url`, `mimes:jpeg,png,jpg,gif,webp`, `max:5120` |
| `image_url` | string | conditional | `required_without:image`, `max:255` |
| `variant_id` | integer | no | `exists:product_variants,id` |
| `alt_text` | string | no | `max:255` |
| `display_order` | integer | no | `min:0` |
| `is_main` | boolean | no | — |

**Response format:**
```json
{
  "id": 1,
  "product_id": 5,
  "variant_id": null,
  "image_url": "http://localhost/storage/products/abc123.jpg",
  "alt_text": "...",
  "display_order": 1,
  "is_main": true
}
```

**ملاحظات:**
- `variant_id: null` → product-level image
- `variant_id: 12` → variant image
- Product `GET /products/{id}` returns only product-level images (`variant_id IS NULL`)
- Variants in `GET /products/{id}` include their own `images` array
- File upload stored in `storage/app/public/products/` or `storage/app/public/variants/`

---

### Categories — `categories` table (CategoryController) — Hierarchical

| Method | Path | Description |
|--------|------|-------------|
| GET | `/categories` | List (tree or flat via `?flat=true`) |
| POST | `/categories` | Create |
| GET | `/categories/{category}` | Show with subcategories |
| PUT | `/categories/{category}` | Update |
| DELETE | `/categories/{category}` | Delete (only if no children) |

#### GET /categories — قائمة التصنيفات

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `flat` | boolean | إذا وُجد: يعيد كل الفئات بدون هيكل هرمي. إذا لم يوجد: يعيد فقط الفئات الرئيسية (`parent_id IS NULL`) مع `children` |

**Response (200):** `{ "categories": [{ id, name_ar, name_en, slug, description_ar, description_en, parent_id, image_url, meta_title, meta_description, display_order, is_active, products_count, children: [...] }] }`

#### POST /categories — إنشاء تصنيف

**Body (يمكن إرسال `image` كـ multipart/form-data أو كـ JSON null):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name_ar` | string | yes | `max:100` |
| `name_en` | string | yes | `max:100` |
| `description_ar` | string | no | — |
| `description_en` | string | no | — |
| `parent_id` | integer | no | `exists:categories,id` |
| `slug` | string | no | `max:100`, `unique:categories,slug` |
| `image` | file/string | no | ملف jpeg/png/jpg/gif/webp (max 5MB) أو رابط URL |
| `meta_title` | string | no | `max:255` |
| `meta_description` | string | no | — |
| `display_order` | integer | no | `min:0` |
| `is_active` | boolean | no | — |

**ملاحظات:**
- Image stored in `storage/app/public/categories/`
- Response returns full URL via `asset('storage/' . $path)`
- Old image file automatically deleted when replaced
- To remove image without replacing: send `"image": null` or empty in multipart

#### DELETE /categories/{category}
- لا يمكن حذف فئة لها فئات فرعية
- **Error (409):** `{ "message": "Cannot delete category with subcategories." }`

---

### Tags — `tags` table (TagController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/tags` | List with product count |
| POST | `/tags` | Create |
| PUT | `/tags/{tag}` | Update |
| DELETE | `/tags/{tag}` | Delete |

**Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name_ar` | string | yes | `max:100` |
| `name_en` | string | yes | `max:100` |
| `slug` | string | no | `max:100`, `unique:tags,slug` |

**Response (200) index:** `{ "tags": [{ id, name_ar, name_en, slug, products_count }] }`

---

### Attributes — `attributes` table (AttributeController) — EAV

| Method | Path | Description |
|--------|------|-------------|
| GET | `/attributes` | List with values |
| POST | `/attributes` | Create |
| GET | `/attributes/{attribute}` | Show with values |
| PUT | `/attributes/{attribute}` | Update |
| DELETE | `/attributes/{attribute}` | Delete |
| POST | `/attributes/{attribute}/values` | Create value |
| PUT | `/attributes/{attribute}/values/{value}` | Update value |
| DELETE | `/attributes/{attribute}/values/{value}` | Delete value |

#### POST /attributes — إنشاء خاصية

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name_ar` | string | yes | `max:100` |
| `name_en` | string | yes | `max:100` |
| `attribute_type` | string | no | `in:select,color,size,text` |
| `display_order` | integer | no | `min:0` |
| `is_global` | boolean | no | — |

#### POST /attributes/{attribute}/values — إنشاء قيمة خاصية

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `value_ar` | string | yes | `max:100` |
| `value_en` | string | yes | `max:100` |
| `extra_data` | JSON | no | — |
| `display_order` | integer | no | `min:0` |

**Response index:** `{ "attributes": [{ id, name_ar, name_en, attribute_type, display_order, is_global, values: [{ id, attribute_id, value_ar, value_en, extra_data, display_order }] }] }`

---

## 4. Orders, Shipping & Payments

### Orders — `orders` table (OrderController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/orders` | List (paginated, filterable) |
| GET | `/orders/{order}` | Show full (items, payments, shipping, addresses) |
| PATCH | `/orders/{order}/status` | Update status (auto-sets timestamps) |
| DELETE | `/orders/{order}` | Delete |

#### GET /orders — قائمة الطلبات

**Query Parameters (Filters):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `order_status` | string | — | `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled` |
| `payment_status` | string | — | `pending`, `paid`, `failed`, `refunded` |
| `search` | string | — | بحث في `order_number` |
| `date_from` | date | — | تاريخ البداية |
| `date_to` | date | — | تاريخ النهاية |
| `sort` | string | `created_at` | — |
| `order` | string | `desc` | `asc` أو `desc` |
| `per_page` | integer | `15` | — |

**Response:** Paginated list with `user` (Eager loaded: `user:id,username,email,first_name,last_name`)

#### GET /orders/{order} — تفاصيل الطلب الكاملة

**Response (200) — عبر `OrderResource`:**

```json
{
  "order": {
    "id": 2,
    "order_number": "ORD-6A4EDA13911C3",
    "total_amount": 3990.00,
    "tax_amount": 0.00,
    "shipping_amount": 0.00,
    "discount_amount": 0.00,
    "coupon_code": null,
    "final_amount": 3990.00,
    "order_status": "pending",
    "payment_status": "pending",
    "notes": null,
    "currency": "SAR",
    "created_at": "2026-07-08T23:15:31.000000Z",
    "confirmed_at": null,
    "processing_at": null,
    "shipped_at": null,
    "delivered_at": null,
    "cancelled_at": null,
    "cancel_reason": null,

    "user": {
      "id": 5,
      "username": "mohammadsadekaljafri_5826",
      "email": "user@example.com",
      "first_name": "محمد",
      "last_name": "أحمد",
      "phone": "0533333333",
      "locale": "ar",
      "is_active": true
    },

    "shipping_address": {
      "id": 2,
      "label": null,
      "street_address": "رقم 29, الياسمين, بلدية الشمال, الرياض",
      "city": "Riyadh",
      "state": "الرياض",
      "country": "Saudi Arabia",
      "postal_code": null,
      "latitude": "24.82366050",
      "longitude": "46.63250400",
      "is_default": true
    },

    "billing_address": {
      "id": 2,
      "label": null,
      "street_address": "رقم 29, الياسمين, بلدية الشمال, الرياض",
      "city": "Riyadh",
      "state": "الرياض",
      "country": "Saudi Arabia",
      "postal_code": null,
      "latitude": "24.82366050",
      "longitude": "46.63250400",
      "is_default": true
    },

    "items": [
      {
        "id": 2,
        "quantity": 1,
        "unit_price": 3990.00,
        "subtotal": 3990.00,
        "tax_amount": 0.00,
        "total_price": 3990.00,
        "product_name": "آيفون 15 بروميوم",
        "sku_snapshot": null,

        "product": {
          "id": 3,
          "name_ar": "آيفون 15 بروميوم",
          "name_en": "iPhone 15 Premium",
          "slug": "iphone-15-premium",
          "sku": "PROD-001",
          "regular_price": 4500.00,
          "sale_price": 3990.00,
          "stock_status": "in_stock",
          "main_image": null,
          "images": [
            { "id": 7, "image_url": "http://localhost/storage/products/xxx.png", "is_main": false }
          ],
          "categories": [
            { "id": 11, "name": "الهواتف الذكية" }
          ]
        },

        "variant": {
          "id": 33,
          "sku": "IPH15-BLU-256",
          "regular_price": 4900.00,
          "sale_price": 0.00,
          "stock_quantity": 14,
          "is_active": true,
          "attribute_values": [
            {
              "id": 103,
              "value": "أزرق",
              "attribute": { "id": 1, "name": "اللون" }
            },
            {
              "id": 212,
              "value": "256 جيجا",
              "attribute": { "id": 7, "name": "سعة الذاكرة" }
            }
          ],
          "images": [
            { "id": 18, "image_url": "http://localhost/storage/variants/yyy.jpg", "is_main": false }
          ],
          "has_variant": true
        }
      }
    ],

    "shipping": {
      "id": 1,
      "carrier": "DHL",
      "tracking_number": "DHL123456789",
      "shipping_status": "shipped",
      "estimated_days": "3-5",
      "shipping_zone": { "id": 1, "name": "الرياض" }
    },

    "payments": [
      {
        "id": 1,
        "amount": 3990.00,
        "status": "paid",
        "method": { "id": 1, "name": "Visa" },
        "transaction_id": "TXN123456",
        "paid_at": "2026-07-09T10:00:00.000000Z"
      }
    ]
  }
}
```

**ملاحظات:**
- `shipping` قد يكون `null` إذا لم يتم تعيين شحن بعد
- `payments` قد يكون `[]` إذا لم تتم أي مدفوعات
- `variant.has_variant: false` للمنتجات البسيطة (بدون متغيرات)
- `product.images` تشمل صور المنتج (product-level)
- `variant.images` تشمل صور المتغير (variant-level)
- `variant.attribute_values[].attribute` يعيد اسم الخاصية (مثل "اللون", "سعة الذاكرة")

#### PATCH /orders/{order}/status — تحديث حالة الطلب

**Validation:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `order_status` | string | no | `in:pending,confirmed,processing,shipped,delivered,cancelled` |
| `payment_status` | string | no | `in:pending,paid,failed,refunded` |

**Status → Timestamp mapping:**

| Status | Timestamp Field |
|--------|-----------------|
| `confirmed` | `confirmed_at` |
| `processing` | `processing_at` |
| `shipped` | `shipped_at` |
| `delivered` | `delivered_at` |
| `cancelled` | `cancelled_at` |

> التواريخ تُدرج **مرة واحدة فقط** — إذا كان الحقل موجوداً مسبقاً لا يتم إعادة كتابته.

**Response (200):** `{ "message": "Order status updated.", "order": OrderResource }`

#### DELETE /orders/{order} — حذف الطلب
حذف مع `items`, `payments`, `shipping` في transaction.

**Response:** `{ "message": "Order deleted successfully." }`

---

### Order Shipping — `shipping` table (ShippingController) — Nested under orders

| Method | Path | Description |
|--------|------|-------------|
| GET | `/orders/{order}/shipping` | List shipping records |
| POST | `/orders/{order}/shipping` | Create shipping record |
| GET | `/orders/{order}/shipping/{shipping}` | Show |
| PUT | `/orders/{order}/shipping/{shipping}` | Update |
| DELETE | `/orders/{order}/shipping/{shipping}` | Delete |

**Body (store/update):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `order_id` | integer | yes | `exists:orders,id` |
| `shipping_method` | string | yes | `max:100` |
| `tracking_number` | string | no | `max:100` |
| `tracking_url` | string | no | `max:255` |
| `carrier` | string | no | `max:100` |
| `shipping_zone_id` | integer | no | `exists:shipping_zones,id` |
| `shipping_date` | date | no | — |
| `estimated_delivery` | date | no | — |
| `actual_delivery` | date | no | — |
| `shipping_status` | string | no | `in:pending,shipped,in_transit,out_for_delivery,delivered` |

**Response (index):** مع `order` و `shippingZone` (Eager loaded)

---

### Payments — `payments` table (PaymentController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/payments` | List |
| POST | `/payments` | Create |
| GET | `/payments/{payment}` | Show |
| PUT | `/payments/{payment}` | Update |
| DELETE | `/payments/{payment}` | Delete |

**Query Parameters (index):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `order_id` | integer | — |
| `payment_status` | string | `pending`, `completed`, `failed`, `refunded` |
| `payment_method` | string | — |
| `per_page` | integer | Default: `15` |

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `order_id` | integer | yes | `exists:orders,id` |
| `method_id` | integer | no | `exists:payment_methods,id` |
| `payment_method` | string | no | `max:100` |
| `transaction_id` | string | no | `max:255` |
| `amount` | numeric | yes | `min:0` |
| `payment_status` | string | no | `in:pending,completed,failed,refunded` |
| `payment_date` | date | no | — |
| `gateway_response` | JSON | no | — |

**Body (update):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `payment_status` | string | no | `in:pending,completed,failed,refunded` |
| `transaction_id` | string | no | `max:255` |
| `gateway_response` | JSON | no | — |

**Response:** مع `order` و `method` (Eager loaded)

---

### Payment Methods — `payment_methods` table (PaymentMethodController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/payment-methods` | List |
| POST | `/payment-methods` | Create |
| PUT | `/payment-methods/{paymentMethod}` | Update |
| DELETE | `/payment-methods/{paymentMethod}` | Delete |

**Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name_ar` | string | yes | `max:50` |
| `name_en` | string | yes | `max:50` |
| `is_active` | boolean | no | — |
| `additional_fee` | numeric | no | `min:0` |

**Response index:** `{ "payment_methods": [...] }` (مرتبة حسب `name_ar`)

---

### Shipping Zones — `shipping_zones` table (ShippingZoneController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/shipping-zones` | List |
| POST | `/shipping-zones` | Create |
| PUT | `/shipping-zones/{shippingZone}` | Update |
| DELETE | `/shipping-zones/{shippingZone}` | Delete |

**Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name_ar` | string | yes | `max:100` |
| `name_en` | string | yes | `max:100` |
| `shipping_cost` | numeric | yes | `min:0` |
| `free_shipping_threshold` | numeric | no | `min:0` |
| `is_active` | boolean | no | — |

**Response index:** `{ "shipping_zones": [...] }` (مرتبة حسب `name_ar`)

---

## 5. Customers & Users

### Users — `users` table (UserController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/users` | List (filterable) |
| POST | `/users` | Create |
| GET | `/users/{user}` | Show with order/review counts |
| PUT | `/users/{user}` | Update |
| DELETE | `/users/{user}` | Delete (last admin prevention) |

#### GET /users — قائمة المستخدمين

**Query Parameters (Filters):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `role` | string | — | `customer`, `admin`, `vendor` |
| `search` | string | — | بحث في `username`, `email`, `first_name`, `last_name`, `phone` |
| `is_active` | boolean | — | — |
| `sort` | string | `created_at` | — |
| `order` | string | `desc` | — |
| `per_page` | integer | `15` | — |

#### POST /users — إنشاء مستخدم

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `username` | string | no | `max:50`, `unique:users,username` |
| `email` | string | yes | `email`, `max:100`, `unique:users,email` |
| `password` | string | no | `min:8` |
| `first_name` | string | no | `max:50` |
| `last_name` | string | no | `max:50` |
| `phone` | string | no | `max:20` |
| `role` | string | no | `in:customer,admin,vendor` |
| `is_active` | boolean | no | — |
| `locale` | string | no | `max:10` |

#### GET /users/{user} — عرض مستخدم

**Response:** `{ "user": { id, username, email, first_name, last_name, phone, role, locale, is_active, last_login, created_at, updated_at, orders_count, reviews_count } }`

#### DELETE /users/{user}
- **Error (409):** `{ "message": "Cannot delete the last admin account." }`

---

### Addresses — `addresses` table (AddressController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/addresses` | List (filterable) |
| POST | `/addresses` | Create |
| GET | `/addresses/{address}` | Show |
| PUT | `/addresses/{address}` | Update |
| DELETE | `/addresses/{address}` | Delete |

#### GET /addresses — قائمة العناوين

**Query Parameters (Filters):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `user_id` | integer | — | — |
| `address_type` | string | — | `home`, `work`, `other`, `shipping`, `billing`, `both` |
| `search` | string | — | بحث في `street_address`, `city` |
| `sort` | string | `created_at` | — |
| `order` | string | `desc` | — |
| `per_page` | integer | `15` | — |

#### POST /addresses — إنشاء عنوان

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `user_id` | integer | yes | `exists:users,id` |
| `address_type` | string | no | `in:home,work,other,shipping,billing,both` |
| `label` | string | no | تسمية العنوان (مثل "المنزل", "العمل") |
| `street_address` | string | yes | `max:255` |
| `city` | string | yes | `max:100` |
| `state` | string | no | `max:100` |
| `postal_code` | string | no | `max:20` |
| `country` | string | no | `max:100` |
| `is_default` | boolean | no | — |
| `latitude` | numeric | no | — |
| `longitude` | numeric | no | — |
| `building_number` | string | no | `max:50` |
| `floor_number` | string | no | `max:10` |
| `apartment_number` | string | no | `max:50` |
| `additional_directions` | string | no | `max:500` |

**Response:** `{ id, user_id, address_type, label, street_address, city, state, country, postal_code, latitude, longitude, building_number, floor_number, apartment_number, additional_directions, is_default, created_at, updated_at }`

---

### Wishlist — `wishlist` table (WishlistController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/wishlist` | List (filterable) |
| POST | `/wishlist` | Create |
| GET | `/wishlist/{wishlist}` | Show |
| PUT | `/wishlist/{wishlist}` | Update |
| DELETE | `/wishlist/{wishlist}` | Delete |

**Query Parameters (index):** `user_id`, `product_id`, `per_page` (default: 15)

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `user_id` | integer | yes | `exists:users,id` |
| `product_id` | integer | yes | `exists:products,id` |
| `variant_id` | integer | no | `exists:product_variants,id` |

**Response:** مع `user`, `product`, `variant` (Eager loaded)

---

### Compare Items — `compare_items` table (CompareController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/compare` | List (filterable) |
| POST | `/compare` | Create |
| GET | `/compare/{compareItem}` | Show |
| DELETE | `/compare/{compareItem}` | Delete |

**Query Parameters (index):** `user_id`, `session_id`, `per_page` (default: 15)

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `user_id` | integer | no | `exists:users,id` |
| `session_id` | string | no | `max:255` |
| `product_id` | integer | yes | `exists:products,id` |

---

### Carts — `carts` table (CartController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/carts` | List with items |
| GET | `/carts/{cart}` | Show full |
| DELETE | `/carts/{cart}` | Delete cart + items |
| GET | `/carts/{cart}/items` | List cart items |
| PUT | `/carts/{cart}/items/{item}` | Update item quantity |
| DELETE | `/carts/{cart}/items/{item}` | Remove specific item |

#### GET /carts — قائمة السلات

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `user_id` | integer | — | — |
| `session_id` | string | — | — |
| `sort` | string | `created_at` | — |
| `order` | string | `desc` | — |
| `per_page` | integer | `15` | — |

**Response:** مع `user`, `items.product`, `items.variant`

#### PUT /carts/{cart}/items/{item} — تحديث كمية الصنف

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `quantity` | integer | yes | `min:1` |

---

### Notifications — `notifications` table (NotificationController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/notifications` | List (filterable) |
| POST | `/notifications` | Create |
| GET | `/notifications/{notification}` | Show |
| PUT | `/notifications/{notification}` | Update |
| PATCH | `/notifications/{notification}/read` | Mark as read |
| DELETE | `/notifications/{notification}` | Delete |

#### GET /notifications — قائمة الإشعارات

**Query Parameters:** `user_id`, `type`, `is_read` (boolean), `per_page` (default: 15)

#### POST /notifications — إنشاء إشعار

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `user_id` | integer | yes | `exists:users,id` |
| `type` | string | yes | `max:100` |
| `title_ar` | string | yes | `max:255` |
| `title_en` | string | yes | `max:255` |
| `body_ar` | string | no | — |
| `body_en` | string | no | — |
| `data` | JSON | no | — |

#### PATCH /notifications/{notification}/read — تعيين كمقروء

**Response:** `{ "message": "Notification marked as read." }`

---

## 6. Marketing & Discounts

### Coupons — `coupons` table (CouponController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/coupons` | List with usage count |
| POST | `/coupons` | Create |
| GET | `/coupons/{coupon}` | Show with usage history |
| PUT | `/coupons/{coupon}` | Update |
| DELETE | `/coupons/{coupon}` | Delete |
| POST | `/coupons/validate` | Validate coupon |

#### GET /coupons — قائمة الكوبونات

**Query Parameters:** `search` (code), `is_active` (boolean), `sort`, `order`, `per_page` (default: 15)

#### POST /coupons — إنشاء كوبون

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `code` | string | yes | `max:255`, `unique:coupons,code` |
| `discount_type` | string | yes | `in:percentage,fixed` |
| `discount_value` | numeric | yes | `min:0` |
| `minimum_order_amount` | numeric | no | `min:0` |
| `maximum_discount` | numeric | no | `min:0` |
| `applicable_to` | string | no | `in:all,categories,products` |
| `minimum_quantity` | integer | no | `min:1` |
| `exclude_sale_items` | boolean | no | — |
| `usage_limit` | integer | no | `min:0` |
| `start_date` | date | no | — |
| `end_date` | date | no | `after_or_equal:start_date` |
| `is_active` | boolean | no | — |
| `is_free_shipping` | boolean | no | — |
| `per_user_limit` | integer | no | `min:1` |
| `user_id` | integer | no | `exists:users,id` |
| `min_orders_count` | integer | no | `min:1` |
| `categories[]` | array | no | `exists:categories,id` |
| `products[]` | array | no | `exists:products,id` |

#### GET /coupons/{coupon} — عرض كوبون

**Response:** مع `categories`, `products`, `usage` (مع `user` و `order`)

#### POST /coupons/validate — التحقق من كوبون

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `code` | string | yes | `max:255`, `exists:coupons,code` |
| `subtotal` | numeric | no | `min:0` |
| `cart_items` | array | no | — |

**Response (valid):** `{ "valid": true, "coupon": {...}, "discount": { "type": "percentage", "value": 10, "amount": 50.00 } }`

**Response (invalid - 422):** `{ "valid": false, "message": "Coupon expired." }`

---

### Reviews — `product_reviews` table (ReviewController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/reviews` | List (filterable) |
| PUT | `/reviews/{review}` | Update |
| PATCH | `/reviews/{review}/approve` | Toggle approval |
| DELETE | `/reviews/{review}` | Delete |

**Query Parameters (index):** `is_approved` (boolean), `rating` (1-5), `sort`, `order`, `per_page` (default: 15)

**Body (update):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `rating` | integer | no | `min:1`, `max:5` |
| `review_title` | string | no | `max:255` |
| `review_text` | string | no | — |
| `is_approved` | boolean | no | — |

**Response (index):** مع `user` و `product` (Eager loaded)

**PATCH /reviews/{review}/approve:** تحوّل حالة `is_approved` بين `true` و `false`. Response: `{ "message": "...", "review": {...} }`

---

### Price Alerts — `price_alerts` table (PriceAlertController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/price-alerts` | List (filterable) |
| POST | `/price-alerts` | Create |
| GET | `/price-alerts/{priceAlert}` | Show |
| PUT | `/price-alerts/{priceAlert}` | Update |
| DELETE | `/price-alerts/{priceAlert}` | Delete |

**Query Parameters (index):** `user_id`, `is_active` (boolean), `is_triggered` (boolean), `per_page` (default: 15)

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `user_id` | integer | yes | `exists:users,id` |
| `product_id` | integer | yes | `exists:products,id` |
| `variant_id` | integer | no | `exists:product_variants,id` |
| `target_price` | numeric | yes | `min:0` |
| `is_active` | boolean | no | — |

**Body (update):** `target_price` (sometimes, min:0), `is_active` (boolean), `is_triggered` (boolean), `triggered_at` (nullable date)

**Response (index):** مع `user`, `product`, `variant` (Eager loaded)

---

### Stock Alerts — `stock_alerts` table (StockAlertController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/stock-alerts` | List (filterable) |
| POST | `/stock-alerts` | Create |
| GET | `/stock-alerts/{stockAlert}` | Show |
| PUT | `/stock-alerts/{stockAlert}` | Update |
| DELETE | `/stock-alerts/{stockAlert}` | Delete |

**Query Parameters (index):** `user_id`, `is_notified` (boolean), `per_page` (default: 15)

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `user_id` | integer | yes | `exists:users,id` |
| `product_id` | integer | yes | `exists:products,id` |
| `variant_id` | integer | no | `exists:product_variants,id` |
| `email` | string | yes | `email`, `max:100` |
| `phone` | string | no | `max:20` |

**Response (index):** مع `user`, `product`, `variant` (Eager loaded)

---

## 7. Inventory & Pricing

### Price History — `price_history` table (PriceHistoryController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/price-history` | List (filterable) |
| POST | `/price-history` | Create (manual record) |
| GET | `/price-history/{priceHistory}` | Show |
| DELETE | `/price-history/{priceHistory}` | Delete |

**Query Parameters (index):** `product_id`, `variant_id`, `date_from`, `date_to`, `per_page` (default: 15)

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `product_id` | integer | yes | `exists:products,id` |
| `variant_id` | integer | no | `exists:product_variants,id` |
| `old_price` | numeric | yes | `min:0` |
| `new_price` | numeric | yes | `min:0` |
| `note` | string | no | `max:255` |

**ملاحظة:** `changed_by` يحدد تلقائياً من `$request->user()->id`

**Response:** مع `product`, `variant`, `changedBy` (Eager loaded)

---

### Inventory Transactions — `inventory_transactions` table (InventoryController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inventory` | List (filterable) |
| POST | `/inventory` | Create transaction (auto-updates stock) |
| GET | `/inventory/{inventoryTransaction}` | Show |
| PUT | `/inventory/{inventoryTransaction}` | Update reason/reference_id |
| DELETE | `/inventory/{inventoryTransaction}` | Delete |

**Query Parameters (index):** `product_id`, `variant_id`, `change_type` (`in|out|adjustment`), `date_from`, `date_to`, `per_page` (default: 15)

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `product_id` | integer | yes | `exists:products,id` |
| `variant_id` | integer | no | `exists:product_variants,id` |
| `quantity_change` | integer | yes | يمكن أن يكون سالباً |
| `change_type` | string | yes | `in:in,out,adjustment` |
| `reason` | string | no | `max:255` |
| `reference_id` | string | no | `max:100` |

**ملاحظة:** يتم تحديث `stock_quantity` في `Product` أو `ProductVariant` تلقائياً عند إنشاء حركة مخزون.

**Body (update):** `reason` (nullable, max:255), `reference_id` (nullable, max:100)

**Response:** مع `product`, `variant`, `changedBy` (Eager loaded)

---

## 8. Configuration

### Tax Rates — `tax_rates` table (TaxRateController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/tax-rates` | List |
| POST | `/tax-rates` | Create |
| PUT | `/tax-rates/{taxRate}` | Update |
| DELETE | `/tax-rates/{taxRate}` | Delete |

**Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name_ar` | string | yes | `max:50` |
| `name_en` | string | yes | `max:50` |
| `rate_percent` | numeric | yes | `min:0`, `max:100` |
| `is_active` | boolean | no | — |

**Response index:** `{ "tax_rates": [...] }` (مرتبة حسب `rate_percent`)

---

### Settings — `settings` table (SettingController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/settings` | All settings grouped |
| PUT | `/settings` | Update multiple |

#### GET /settings
**Response:** `{ "settings": { "general": [{ "key": "store_name", "value": "متجري", "group": "general" }], "shipping": [...] } }`

#### PUT /settings — تحديث الإعدادات

**Body:**
```json
{
  "settings": [
    { "key": "store_name", "value": "متجري", "group": "general" },
    { "key": "shipping_cost", "value": "25", "group": "shipping" }
  ]
}
```

**Validation:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `settings` | array | yes | — |
| `settings.*.key` | string | yes | `max:100` |
| `settings.*.value` | string | yes | — |
| `settings.*.group` | string | no | `max:50` |

**Behavior:** `updateOrCreate` لكل مفتاح (ينشئ إذا لم يكن موجوداً أو يحدّث الموجود)

---

## 9. Analytics & Monitoring

### Recently Viewed — `recently_viewed` table (RecentlyViewedController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/recently-viewed` | List (filterable) |
| POST | `/recently-viewed` | Create |
| GET | `/recently-viewed/{recentlyViewed}` | Show |
| DELETE | `/recently-viewed/{recentlyViewed}` | Delete |

**Query Parameters (index):** `user_id`, `product_id`, `per_page` (default: 15)

**Body (store):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `user_id` | integer | yes | `exists:users,id` |
| `product_id` | integer | yes | `exists:products,id` |
| `variant_id` | integer | no | `exists:product_variants,id` |
| `session_id` | string | no | `max:255` |
| `viewed_at` | date | no | — |

**Response:** مع `user`, `product`, `variant` (مرتبة حسب `viewed_at` desc)

---

### Audit Log — `audit_log` table (AuditLogController) — Read-only

| Method | Path | Description |
|--------|------|-------------|
| GET | `/audit-logs` | List (filterable) |
| GET | `/audit-logs/{auditLog}` | Show |

**Query Parameters (index):** `user_id`, `event_type`, `auditable_type`, `auditable_id`, `date_from`, `date_to`, `per_page` (default: 15)

**Response:** مع `user` (Eager loaded)

Captures: created/updated/deleted on Products, Orders, Coupons, Settings + user/IP/UA.

---

### System: Failed Jobs — `failed_jobs` table (SystemController)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/system/failed-jobs` | List |
| GET | `/system/failed-jobs/{id}` | Show |
| POST | `/system/failed-jobs/{id}/retry` | Retry (remove from failed) |

### System: Job Batches — `job_batches` table
| Method | Path | Description |
|--------|------|-------------|
| GET | `/system/job-batches` | List |
| GET | `/system/job-batches/{id}` | Show |

### System: Jobs Queue — `jobs` table
| Method | Path | Description |
|--------|------|-------------|
| GET | `/system/jobs` | List queue |

---

## Common Response Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthenticated |
| 403 | Forbidden (not admin) |
| 404 | Not found |
| 409 | Conflict (e.g. last admin, category with children) |
| 422 | Validation failed |

## Error Format
```json
{ "message": "Error description", "code": 403 }
```

## Validation Error Format
```json
{
  "message": "The name_ar field is required.",
  "errors": { "name_ar": ["The name_ar field is required."] }
}
```

---

## Complete Database Coverage (46 Tables)

| # | Table | Admin API | Controller |
|---|-------|-----------|------------|
| 1 | `users` | ✅ CRUD | UserController |
| 2 | `password_reset_tokens` | — (internal) | — |
| 3 | `addresses` | ✅ Full CRUD | AddressController |
| 4 | `audit_log` | ✅ Read | AuditLogController |
| 5 | `categories` | ✅ CRUD + tree | CategoryController |
| 6 | `tags` | ✅ CRUD | TagController |
| 7 | `tax_rates` | ✅ CRUD | TaxRateController |
| 8 | `products` | ✅ CRUD | ProductController |
| 9 | `product_categories` | ✅ sync via products | — |
| 10 | `product_tags` | ✅ sync via products | — |
| 11 | `attributes` | ✅ CRUD | AttributeController |
| 12 | `attribute_values` | ✅ Full CRUD (Create/Update/Delete) | AttributeController |
| 13 | `product_attributes` | ✅ sync via products | — |
| 14 | `product_variants` | ✅ CRUD | VariantController |
| 15 | `variant_attribute_values` | ✅ sync via variants | — |
| 16 | `product_images` | ✅ CRUD | ImageController |
| 17 | `product_reviews` | ✅ Full CRUD + Approve | ReviewController |
| 18 | `carts` | ✅ List/Show/Delete | CartController |
| 19 | `cart_items` | ✅ List/Update/Remove | CartController |
| 20 | `coupons` | ✅ CRUD + Validate | CouponController |
| 21 | `coupon_categories` | ✅ sync via coupons | — |
| 22 | `coupon_products` | ✅ sync via coupons | — |
| 23 | `orders` | ✅ List/Show/Status/Delete | OrderController |
| 24 | `coupon_usage` | ✅ loaded via coupons | — |
| 25 | `order_items` | ✅ loaded via orders | — |
| 26 | `wishlist` | ✅ CRUD | WishlistController |
| 27 | `compare_items` | ✅ CRUD | CompareController |
| 28 | `notifications` | ✅ Full CRUD + MarkRead | NotificationController |
| 29 | `recently_viewed` | ✅ CRUD | RecentlyViewedController |
| 30 | `price_alerts` | ✅ Full CRUD | PriceAlertController |
| 31 | `stock_alerts` | ✅ Full CRUD | StockAlertController |
| 32 | `price_history` | ✅ CRUD (manual record) | PriceHistoryController |
| 33 | `inventory_transactions` | ✅ Full CRUD | InventoryController |
| 34 | `payment_methods` | ✅ CRUD | PaymentMethodController |
| 35 | `payments` | ✅ Full CRUD | PaymentController |
| 36 | `shipping_zones` | ✅ CRUD | ShippingZoneController |
| 37 | `shipping` | ✅ CRUD | ShippingController |
| 38 | `settings` | ✅ Read/Update | SettingController |
| 39 | `cache` | — (internal) | — |
| 40 | `cache_locks` | — (internal) | — |
| 41 | `sessions` | — (internal) | — |
| 42 | `jobs` | ✅ List | SystemController |
| 43 | `job_batches` | ✅ List/Show | SystemController |
| 44 | `failed_jobs` | ✅ List/Retry | SystemController |
| 45 | `personal_access_tokens` | — (Sanctum) | — |
| 46 | `password_reset_tokens` | — (internal) | — |

---

## Architecture

### Services (`app/Services/`)
| Service | Responsibility |
|---------|---------------|
| `OrderService` | Order listing, status transitions, revenue summary |
| `CouponService` | Coupon validation (dates, limits, min order, applicables), discount calc |
| `ProductVariantService` | Variant CRUD with EAV sync, stock management |
| `AuditService` | Centralized audit log with user/IP/UA context |

### Observers (`app/Observers/`) — auto-log to `audit_log`
- `ProductObserver`, `OrderObserver`, `CouponObserver`, `SettingObserver`

### Middleware
| Middleware | Scope |
|-----------|-------|
| `auth:sanctum` | جميع المسارات ما عدا `/auth/login` |
| `admin` | جميع المسارات ما عدا `/auth/login` (تحقق `role === admin`) |
| `throttle:5,1` | `/auth/login` فقط |

---

## Quick Start for Frontend Developer

```js
// 1. Login
POST /api/admin/auth/login  { email: "admin@admin.com", password: "admin123" }
// Response: { token: "1|xxx", user: {...} }

// 2. All requests include:
Authorization: Bearer 1|xxx
Accept: application/json

// 3. First page: Login → Dashboard → Products
```
