-- ============================================================
-- قاعدة بيانات متجر إلكتروني متكامل (Online Store Database)
-- النسخة المحسنة - تدعم اللغة العربية والإنجليزية بالكامل
-- متوافقة مع Laravel 13 / MySQL 8+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- 1. المستخدمون (users)
-- ============================================================
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'المعرف الفريد للمستخدم',
  `username` VARCHAR(50) NOT NULL COMMENT 'اسم المستخدم (فريد)',
  `email` VARCHAR(100) NOT NULL COMMENT 'البريد الإلكتروني (فريد)',
  `email_verified_at` TIMESTAMP NULL COMMENT 'تاريخ توثيق البريد',
  `remember_token` VARCHAR(100) NULL COMMENT 'رمز تذكر الدخول',
  `password` VARCHAR(255) NOT NULL COMMENT 'كلمة المرور المشفرة',
  `first_name` VARCHAR(50) NULL COMMENT 'الاسم الأول',
  `last_name` VARCHAR(50) NULL COMMENT 'اسم العائلة',
  `phone` VARCHAR(20) NULL COMMENT 'رقم الهاتف',
  `date_of_birth` DATE NULL COMMENT 'تاريخ الميلاد',
  `last_login` TIMESTAMP NULL COMMENT 'آخر تسجيل دخول',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'الحساب نشط؟',
  `role` ENUM('customer','admin','vendor') NOT NULL DEFAULT 'customer' COMMENT 'دور المستخدم',
  `locale` VARCHAR(10) NOT NULL DEFAULT 'ar-SA' COMMENT 'اللغة المفضلة',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='المستخدمون (عملاء، إداريون، بائعون)';

-- ============================================================
-- 2. رموز إعادة تعيين كلمة المرور (password_reset_tokens)
-- ============================================================
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(100) NOT NULL COMMENT 'البريد الإلكتروني',
  `token` VARCHAR(255) NOT NULL COMMENT 'رمز إعادة التعيين',
  `created_at` TIMESTAMP NULL COMMENT 'تاريخ الإنشاء',
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='رموز إعادة تعيين كلمة المرور';

-- ============================================================
-- 3. العناوين (addresses)
-- ============================================================
CREATE TABLE `addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف العنوان',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم المالك',
  `address_type` ENUM('home','work','other','shipping','billing','both') NOT NULL DEFAULT 'home' COMMENT 'نوع العنوان',
  `street_address` VARCHAR(255) NOT NULL COMMENT 'العنوان التفصيلي',
  `city` VARCHAR(100) NOT NULL COMMENT 'المدينة',
  `state` VARCHAR(100) NULL COMMENT 'المنطقة / المحافظة',
  `postal_code` VARCHAR(20) NULL COMMENT 'الرمز البريدي',
  `country` VARCHAR(100) NOT NULL DEFAULT 'Saudi Arabia' COMMENT 'الدولة',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'العنوان الافتراضي؟',
  `latitude` DECIMAL(10,8) NULL COMMENT 'خط العرض',
  `longitude` DECIMAL(11,8) NULL COMMENT 'خط الطول',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_addresses_user` (`user_id`),
  KEY `idx_addresses_type_default` (`address_type`,`is_default`),
  CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='عناوين المستخدمين (شحن وفوترة)';

-- ============================================================
-- 4. سجل التدقيق (audit_log)
-- ============================================================
CREATE TABLE `audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL COMMENT 'المستخدم المنفذ',
  `action` VARCHAR(50) NOT NULL COMMENT 'الإجراء (create, update, delete)',
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'نوع الكيان (product, order...)',
  `entity_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف الكيان',
  `before_payload` JSON NULL COMMENT 'البيانات قبل التغيير',
  `after_payload` JSON NULL COMMENT 'البيانات بعد التغيير',
  `ip_address` VARCHAR(45) NULL COMMENT 'عنوان IP',
  `user_agent` VARCHAR(255) NULL COMMENT 'المتصفح',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_entity` (`entity_type`,`entity_id`),
  KEY `idx_al_action_time` (`action`,`created_at`),
  KEY `fk_al_user` (`user_id`),
  CONSTRAINT `fk_audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل التدقيق والمراجعة لجميع التغييرات';

-- ============================================================
-- 5. الفئات (categories) – هيكل هرمي
-- ============================================================
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الفئة',
  `name_ar` VARCHAR(100) NOT NULL COMMENT 'اسم الفئة بالعربية',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'اسم الفئة بالإنجليزية',
  `description_ar` TEXT NULL COMMENT 'وصف الفئة بالعربية',
  `description_en` TEXT NULL COMMENT 'وصف الفئة بالإنجليزية',
  `parent_id` BIGINT UNSIGNED NULL COMMENT 'الفئة الأم (للتصنيف الهرمي)',
  `slug` VARCHAR(100) NOT NULL COMMENT 'رابط SEO',
  `image` VARCHAR(255) NULL COMMENT 'صورة الفئة',
  `meta_title` VARCHAR(255) NULL COMMENT 'عنوان SEO',
  `meta_description` TEXT NULL COMMENT 'وصف SEO',
  `display_order` INT NOT NULL DEFAULT 0 COMMENT 'ترتيب العرض',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشطة؟',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  KEY `idx_categories_active` (`is_active`),
  FULLTEXT KEY `ftx_categories_text` (`name_ar`,`name_en`,`description_ar`,`description_en`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='فئات المنتجات (هرمية)';

-- ============================================================
-- 6. الوسوم (tags)
-- ============================================================
CREATE TABLE `tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الوسم',
  `name_ar` VARCHAR(100) NOT NULL COMMENT 'اسم الوسم بالعربية',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'اسم الوسم بالإنجليزية',
  `slug` VARCHAR(100) NOT NULL COMMENT 'رابط SEO',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='وسوم المنتجات';

-- ============================================================
-- 7. الضرائب (tax_rates)
-- ============================================================
CREATE TABLE `tax_rates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الضريبة',
  `name_ar` VARCHAR(50) NOT NULL COMMENT 'اسم الضريبة بالعربية',
  `name_en` VARCHAR(50) NOT NULL COMMENT 'اسم الضريبة بالإنجليزية',
  `rate_percent` DECIMAL(5,2) NOT NULL COMMENT 'نسبة الضريبة (مثال: 15.00)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشطة؟',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tax_rates_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='نسب الضرائب';

-- ============================================================
-- 8. المنتجات (products)
-- ============================================================
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف المنتج',
  `name_ar` VARCHAR(500) NOT NULL COMMENT 'اسم المنتج بالعربية',
  `name_en` VARCHAR(500) NOT NULL COMMENT 'اسم المنتج بالإنجليزية',
  `slug` VARCHAR(255) NULL COMMENT 'رابط SEO',
  `description_ar` TEXT NULL COMMENT 'وصف المنتج بالعربية',
  `description_en` TEXT NULL COMMENT 'وصف المنتج بالإنجليزية',
  `sku` VARCHAR(100) NOT NULL COMMENT 'رمز التخزين (قد لا يستخدم مع المتغيرات)',
  `regular_price` DECIMAL(12,2) NOT NULL COMMENT 'السعر العادي',
  `sale_price` DECIMAL(12,2) NULL COMMENT 'سعر الخصم',
  `cost_price` DECIMAL(12,2) NULL COMMENT 'سعر التكلفة',
  `tax_rate_id` INT UNSIGNED NULL COMMENT 'معرف الضريبة',
  `quantity_in_stock` INT NOT NULL DEFAULT 0 COMMENT 'المخزون الإجمالي',
  `low_stock_threshold` INT NOT NULL DEFAULT 0 COMMENT 'حد التنبيه لانخفاض المخزون',
  `weight` DECIMAL(10,2) NULL COMMENT 'الوزن (كجم)',
  `dimensions` VARCHAR(100) NULL COMMENT 'الأبعاد (طول×عرض×ارتفاع)',
  `main_image` VARCHAR(255) NULL COMMENT 'الصورة الرئيسية',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'منتج نشط؟',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'منتج مميز؟',
  `brand_id` BIGINT UNSIGNED NULL COMMENT 'العلامة التجارية',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `idx_products_active` (`is_active`),
  KEY `idx_products_featured` (`is_featured`),
  KEY `idx_products_tax_rate` (`tax_rate_id`),
  FULLTEXT KEY `ftx_products_text` (`name_ar`,`name_en`,`description_ar`,`description_en`),
  CONSTRAINT `fk_products_tax_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='المنتجات';

-- ============================================================
-- 9. ربط المنتجات بالفئات (product_categories)
-- ============================================================
CREATE TABLE `product_categories` (
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف المنتج',
  `category_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف الفئة',
  PRIMARY KEY (`product_id`, `category_id`),
  KEY `idx_pc_category` (`category_id`),
  CONSTRAINT `fk_pc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ربط المنتجات بالفئات';

-- ============================================================
-- 10. ربط المنتجات بالوسوم (product_tags)
-- ============================================================
CREATE TABLE `product_tags` (
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف المنتج',
  `tag_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف الوسم',
  PRIMARY KEY (`product_id`, `tag_id`),
  KEY `idx_pt_tag` (`tag_id`),
  CONSTRAINT `fk_pt_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ربط المنتجات بالوسوم';

-- ============================================================
-- 11. أنواع السمات (attributes) – EAV
-- ============================================================
CREATE TABLE `attributes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف السمة',
  `name_ar` VARCHAR(100) NOT NULL COMMENT 'اسم السمة بالعربية (مثال: اللون)',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'اسم السمة بالإنجليزية (مثال: Color)',
  `attribute_type` ENUM('select','color','size','text') NOT NULL DEFAULT 'select' COMMENT 'نوع واجهة الإدخال',
  `display_order` INT NOT NULL DEFAULT 0 COMMENT 'ترتيب العرض',
  `is_global` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'قابلة لإعادة الاستخدام عبر المنتجات؟',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='أنواع السمات (الخصائص) للمنتجات';

-- ============================================================
-- 12. قيم السمات (attribute_values)
-- ============================================================
CREATE TABLE `attribute_values` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف قيمة السمة',
  `attribute_id` INT UNSIGNED NOT NULL COMMENT 'السمة التابعة لها',
  `value_ar` VARCHAR(100) NOT NULL COMMENT 'القيمة بالعربية (مثال: أسود)',
  `value_en` VARCHAR(100) NOT NULL COMMENT 'القيمة بالإنجليزية (مثال: Black)',
  `extra_data` JSON NULL COMMENT 'بيانات إضافية (رمز اللون HEX، صورة مصغرة)',
  `display_order` INT NOT NULL DEFAULT 0 COMMENT 'ترتيب العرض ضمن السمة',
  PRIMARY KEY (`id`),
  KEY `idx_av_attribute` (`attribute_id`),
  CONSTRAINT `fk_attribute_values_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='قيم السمات (الخيارات المتاحة لكل سمة)';

-- ============================================================
-- 13. ربط السمات بالمنتجات (product_attributes)
-- ============================================================
CREATE TABLE `product_attributes` (
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف المنتج',
  `attribute_id` INT UNSIGNED NOT NULL COMMENT 'معرف السمة',
  `is_variation` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'لإنشاء متغيرات أم وصف ثابت؟',
  `display_order` INT NOT NULL DEFAULT 0 COMMENT 'ترتيب العرض',
  PRIMARY KEY (`product_id`, `attribute_id`),
  KEY `idx_pa_attribute` (`attribute_id`),
  CONSTRAINT `fk_pa_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تحديد السمات التي يمتلكها كل منتج';

-- ============================================================
-- 14. متغيرات المنتج (product_variants)
-- ============================================================
CREATE TABLE `product_variants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف المتغير',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج الأصلي',
  `sku` VARCHAR(100) NULL COMMENT 'رمز التخزين (فريد)',
  `regular_price` DECIMAL(12,2) NOT NULL COMMENT 'سعر المتغير',
  `sale_price` DECIMAL(12,2) NULL COMMENT 'سعر الخصم',
  `cost_price` DECIMAL(12,2) NULL COMMENT 'سعر التكلفة',
  `stock_quantity` INT NOT NULL DEFAULT 0 COMMENT 'الكمية المتوفرة',
  `barcode` VARCHAR(100) NULL COMMENT 'الباركود',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشط؟',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `idx_pv_product` (`product_id`),
  KEY `idx_pv_active` (`is_active`),
  CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='المتغيرات (التركيبات) لكل منتج';

-- ============================================================
-- 15. ربط المتغيرات بقيم السمات (variant_attribute_values)
-- ============================================================
CREATE TABLE `variant_attribute_values` (
  `variant_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف المتغير',
  `value_id` INT UNSIGNED NOT NULL COMMENT 'معرف قيمة السمة',
  PRIMARY KEY (`variant_id`, `value_id`),
  KEY `idx_vav_variant` (`variant_id`),
  KEY `idx_vav_value` (`value_id`),
  CONSTRAINT `fk_vav_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vav_value` FOREIGN KEY (`value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=u+tf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ربط المتغيرات بقيم السمات (تحقيق التركيبات الفريدة)';

-- ============================================================
-- 16. صور المنتجات (product_images)
-- ============================================================
CREATE TABLE `product_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الصورة',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'خاص بمتغير معين (مثل صورة اللون الأسود)',
  `image_url` VARCHAR(255) NOT NULL COMMENT 'مسار الصورة',
  `alt_text` VARCHAR(255) NULL COMMENT 'نص بديل',
  `display_order` INT NOT NULL DEFAULT 0 COMMENT 'ترتيب العرض',
  `is_main` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'الصورة الرئيسية؟',
  PRIMARY KEY (`id`),
  KEY `idx_pi_product_order` (`product_id`,`display_order`),
  KEY `idx_pi_variant` (`variant_id`),
  KEY `idx_pi_main` (`product_id`,`is_main`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='صور المنتجات (يمكن تخصيصها لكل متغير)';

-- ============================================================
-- 17. تقييمات المنتجات (product_reviews)
-- ============================================================
CREATE TABLE `product_reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف التقييم',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم',
  `rating` TINYINT UNSIGNED NOT NULL COMMENT 'التقييم (1-5)',
  `review_title` VARCHAR(255) NULL COMMENT 'عنوان المراجعة',
  `review_text` TEXT NULL COMMENT 'نص المراجعة',
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'موافقة الإدارة؟',
  `is_verified_purchase` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'مشتري مؤكد؟',
  `helpful_count` INT NOT NULL DEFAULT 0 COMMENT 'عدد المستفيدين',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pr_user_product` (`user_id`,`product_id`),
  KEY `idx_pr_product` (`product_id`),
  KEY `idx_pr_approved` (`is_approved`),
  KEY `idx_pr_verified` (`is_verified_purchase`),
  CONSTRAINT `fk_pr_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تقييمات وتعليقات المستخدمين على المنتجات';

-- ============================================================
-- 18. السلة (carts)
-- ============================================================
CREATE TABLE `carts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف السلة',
  `user_id` BIGINT UNSIGNED NULL COMMENT 'المستخدم (إذا كان مسجلاً)',
  `session_id` VARCHAR(255) NULL COMMENT 'معرف الجلسة للزوار',
  `coupon_code` VARCHAR(50) NULL COMMENT 'رمز الكوبون المطبق',
  `coupon_discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'قيمة الخصم',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_carts_user` (`user_id`),
  KEY `idx_carts_session` (`session_id`),
  CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سلة التسوق';

-- ============================================================
-- 19. عناصر السلة (cart_items)
-- ============================================================
CREATE TABLE `cart_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف العنصر',
  `cart_id` BIGINT UNSIGNED NOT NULL COMMENT 'السلة',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'المتغير المختار',
  `quantity` INT NOT NULL COMMENT 'الكمية',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ci_cart_product_variant` (`cart_id`,`product_id`,`variant_id`),
  KEY `idx_ci_cart` (`cart_id`),
  KEY `idx_ci_product` (`product_id`),
  KEY `idx_ci_variant` (`variant_id`),
  CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='عناصر السلة';

-- ============================================================
-- 20. الكوبونات (coupons)
-- ============================================================
CREATE TABLE `coupons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الكوبون',
  `code` VARCHAR(50) NOT NULL COMMENT 'رمز الكوبون',
  `discount_type` ENUM('percentage','fixed') NOT NULL COMMENT 'نوع الخصم',
  `discount_value` DECIMAL(12,2) NOT NULL COMMENT 'قيمة الخصم',
  `minimum_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'الحد الأدنى للطلب',
  `maximum_discount` DECIMAL(12,2) NULL COMMENT 'أقصى خصم (للمئوي)',
  `applicable_to` ENUM('all','categories','products') NOT NULL DEFAULT 'all' COMMENT 'ينطبق على',
  `minimum_quantity` INT NULL COMMENT 'الحد الأدنى للكمية',
  `exclude_sale_items` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'استبعاد المخفض؟',
  `usage_limit` INT NOT NULL DEFAULT 0 COMMENT 'حد الاستخدام (0 = غير محدود)',
  `used_count` INT NOT NULL DEFAULT 0 COMMENT 'عدد مرات الاستخدام',
  `start_date` TIMESTAMP NULL COMMENT 'تاريخ البدء',
  `end_date` TIMESTAMP NULL COMMENT 'تاريخ الانتهاء',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشط؟',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `idx_coupons_active_window` (`is_active`,`start_date`,`end_date`),
  KEY `idx_coupons_applicable` (`applicable_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='كوبونات الخصم';

-- ============================================================
-- 21. نطاق الكوبون على الفئات (coupon_categories)
-- ============================================================
CREATE TABLE `coupon_categories` (
  `coupon_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف الكوبون',
  `category_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف الفئة',
  PRIMARY KEY (`coupon_id`, `category_id`),
  KEY `idx_cc_category` (`category_id`),
  CONSTRAINT `fk_cc_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='الفئات التي ينطبق عليها الكوبون';

-- ============================================================
-- 22. نطاق الكوبون على المنتجات (coupon_products)
-- ============================================================
CREATE TABLE `coupon_products` (
  `coupon_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف الكوبون',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'معرف المنتج',
  PRIMARY KEY (`coupon_id`, `product_id`),
  KEY `idx_cp_product` (`product_id`),
  CONSTRAINT `fk_cp_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='المنتجات التي ينطبق عليها الكوبون';

-- ============================================================
-- 23. الطلبات (orders)
-- ============================================================
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الطلب',
  `order_number` VARCHAR(100) NOT NULL COMMENT 'رقم الطلب الفريد',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم',
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'المجموع قبل الخصم والشحن والضريبة',
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'قيمة الضريبة',
  `shipping_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'قيمة الشحن',
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'قيمة الخصم',
  `coupon_code` VARCHAR(50) NULL COMMENT 'رمز الكوبون المستخدم',
  `final_amount` DECIMAL(12,2) NOT NULL COMMENT 'المبلغ النهائي',
  `currency` CHAR(3) NOT NULL DEFAULT 'SAR' COMMENT 'العملة',
  `currency_rate` DECIMAL(10,4) NOT NULL DEFAULT 1.0000 COMMENT 'سعر الصرف',
  `order_status` ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'حالة الطلب',
  `confirmed_at` TIMESTAMP NULL COMMENT 'تاريخ التأكيد',
  `shipped_at` TIMESTAMP NULL COMMENT 'تاريخ الشحن',
  `delivered_at` TIMESTAMP NULL COMMENT 'تاريخ التسليم',
  `cancelled_at` TIMESTAMP NULL COMMENT 'تاريخ الإلغاء',
  `payment_status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending' COMMENT 'حالة الدفع',
  `shipping_address_id` BIGINT UNSIGNED NULL COMMENT 'عنوان الشحن',
  `billing_address_id` BIGINT UNSIGNED NULL COMMENT 'عنوان الفوترة',
  `order_meta` JSON NULL COMMENT 'بيانات إضافية',
  `notes` TEXT NULL COMMENT 'ملاحظات العميل',
  `customer_ip` VARCHAR(45) NULL COMMENT 'عنوان IP العميل',
  `customer_user_agent` VARCHAR(255) NULL COMMENT 'متصفح العميل',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `idx_orders_user_status` (`user_id`,`order_status`),
  KEY `idx_orders_date` (`created_at`),
  KEY `idx_orders_shipping_address` (`shipping_address_id`),
  KEY `idx_orders_billing_address` (`billing_address_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_shipping_address` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_billing_address` FOREIGN KEY (`billing_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='الطلبات';

-- ============================================================
-- 24. استخدام الكوبونات (coupon_usage)
-- ============================================================
CREATE TABLE `coupon_usage` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الاستخدام',
  `coupon_id` BIGINT UNSIGNED NOT NULL COMMENT 'الكوبون',
  `order_id` BIGINT UNSIGNED NOT NULL COMMENT 'الطلب',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم',
  `discount_amount` DECIMAL(12,2) NOT NULL COMMENT 'قيمة الخصم',
  `used_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الاستخدام',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cu_coupon_order_user` (`coupon_id`,`order_id`,`user_id`),
  KEY `idx_cu_order` (`order_id`),
  KEY `idx_cu_coupon_time` (`coupon_id`,`used_at`),
  KEY `idx_cu_user_time` (`user_id`,`used_at`),
  CONSTRAINT `fk_cu_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cu_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل استخدام الكوبونات';

-- ============================================================
-- 25. عناصر الطلب (order_items)
-- ============================================================
CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف العنصر',
  `order_id` BIGINT UNSIGNED NOT NULL COMMENT 'الطلب',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'المتغير المشترى',
  `quantity` INT NOT NULL COMMENT 'الكمية',
  `unit_price` DECIMAL(12,2) NOT NULL COMMENT 'سعر الوحدة وقت الشراء',
  `subtotal` DECIMAL(12,2) NOT NULL COMMENT 'المجموع الفرعي',
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'الضريبة',
  `total_price` DECIMAL(12,2) NOT NULL COMMENT 'الإجمالي',
  `product_name_ar` VARCHAR(500) NULL COMMENT 'لقطة اسم المنتج (عربي)',
  `product_name_en` VARCHAR(500) NULL COMMENT 'لقطة اسم المنتج (إنجليزي)',
  `sku_snapshot` VARCHAR(100) NULL COMMENT 'لقطة SKU',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_oi_order_product_variant` (`order_id`,`product_id`,`variant_id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `idx_oi_product` (`product_id`),
  KEY `idx_oi_variant` (`variant_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='عناصر الطلب (المنتجات المشتراة)';

-- ============================================================
-- 26. طلبات الإرجاع (return_requests)
-- ============================================================
CREATE TABLE `return_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف طلب الإرجاع',
  `order_id` BIGINT UNSIGNED NOT NULL COMMENT 'الطلب الأصلي',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم',
  `return_type` ENUM('refund','exchange') NOT NULL DEFAULT 'refund' COMMENT 'نوع الإرجاع',
  `status` ENUM('pending','approved','rejected','items_received','refunded','completed') NOT NULL DEFAULT 'pending' COMMENT 'حالة الطلب',
  `refund_amount` DECIMAL(12,2) NULL COMMENT 'المبلغ المسترد',
  `notes` TEXT NULL COMMENT 'ملاحظات',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_return_requests_order` (`order_id`),
  KEY `idx_return_requests_user` (`user_id`),
  CONSTRAINT `fk_return_requests_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_return_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='طلبات الإرجاع والاستبدال';

-- ============================================================
-- 27. عناصر الإرجاع (return_items)
-- ============================================================
CREATE TABLE `return_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف العنصر',
  `return_request_id` BIGINT UNSIGNED NOT NULL COMMENT 'طلب الإرجاع',
  `order_item_id` BIGINT UNSIGNED NOT NULL COMMENT 'عنصر الطلب الأصلي',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `quantity` INT NOT NULL COMMENT 'الكمية المرتجعة',
  `reason` TEXT NULL COMMENT 'سبب الإرجاع',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ri_return` (`return_request_id`),
  KEY `idx_ri_order_item` (`order_item_id`),
  KEY `idx_ri_product` (`product_id`),
  CONSTRAINT `fk_ri_return` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='عناصر طلب الإرجاع';

-- ============================================================
-- 28. المفضلة (wishlist)
-- ============================================================
CREATE TABLE `wishlist` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف العنصر',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'متغير معين (اختياري)',
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wishlist_user_product` (`user_id`,`product_id`,`variant_id`),
  KEY `idx_wishlist_user` (`user_id`),
  KEY `idx_wishlist_product` (`product_id`),
  KEY `idx_wishlist_variant` (`variant_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='قائمة المفضلة (المنتجات المحفوظة)';

-- ============================================================
-- 29. مقارنة المنتجات (compare_items)
-- ============================================================
CREATE TABLE `compare_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف العنصر',
  `user_id` BIGINT UNSIGNED NULL COMMENT 'المستخدم (للمسجلين)',
  `session_id` VARCHAR(255) NULL COMMENT 'معرف الجلسة (للزوار)',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_compare_user_product` (`user_id`,`product_id`),
  KEY `idx_compare_session` (`session_id`),
  KEY `idx_compare_product` (`product_id`),
  CONSTRAINT `fk_compare_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_compare_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='منتجات قائمة المقارنة';

-- ============================================================
-- 30. الإشعارات (notifications)
-- ============================================================
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الإشعار',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم المستلم',
  `type` VARCHAR(100) NOT NULL COMMENT 'نوع الإشعار (order_confirmed, price_drop...)',
  `title_ar` VARCHAR(255) NOT NULL COMMENT 'العنوان بالعربية',
  `title_en` VARCHAR(255) NOT NULL COMMENT 'العنوان بالإنجليزية',
  `body_ar` TEXT NULL COMMENT 'النص بالعربية',
  `body_en` TEXT NULL COMMENT 'النص بالإنجليزية',
  `data` JSON NULL COMMENT 'بيانات إضافية',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'مقروء؟',
  `read_at` TIMESTAMP NULL COMMENT 'تاريخ القراءة',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_read` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='إشعارات المستخدمين';

-- ============================================================
-- 31. تنبيهات الأسعار (price_alerts)
-- ============================================================
CREATE TABLE `price_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف التنبيه',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'متغير معين',
  `target_price` DECIMAL(12,2) NOT NULL COMMENT 'السعر المستهدف',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشط؟',
  `is_triggered` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'تم التفعيل؟',
  `triggered_at` TIMESTAMP NULL COMMENT 'تاريخ التفعيل',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_price_alert_user_product` (`user_id`,`product_id`,`variant_id`),
  KEY `idx_pa_product` (`product_id`),
  KEY `idx_pa_variant` (`variant_id`),
  KEY `idx_pa_status` (`is_active`,`is_triggered`),
  CONSTRAINT `fk_pa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تنبيهات انخفاض الأسعار';

-- ============================================================
-- 32. تنبيهات المخزون (stock_alerts)
-- ============================================================
CREATE TABLE `stock_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف التنبيه',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'متغير معين',
  `email` VARCHAR(100) NOT NULL COMMENT 'البريد للإشعار',
  `phone` VARCHAR(20) NULL COMMENT 'الهاتف للإشعار',
  `is_notified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'تم الإشعار؟',
  `notified_at` TIMESTAMP NULL COMMENT 'تاريخ الإشعار',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stock_alert_user_product` (`user_id`,`product_id`,`variant_id`),
  KEY `idx_sa_product` (`product_id`),
  KEY `idx_sa_variant` (`variant_id`),
  KEY `idx_sa_notified` (`is_notified`),
  CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sa_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sa_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تنبيهات توفر المخزون';

-- ============================================================
-- 33. تاريخ تغيرات الأسعار (price_history)
-- ============================================================
CREATE TABLE `price_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف السجل',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'المتغير',
  `old_price` DECIMAL(12,2) NOT NULL COMMENT 'السعر القديم',
  `new_price` DECIMAL(12,2) NOT NULL COMMENT 'السعر الجديد',
  `changed_by` BIGINT UNSIGNED NULL COMMENT 'من قام بالتغيير',
  `note` VARCHAR(255) NULL COMMENT 'ملاحظة',
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ph_product_time` (`product_id`,`created_at`),
  KEY `idx_ph_variant_time` (`variant_id`,`created_at`),
  KEY `fk_ph_user` (`changed_by`),
  CONSTRAINT `fk_ph_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ph_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ph_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل تغيرات الأسعار';

-- ============================================================
-- 34. حركة المخزون (inventory_transactions)
-- ============================================================
CREATE TABLE `inventory_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الحركة',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'المتغير',
  `quantity_change` INT NOT NULL COMMENT 'التغير (موجب = إضافة، سالب = خصم)',
  `change_type` ENUM('in','out','adjustment') NOT NULL COMMENT 'نوع الحركة',
  `reason` VARCHAR(255) NULL COMMENT 'السبب',
  `reference_id` VARCHAR(100) NULL COMMENT 'رقم مرجعي (رقم الطلب مثلاً)',
  `changed_by` BIGINT UNSIGNED NULL COMMENT 'من قام بالتغيير',
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_it_product_time` (`product_id`,`created_at`),
  KEY `idx_it_variant_time` (`variant_id`,`created_at`),
  KEY `idx_it_type` (`change_type`),
  KEY `fk_it_user` (`changed_by`),
  CONSTRAINT `fk_it_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_it_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_it_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل حركات المخزون';

-- ============================================================
-- 35. طرق الدفع (payment_methods)
-- ============================================================
CREATE TABLE `payment_methods` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الطريقة',
  `name_ar` VARCHAR(50) NOT NULL COMMENT 'الاسم بالعربية',
  `name_en` VARCHAR(50) NOT NULL COMMENT 'الاسم بالإنجليزية',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشطة؟',
  `additional_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'رسوم إضافية',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_method_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='طرق الدفع المتاحة';

-- ============================================================
-- 36. المدفوعات (payments)
-- ============================================================
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الدفعة',
  `order_id` BIGINT UNSIGNED NOT NULL COMMENT 'الطلب',
  `method_id` TINYINT UNSIGNED NULL COMMENT 'طريقة الدفع',
  `payment_method` VARCHAR(100) NULL COMMENT 'اسم طريقة الدفع (لقطة)',
  `transaction_id` VARCHAR(255) NULL COMMENT 'رقم المعاملة من البوابة',
  `amount` DECIMAL(12,2) NOT NULL COMMENT 'المبلغ',
  `payment_status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending' COMMENT 'حالة الدفع',
  `payment_date` TIMESTAMP NULL COMMENT 'تاريخ الدفع',
  `gateway_response` JSON NULL COMMENT 'الرد الكامل من بوابة الدفع',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pay_order_status` (`order_id`,`payment_status`),
  KEY `idx_pay_transaction` (`transaction_id`),
  KEY `idx_pay_method` (`method_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل المدفوعات';

-- ============================================================
-- 37. العلامات التجارية (brands)
-- ============================================================
CREATE TABLE `brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف العلامة التجارية',
  `name_ar` VARCHAR(100) NOT NULL COMMENT 'الاسم بالعربية',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'الاسم بالإنجليزية',
  `slug` VARCHAR(100) NOT NULL COMMENT 'رابط SEO',
  `logo` VARCHAR(255) NULL COMMENT 'شعار العلامة',
  `description_ar` TEXT NULL COMMENT 'وصف بالعربية',
  `description_en` TEXT NULL COMMENT 'وصف بالإنجليزية',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشطة؟',
  `meta_title` VARCHAR(255) NULL COMMENT 'عنوان SEO',
  `meta_description` TEXT NULL COMMENT 'وصف SEO',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='العلامات التجارية للمنتجات';

-- ============================================================
-- 38. مناطق الشحن (shipping_zones)
-- ============================================================
CREATE TABLE `shipping_zones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف المنطقة',
  `name_ar` VARCHAR(100) NOT NULL COMMENT 'اسم المنطقة بالعربية',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'اسم المنطقة بالإنجليزية',
  `shipping_cost` DECIMAL(10,2) NOT NULL COMMENT 'تكلفة الشحن الثابتة',
  `free_shipping_threshold` DECIMAL(10,2) NULL COMMENT 'الحد الأدنى للشحن المجاني',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشطة؟',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_zones_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='مناطق الشحن والتكلفة';

-- ============================================================
-- 39. مدن الشحن (shipping_cities)
-- ============================================================
CREATE TABLE `shipping_cities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف المدينة',
  `shipping_zone_id` BIGINT UNSIGNED NULL COMMENT 'منطقة الشحن',
  `name_ar` VARCHAR(100) NOT NULL COMMENT 'اسم المدينة بالعربية',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'اسم المدينة بالإنجليزية',
  `cost` DECIMAL(10,2) NOT NULL COMMENT 'تكلفة الشحن',
  `estimated_days_min` SMALLINT NOT NULL DEFAULT 1 COMMENT 'أقل عدد أيام التوصيل',
  `estimated_days_max` SMALLINT NOT NULL DEFAULT 5 COMMENT 'أقصى عدد أيام التوصيل',
  `free_shipping_threshold` DECIMAL(12,2) NULL COMMENT 'الحد الأدنى للشحن المجاني',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نشطة؟',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sc_zone` (`shipping_zone_id`),
  CONSTRAINT `fk_sc_zone` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='مدن الشحن مع تكلفة وأيام التوصيل';

-- ============================================================
-- 40. الشحن (shipping) – تفاصيل شحن الطلب
-- ============================================================
CREATE TABLE `shipping` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الشحنة',
  `order_id` BIGINT UNSIGNED NOT NULL COMMENT 'الطلب',
  `shipping_method` VARCHAR(100) NOT NULL COMMENT 'طريقة الشحن',
  `tracking_number` VARCHAR(100) NULL COMMENT 'رقم التتبع',
  `tracking_url` VARCHAR(255) NULL COMMENT 'رابط التتبع',
  `carrier` VARCHAR(100) NULL COMMENT 'شركة الشحن',
  `shipping_zone_id` INT UNSIGNED NULL COMMENT 'منطقة الشحن',
  `shipping_date` TIMESTAMP NULL COMMENT 'تاريخ الشحن',
  `estimated_delivery` TIMESTAMP NULL COMMENT 'التسليم المتوقع',
  `actual_delivery` TIMESTAMP NULL COMMENT 'التسليم الفعلي',
  `shipping_status` ENUM('pending','shipped','in_transit','out_for_delivery','delivered') NOT NULL DEFAULT 'pending' COMMENT 'حالة الشحن',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_order` (`order_id`),
  KEY `idx_shipping_tracking` (`tracking_number`),
  KEY `idx_shipping_carrier_status` (`carrier`,`shipping_status`),
  KEY `idx_shipping_zone` (`shipping_zone_id`),
  CONSTRAINT `fk_shipping_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_shipping_zone` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تفاصيل شحن الطلب';

-- ============================================================
-- 41. المنتجات التي شوهدت مؤخراً (recently_viewed)
-- ============================================================
CREATE TABLE `recently_viewed` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف السجل',
  `user_id` BIGINT UNSIGNED NULL COMMENT 'المستخدم',
  `session_id` VARCHAR(255) NULL COMMENT 'معرف الجلسة',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'المنتج',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'المتغير',
  `viewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ المشاهدة',
  PRIMARY KEY (`id`),
  KEY `idx_rv_product` (`product_id`),
  KEY `idx_rv_variant` (`variant_id`),
  KEY `idx_rv_user` (`user_id`,`viewed_at`),
  KEY `idx_rv_session` (`session_id`,`viewed_at`),
  CONSTRAINT `fk_rv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rv_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='المنتجات التي شاهدها المستخدم مؤخراً';

-- ============================================================
-- 42. إعدادات المتجر (settings)
-- ============================================================
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف الإعداد',
  `key` VARCHAR(100) NOT NULL COMMENT 'مفتاح الإعداد (فريد)',
  `value` LONGTEXT NOT NULL COMMENT 'قيمة الإعداد',
  `group` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT 'مجموعة الإعداد (general, payment, shipping...)',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='إعدادات المتجر العامة';

-- ============================================================
-- جداول Laravel النظامية
-- ============================================================

-- ============================================================
-- 43. التخزين المؤقت (cache)
-- ============================================================
CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 44. أقفال التخزين المؤقت (cache_locks)
-- ============================================================
CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 45. الجلسات (sessions)
-- ============================================================
CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 46. المهام (jobs)
-- ============================================================
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 47. مجموعات المهام (job_batches)
-- ============================================================
CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 48. المهام الفاشلة (failed_jobs)
-- ============================================================
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 49. رموز المصادقة الشخصية (personal_access_tokens)
-- ============================================================
CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 51. سجل الترحيلات (migrations)
-- ============================================================
CREATE TABLE `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 50. المشتركون في النشرة البريدية (newsletter_subscribers)
-- ============================================================
CREATE TABLE `newsletter_subscribers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'معرف المشترك',
  `email` VARCHAR(100) NOT NULL COMMENT 'البريد الإلكتروني',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'مشترك فعال؟',
  `subscribed_at` TIMESTAMP NULL COMMENT 'تاريخ الاشتراك',
  `unsubscribed_at` TIMESTAMP NULL COMMENT 'تاريخ إلغاء الاشتراك',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_subscribers_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='المشتركون في النشرة البريدية';

-- ============================================================
-- انتهاء إنشاء جميع الجداول
-- ============================================================
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
