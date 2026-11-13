<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\WooCommerceClient;

class WooCommerceNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.woocommerce',
            name: 'WooCommerce',
            category: NodeCategory::Action,
            description: 'مدیریت کامل محصولات، سفارش‌ها و مشتریان در فروشگاه ووکامرس (وردپرس).',
            icon: 'brand:woocommerce',
            group: 'woocommerce',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'woocommerce',
                    'label'           => 'اعتبارنامه فروشگاه ووکامرس',
                    'required'        => true,
                ],
                'resource' => [
                    'type'     => 'select',
                    'label'    => 'بخش (Resource)',
                    'options'  => [
                        ['value' => 'order', 'label' => 'Order (سفارش‌ها)'],
                        ['value' => 'product', 'label' => 'Product (محصولات)'],
                        ['value' => 'customer', 'label' => 'Customer (مشتریان)'],
                    ],
                    'default'  => 'order',
                    'required' => true,
                ],

                'operation_customer' => [
                    'type'       => 'select',
                    'label'      => 'عملیات مشتری (Operation)',
                    'options'    => [
                        ['value' => 'create', 'label' => 'Create a customer (ایجاد مشتری جدید)'],
                        ['value' => 'delete', 'label' => 'Delete a customer (حذف مشتری)'],
                        ['value' => 'get', 'label' => 'Get a customer (دریافت اطلاعات مشتری)'],
                        ['value' => 'get_many', 'label' => 'Get many customers (دریافت لیست مشتریان)'],
                        ['value' => 'update', 'label' => 'Update a customer (ویرایش اطلاعات مشتری)'],
                    ],
                    'default'    => 'create',
                    'required'   => true,
                    'depends_on' => ['resource' => 'customer'],
                ],
                'operation_order' => [
                    'type'       => 'select',
                    'label'      => 'عملیات سفارش (Operation)',
                    'options'    => [
                        ['value' => 'create', 'label' => 'Create an order (ثبت سفارش جدید)'],
                        ['value' => 'delete', 'label' => 'Delete an order (حذف سفارش)'],
                        ['value' => 'get', 'label' => 'Get an order (دریافت جزئیات سفارش)'],
                        ['value' => 'get_many', 'label' => 'Get many orders (دریافت لیست سفارش‌ها)'],
                        ['value' => 'update', 'label' => 'Update an order (ویرایش و تغییر وضعیت سفارش)'],
                    ],
                    'default'    => 'get',
                    'required'   => true,
                    'depends_on' => ['resource' => 'order'],
                ],
                'operation_product' => [
                    'type'       => 'select',
                    'label'      => 'عملیات محصول (Operation)',
                    'options'    => [
                        ['value' => 'create', 'label' => 'Create a product (ایجاد محصول جدید)'],
                        ['value' => 'delete', 'label' => 'Delete a product (حذف محصول)'],
                        ['value' => 'get', 'label' => 'Get a product (دریافت اطلاعات محصول)'],
                        ['value' => 'get_many', 'label' => 'Get many products (دریافت لیست محصولات)'],
                        ['value' => 'update', 'label' => 'Update a product (به‌روزرسانی قیمت، موجودی و محصول)'],
                    ],
                    'default'    => 'get_many',
                    'required'   => true,
                    'depends_on' => ['resource' => 'product'],
                ],

                'customer_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه مشتری (Customer ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: {{ input.id }}',
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => ['get', 'delete', 'update'],
                    ],
                ],
                'customer_email' => [
                    'type'        => 'text',
                    'label'       => 'ایمیل مشتری (Email)',
                    'required'    => true,
                    'placeholder' => 'customer@example.com یا {{ input.email }}',
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => 'create',
                    ],
                ],
                'customer_first_name' => [
                    'type'        => 'text',
                    'label'       => 'نام (First Name)',
                    'placeholder' => '{{ input.first_name }}',
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => ['create', 'update'],
                    ],
                ],
                'customer_last_name' => [
                    'type'        => 'text',
                    'label'       => 'نام خانوادگی (Last Name)',
                    'placeholder' => '{{ input.last_name }}',
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => ['create', 'update'],
                    ],
                ],
                'customer_phone' => [
                    'type'        => 'text',
                    'label'       => 'شماره موبایل / تلفن (Phone)',
                    'placeholder' => '09120000000 یا {{ input.phone }}',
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => ['create', 'update'],
                    ],
                ],

                'order_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه سفارش (Order ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: {{ input.id }}',
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => ['get', 'delete', 'update'],
                    ],
                ],
                'order_line_items' => [
                    'type'        => 'textarea',
                    'label'       => 'اقلام سفارش (Line Items)',
                    'required'    => true,
                    'default'     => "[\n  {\"product_id\": 100, \"quantity\": 1}\n]",
                    'placeholder' => '[{"product_id": 100, "quantity": 2}] یا 100:2, 105:1',
                    'help'        => 'آرایه JSON از اقلام سفارش یا فرمت خلاصه «شناسه_محصول:تعداد» (مثل 100:2) وارد کنید.',
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => 'create',
                    ],
                ],
                'order_status' => [
                    'type'       => 'select',
                    'label'      => 'وضعیت سفارش (Order Status)',
                    'options'    => [
                        ['value' => 'pending', 'label' => 'در انتظار پرداخت (pending)'],
                        ['value' => 'processing', 'label' => 'در حال انجام (processing)'],
                        ['value' => 'on-hold', 'label' => 'در انتظار بررسی (on-hold)'],
                        ['value' => 'completed', 'label' => 'تکمیل‌شده (completed)'],
                        ['value' => 'cancelled', 'label' => 'لغوشده (cancelled)'],
                        ['value' => 'refunded', 'label' => 'مستردشده (refunded)'],
                        ['value' => 'failed', 'label' => 'ناموفق (failed)'],
                    ],
                    'default'    => 'processing',
                    'required'   => true,
                    'depends_on' => [
                        'resource'        => 'order',
                        'operation_order' => ['create', 'update'],
                    ],
                ],
                'order_status_filter' => [
                    'type'       => 'select',
                    'label'      => 'فیلتر وضعیت سفارش',
                    'options'    => [
                        ['value' => 'any', 'label' => 'همه وضعیت‌ها (any)'],
                        ['value' => 'pending', 'label' => 'در انتظار پرداخت (pending)'],
                        ['value' => 'processing', 'label' => 'در حال انجام (processing)'],
                        ['value' => 'on-hold', 'label' => 'در انتظار بررسی (on-hold)'],
                        ['value' => 'completed', 'label' => 'تکمیل‌شده (completed)'],
                        ['value' => 'cancelled', 'label' => 'لغوشده (cancelled)'],
                        ['value' => 'refunded', 'label' => 'مستردشده (refunded)'],
                        ['value' => 'failed', 'label' => 'ناموفق (failed)'],
                    ],
                    'default'    => 'any',
                    'depends_on' => [
                        'resource'        => 'order',
                        'operation_order' => 'get_many',
                    ],
                ],
                'billing_first_name' => [
                    'type'        => 'text',
                    'label'       => 'نام مشتری (Billing First Name)',
                    'placeholder' => '{{ input.first_name }}',
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => 'create',
                    ],
                ],
                'billing_last_name' => [
                    'type'        => 'text',
                    'label'       => 'نام خانوادگی مشتری (Billing Last Name)',
                    'placeholder' => '{{ input.last_name }}',
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => 'create',
                    ],
                ],
                'billing_phone' => [
                    'type'        => 'text',
                    'label'       => 'شماره تماس مشتری (Billing Phone)',
                    'placeholder' => '09120000000 یا {{ input.phone }}',
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => 'create',
                    ],
                ],

                'product_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه محصول (Product ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: {{ input.id }}',
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => ['get', 'delete', 'update'],
                    ],
                ],
                'product_name' => [
                    'type'        => 'text',
                    'label'       => 'نام محصول (Product Name)',
                    'required'    => true,
                    'placeholder' => 'نام محصول را وارد کنید',
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => 'create',
                    ],
                ],
                'product_type' => [
                    'type'       => 'select',
                    'label'      => 'نوع محصول (Product Type)',
                    'options'    => [
                        ['value' => 'simple', 'label' => 'محصول ساده (simple)'],
                        ['value' => 'variable', 'label' => 'محصول متغیر (variable)'],
                        ['value' => 'external', 'label' => 'محصول خارجی/وابسته (external)'],
                        ['value' => 'grouped', 'label' => 'محصول گروهی (grouped)'],
                    ],
                    'default'    => 'simple',
                    'depends_on' => [
                        'resource'          => 'product',
                        'operation_product' => 'create',
                    ],
                ],
                'regular_price' => [
                    'type'        => 'text',
                    'label'       => 'قیمت اصلی (Regular Price)',
                    'placeholder' => 'مثلاً: 250000 یا {{ input.price }}',
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => ['create', 'update'],
                    ],
                ],
                'sale_price' => [
                    'type'        => 'text',
                    'label'       => 'قیمت فروش ویژه / حراج (Sale Price)',
                    'placeholder' => 'مثلاً: 199000',
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => 'update',
                    ],
                ],
                'stock_quantity' => [
                    'type'        => 'text',
                    'label'       => 'تعداد موجودی انبار (Stock Quantity)',
                    'placeholder' => 'مثلاً: 15 یا {{ input.stock }}',
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => 'update',
                    ],
                ],
                'product_description' => [
                    'type'        => 'textarea',
                    'label'       => 'توضیحات محصول (Description)',
                    'placeholder' => 'توضیحات کامل محصول (HTML یا متن)',
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => 'create',
                    ],
                ],

                'search' => [
                    'type'        => 'text',
                    'label'       => 'عبارت جستجو (Search)',
                    'placeholder' => 'جستجو بر اساس نام، کد، ایمیل یا متن...',
                    'depends_on'  => [
                        'resource' => ['customer', 'order', 'product'],
                    ],
                ],

                'customer_username' => [
                    'type'        => 'text',
                    'label'       => 'نام کاربری مشتری (Username)',
                    'placeholder' => 'username',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => 'create',
                    ],
                ],
                'customer_password' => [
                    'type'        => 'password',
                    'label'       => 'رمز عبور مشتری (Password)',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => 'create',
                    ],
                ],
                'customer_email_optional' => [
                    'type'        => 'text',
                    'label'       => 'تغییر ایمیل مشتری (Email)',
                    'placeholder' => 'new@example.com',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'           => 'customer',
                        'operation_customer' => 'update',
                    ],
                ],
                'customer_role' => [
                    'type'       => 'select',
                    'label'      => 'فیلتر نقش کاربری (Role)',
                    'options'    => [
                        ['value' => 'all', 'label' => 'همه نقش‌ها (all)'],
                        ['value' => 'customer', 'label' => 'مشتری (customer)'],
                        ['value' => 'subscriber', 'label' => 'مشترک (subscriber)'],
                    ],
                    'default'    => 'all',
                    'advanced'   => true,
                    'depends_on' => [
                        'resource'           => 'customer',
                        'operation_customer' => 'get_many',
                    ],
                ],

                'order_customer_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه مشتری ووکامرس (Customer ID)',
                    'placeholder' => '0 برای مهمان یا شناسه عددی کاربر',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => ['create', 'get_many'],
                    ],
                ],
                'order_set_paid' => [
                    'type'       => 'boolean',
                    'label'      => 'علامت‌گذاری به عنوان پرداخت‌شده (Set Paid)',
                    'default'    => false,
                    'advanced'   => true,
                    'depends_on' => [
                        'resource'        => 'order',
                        'operation_order' => ['create', 'update'],
                    ],
                ],
                'customer_note' => [
                    'type'        => 'textarea',
                    'label'       => 'یادداشت مشتری / توضیحات سفارش (Customer Note)',
                    'placeholder' => 'توضیحات سفارش یا پیام مشتری',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => ['create', 'update'],
                    ],
                ],
                'coupon_code' => [
                    'type'        => 'text',
                    'label'       => 'کد تخفیف (Coupon Code)',
                    'placeholder' => 'مثلاً: OFF20',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => 'create',
                    ],
                ],
                'billing_email' => [
                    'type'        => 'text',
                    'label'       => 'ایمیل صورتحساب (Billing Email)',
                    'placeholder' => 'customer@example.com',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'        => 'order',
                        'operation_order' => ['create', 'update'],
                    ],
                ],
                'address_line' => [
                    'type'        => 'text',
                    'label'       => 'آدرس پستی (Address)',
                    'placeholder' => 'خیابان، پلاک، واحد',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource' => ['customer', 'order'],
                    ],
                ],
                'city' => [
                    'type'        => 'text',
                    'label'       => 'شهر (City)',
                    'placeholder' => 'تهران',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource' => ['customer', 'order'],
                    ],
                ],
                'state' => [
                    'type'        => 'text',
                    'label'       => 'استان (State)',
                    'placeholder' => 'THR',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource' => ['customer', 'order'],
                    ],
                ],
                'postcode' => [
                    'type'        => 'text',
                    'label'       => 'کد پستی (Postcode)',
                    'placeholder' => '1234567890',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource' => ['customer', 'order'],
                    ],
                ],

                'product_name_optional' => [
                    'type'        => 'text',
                    'label'       => 'نام جدید محصول (Product Name)',
                    'placeholder' => 'نام جدید محصول',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => 'update',
                    ],
                ],
                'sale_price_create' => [
                    'type'        => 'text',
                    'label'       => 'قیمت فروش ویژه / حراج (Sale Price)',
                    'placeholder' => 'مثلاً: 199000',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => 'create',
                    ],
                ],
                'stock_quantity_create' => [
                    'type'        => 'text',
                    'label'       => 'تعداد موجودی انبار (Stock Quantity)',
                    'placeholder' => 'مثلاً: 15',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => 'create',
                    ],
                ],
                'short_description' => [
                    'type'        => 'textarea',
                    'label'       => 'توضیحات کوتاه محصول (Short Description)',
                    'placeholder' => 'خلاصه ویژگی‌های محصول',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => ['create', 'update'],
                    ],
                ],
                'sku' => [
                    'type'        => 'text',
                    'label'       => 'کد محصول / شناسه انبار (SKU)',
                    'placeholder' => 'مثلاً: PRD-101',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => ['create', 'update', 'get_many'],
                    ],
                ],
                'stock_status' => [
                    'type'       => 'select',
                    'label'      => 'وضعیت موجودی (Stock Status)',
                    'options'    => [
                        ['value' => 'instock', 'label' => 'موجود در انبار (instock)'],
                        ['value' => 'outofstock', 'label' => 'ناموجود (outofstock)'],
                        ['value' => 'onbackorder', 'label' => 'پیش‌خرید (onbackorder)'],
                    ],
                    'default'    => 'instock',
                    'advanced'   => true,
                    'depends_on' => [
                        'resource'          => 'product',
                        'operation_product' => ['create', 'update', 'get_many'],
                    ],
                ],
                'product_status' => [
                    'type'       => 'select',
                    'label'      => 'وضعیت انتشار محصول (Status)',
                    'options'    => [
                        ['value' => 'publish', 'label' => 'منتشرشده (publish)'],
                        ['value' => 'draft', 'label' => 'پیش‌نویس (draft)'],
                        ['value' => 'pending', 'label' => 'در انتظار بازبینی (pending)'],
                        ['value' => 'private', 'label' => 'خصوصی (private)'],
                    ],
                    'default'    => 'publish',
                    'advanced'   => true,
                    'depends_on' => [
                        'resource'          => 'product',
                        'operation_product' => ['create', 'update'],
                    ],
                ],
                'image_urls' => [
                    'type'        => 'textarea',
                    'label'       => 'آدرس تصاویر محصول (Image URLs)',
                    'placeholder' => 'https://example.com/image1.jpg, https://example.com/image2.jpg',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => ['create', 'update'],
                    ],
                ],
                'category_ids' => [
                    'type'        => 'text',
                    'label'       => 'شناسه دسته‌بندی‌ها (Category IDs با کاما)',
                    'placeholder' => 'مثلاً: 15, 22',
                    'advanced'    => true,
                    'depends_on'  => [
                        'resource'          => 'product',
                        'operation_product' => ['create', 'update', 'get_many'],
                    ],
                ],
                'on_sale' => [
                    'type'       => 'boolean',
                    'label'      => 'فقط محصولات حراجی (On Sale)',
                    'default'    => false,
                    'advanced'   => true,
                    'depends_on' => [
                        'resource'          => 'product',
                        'operation_product' => 'get_many',
                    ],
                ],

                'limit' => [
                    'type'     => 'number',
                    'label'    => 'حداکثر تعداد نتایج (Limit)',
                    'default'  => 20,
                    'min'      => 1,
                    'max'      => 100,
                    'integer'  => true,
                    'advanced' => true,
                ],
                'page' => [
                    'type'     => 'number',
                    'label'    => 'شماره صفحه (Page)',
                    'default'  => 1,
                    'min'      => 1,
                    'max'      => 1000,
                    'integer'  => true,
                    'advanced' => true,
                ],
                'force_delete' => [
                    'type'     => 'boolean',
                    'label'    => 'حذف دائمی بدون انتقال به زباله‌دان (Force Delete)',
                    'default'  => true,
                    'advanced' => true,
                ],
                'extra_json' => [
                    'type'        => 'code',
                    'label'       => 'فیلدهای سفارشی بیشتر ووکامرس (JSON)',
                    'placeholder' => "{\n  \"meta_data\": [\n    {\"key\": \"_custom_key\", \"value\": \"مقدار\"}\n  ]\n}",
                    'advanced'    => true,
                ],
            ],
            presets: [
                [
                    'key'         => 'customer_create',
                    'name'        => 'Create a customer',
                    'description' => 'ایجاد حساب مشتری جدید در ووکامرس به همراه اطلاعات تماس و آدرس.',
                    'config'      => ['resource' => 'customer', 'operation_customer' => 'create'],
                ],
                [
                    'key'         => 'customer_delete',
                    'name'        => 'Delete a customer',
                    'description' => 'حذف یک مشتری از فروشگاه ووکامرس با شناسه کاربری.',
                    'config'      => ['resource' => 'customer', 'operation_customer' => 'delete'],
                ],
                [
                    'key'         => 'customer_get',
                    'name'        => 'Get a customer',
                    'description' => 'دریافت مشخصات کامل یک مشتری با شناسه او.',
                    'config'      => ['resource' => 'customer', 'operation_customer' => 'get'],
                ],
                [
                    'key'         => 'customer_get_many',
                    'name'        => 'Get many customers',
                    'description' => 'جستجو و دریافت لیست مشتریان فروشگاه ووکامرس.',
                    'config'      => ['resource' => 'customer', 'operation_customer' => 'get_many'],
                ],
                [
                    'key'         => 'customer_update',
                    'name'        => 'Update a customer',
                    'description' => 'ویرایش نام، شماره تماس، آدرس و مشخصات مشتری در ووکامرس.',
                    'config'      => ['resource' => 'customer', 'operation_customer' => 'update'],
                ],

                [
                    'key'         => 'order_create',
                    'name'        => 'Create an order',
                    'description' => 'ثبت سفارش جدید در ووکامرس به همراه اقلام، وضعیت و مشخصات مشتری.',
                    'config'      => ['resource' => 'order', 'operation_order' => 'create'],
                ],
                [
                    'key'         => 'order_delete',
                    'name'        => 'Delete an order',
                    'description' => 'حذف یک سفارش از فروشگاه ووکامرس با شناسه سفارش.',
                    'config'      => ['resource' => 'order', 'operation_order' => 'delete'],
                ],
                [
                    'key'         => 'order_get',
                    'name'        => 'Get an order',
                    'description' => 'دریافت جزئیات کامل یک سفارش، اقلام و وضعیت پرداخت با شناسه سفارش.',
                    'config'      => ['resource' => 'order', 'operation_order' => 'get'],
                ],
                [
                    'key'         => 'order_get_many',
                    'name'        => 'Get many orders',
                    'description' => 'دریافت و فیلتر لیست سفارش‌های فروشگاه بر اساس وضعیت، مشتری یا جستجو.',
                    'config'      => ['resource' => 'order', 'operation_order' => 'get_many'],
                ],
                [
                    'key'         => 'order_update',
                    'name'        => 'Update an order',
                    'description' => 'تغییر وضعیت سفارش (مثلاً به تکمیل‌شده) یا ویرایش اطلاعات سفارش.',
                    'config'      => ['resource' => 'order', 'operation_order' => 'update'],
                ],

                [
                    'key'         => 'product_create',
                    'name'        => 'Create a product',
                    'description' => 'ایجاد محصول جدید در فروشگاه ووکامرس با قیمت، موجودی، تصویر و دسته‌بندی.',
                    'config'      => ['resource' => 'product', 'operation_product' => 'create'],
                ],
                [
                    'key'         => 'product_delete',
                    'name'        => 'Delete a product',
                    'description' => 'حذف یک محصول از فروشگاه ووکامرس با شناسه محصول.',
                    'config'      => ['resource' => 'product', 'operation_product' => 'delete'],
                ],
                [
                    'key'         => 'product_get',
                    'name'        => 'Get a product',
                    'description' => 'دریافت اطلاعات، قیمت و موجودی یک محصول با شناسه آن.',
                    'config'      => ['resource' => 'product', 'operation_product' => 'get'],
                ],
                [
                    'key'         => 'product_get_many',
                    'name'        => 'Get many products',
                    'description' => 'جستجو و دریافت لیست محصولات فروشگاه بر اساس نام، کد محصول (SKU) یا وضعیت موجودی.',
                    'config'      => ['resource' => 'product', 'operation_product' => 'get_many'],
                ],
                [
                    'key'         => 'product_update',
                    'name'        => 'Update a product',
                    'description' => 'به‌روزرسانی سریع قیمت، قیمت حراج، موجودی انبار و مشخصات محصول در ووکامرس.',
                    'config'      => ['resource' => 'product', 'operation_product' => 'update'],
                ],
            ],
            color: 'violet',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه فروشگاه ووکامرس انتخاب نشده است.');
        }

        $client = new WooCommerceClient($credential);
        $resource = (string) ($context->config['resource'] ?? 'order');

        return match ($resource) {
            'customer' => $this->handleCustomer($context, $client),
            'order'    => $this->handleOrder($context, $client),
            'product'  => $this->handleProduct($context, $client),
            default    => throw NodeExecutionException::because("بخش ووکامرس نامعتبر است: $resource"),
        };
    }

    private function handleCustomer(NodeContext $context, WooCommerceClient $client): NodeResult
    {
        $op = (string) ($context->config['operation_customer'] ?? 'create');

        return match ($op) {
            'get' => $this->singleResult(
                $client->get('customers/' . $this->requireId($context, 'customer_id', 'شناسه مشتری (Customer ID) الزامی است.'))
            ),

            'delete' => $this->singleResult(
                $client->delete(
                    'customers/' . $this->requireId($context, 'customer_id', 'شناسه مشتری (Customer ID) الزامی است.'),
                    ['force' => $this->bool($context, 'force_delete', true)]
                )
            ),

            'get_many' => (function () use ($context, $client) {
                $query = $this->paginationQuery($context);
                if (($search = $this->str($context, 'search')) !== '') {
                    $query['search'] = $search;
                }
                if (($role = $this->str($context, 'customer_role', 'all')) !== '' && $role !== 'all') {
                    $query['role'] = $role;
                }

                return $this->listResult($client->get('customers', $query));
            })(),

            'create' => (function () use ($context, $client) {
                $email = $this->str($context, 'customer_email');
                if ($email === '') {
                    throw NodeExecutionException::because('ایمیل مشتری (Email) برای ایجاد مشتری الزامی است.');
                }

                $payload = $this->buildCustomerPayload($context, ['email' => $email]);
                if (($username = $this->str($context, 'customer_username')) !== '') {
                    $payload['username'] = $username;
                }
                if (($password = $this->str($context, 'customer_password')) !== '') {
                    $payload['password'] = $password;
                }

                return $this->singleResult($client->post('customers', $payload));
            })(),

            'update' => (function () use ($context, $client) {
                $id = $this->requireId($context, 'customer_id', 'شناسه مشتری (Customer ID) الزامی است.');
                $payload = $this->buildCustomerPayload($context, []);

                if (($email = $this->str($context, 'customer_email_optional')) !== '') {
                    $payload['email'] = $email;
                }

                return $this->singleResult($client->put("customers/$id", $payload));
            })(),

            default => throw NodeExecutionException::because("عملیات مشتری نامعتبر است: $op"),
        };
    }

    private function handleOrder(NodeContext $context, WooCommerceClient $client): NodeResult
    {
        $op = (string) ($context->config['operation_order'] ?? 'get');

        return match ($op) {
            'get' => $this->singleResult(
                $client->get('orders/' . $this->requireId($context, 'order_id', 'شناسه سفارش (Order ID) الزامی است.'))
            ),

            'delete' => $this->singleResult(
                $client->delete(
                    'orders/' . $this->requireId($context, 'order_id', 'شناسه سفارش (Order ID) الزامی است.'),
                    ['force' => $this->bool($context, 'force_delete', true)]
                )
            ),

            'get_many' => (function () use ($context, $client) {
                $query = $this->paginationQuery($context);
                if (($status = $this->str($context, 'order_status_filter', 'any')) !== '' && $status !== 'any') {
                    $query['status'] = $status;
                }
                if (($search = $this->str($context, 'search')) !== '') {
                    $query['search'] = $search;
                }
                if (($customerId = $this->str($context, 'order_customer_id')) !== '' && is_numeric($customerId)) {
                    $query['customer'] = (int) $customerId;
                }

                return $this->listResult($client->get('orders', $query));
            })(),

            'create' => (function () use ($context, $client) {
                $lineItems = $this->parseLineItems($this->str($context, 'order_line_items'));

                $payload = [
                    'status'     => $this->str($context, 'order_status', 'processing'),
                    'line_items' => $lineItems,
                ];

                if (array_key_exists('order_set_paid', $context->config)) {
                    $payload['set_paid'] = $this->bool($context, 'order_set_paid', false);
                }

                if (($customerId = $this->str($context, 'order_customer_id')) !== '' && is_numeric($customerId)) {
                    $payload['customer_id'] = (int) $customerId;
                }

                if (($note = $this->str($context, 'customer_note')) !== '') {
                    $payload['customer_note'] = $note;
                }

                if (($coupon = $this->str($context, 'coupon_code')) !== '') {
                    $payload['coupon_lines'] = [['code' => $coupon]];
                }

                $billing = $this->buildOrderAddress($context);
                if ($billing !== []) {
                    $payload['billing'] = $billing;
                    $payload['shipping'] = array_diff_key($billing, ['email' => true, 'phone' => true]);
                }

                $payload = $this->mergeExtraJson($context, $payload);

                return $this->singleResult($client->post('orders', $payload));
            })(),

            'update' => (function () use ($context, $client) {
                $id = $this->requireId($context, 'order_id', 'شناسه سفارش (Order ID) الزامی است.');
                $payload = [];

                if (($status = $this->str($context, 'order_status')) !== '') {
                    $payload['status'] = $status;
                }

                if (array_key_exists('order_set_paid', $context->config)) {
                    $payload['set_paid'] = $this->bool($context, 'order_set_paid', false);
                }

                if (($note = $this->str($context, 'customer_note')) !== '') {
                    $payload['customer_note'] = $note;
                }

                $billing = $this->buildOrderAddress($context);
                if ($billing !== []) {
                    $payload['billing'] = $billing;
                }

                $payload = $this->mergeExtraJson($context, $payload);

                return $this->singleResult($client->put("orders/$id", $payload));
            })(),

            default => throw NodeExecutionException::because("عملیات سفارش نامعتبر است: $op"),
        };
    }

    private function handleProduct(NodeContext $context, WooCommerceClient $client): NodeResult
    {
        $op = (string) ($context->config['operation_product'] ?? 'get_many');

        return match ($op) {
            'get' => $this->singleResult(
                $client->get('products/' . $this->requireId($context, 'product_id', 'شناسه محصول (Product ID) الزامی است.'))
            ),

            'delete' => $this->singleResult(
                $client->delete(
                    'products/' . $this->requireId($context, 'product_id', 'شناسه محصول (Product ID) الزامی است.'),
                    ['force' => $this->bool($context, 'force_delete', true)]
                )
            ),

            'get_many' => (function () use ($context, $client) {
                $query = $this->paginationQuery($context);
                if (($search = $this->str($context, 'search')) !== '') {
                    $query['search'] = $search;
                }
                if (($sku = $this->str($context, 'sku')) !== '') {
                    $query['sku'] = $sku;
                }
                if (($stockStatus = $this->str($context, 'stock_status')) !== '') {
                    $query['stock_status'] = $stockStatus;
                }
                if (($cats = $this->str($context, 'category_ids')) !== '') {
                    $firstCat = trim((preg_split('/[,،]+/', $cats) ?: [])[0] ?? '');
                    if ($firstCat !== '' && is_numeric($firstCat)) {
                        $query['category'] = (int) $firstCat;
                    }
                }
                if ($this->bool($context, 'on_sale', false)) {
                    $query['on_sale'] = true;
                }

                return $this->listResult($client->get('products', $query));
            })(),

            'create' => (function () use ($context, $client) {
                $name = $this->str($context, 'product_name');
                if ($name === '') {
                    throw NodeExecutionException::because('نام محصول (Product Name) الزامی است.');
                }

                $payload = [
                    'name' => $name,
                    'type' => $this->str($context, 'product_type', 'simple'),
                ];

                $payload = $this->populateProductPayload($context, $payload, isCreate: true);

                return $this->singleResult($client->post('products', $payload));
            })(),

            'update' => (function () use ($context, $client) {
                $id = $this->requireId($context, 'product_id', 'شناسه محصول (Product ID) الزامی است.');
                $payload = [];

                if (($name = $this->str($context, 'product_name_optional')) !== '') {
                    $payload['name'] = $name;
                }

                $payload = $this->populateProductPayload($context, $payload, isCreate: false);

                return $this->singleResult($client->put("products/$id", $payload));
            })(),

            default => throw NodeExecutionException::because("عملیات محصول نامعتبر است: $op"),
        };
    }

    private function buildCustomerPayload(NodeContext $context, array $base): array
    {
        $firstName = $this->str($context, 'customer_first_name');
        $lastName = $this->str($context, 'customer_last_name');
        $phone = $this->str($context, 'customer_phone');
        $address = $this->str($context, 'address_line');
        $city = $this->str($context, 'city');
        $state = $this->str($context, 'state');
        $postcode = $this->str($context, 'postcode');

        if ($firstName !== '') {
            $base['first_name'] = $firstName;
        }
        if ($lastName !== '') {
            $base['last_name'] = $lastName;
        }

        $billing = array_filter([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
            'address_1'  => $address,
            'city'       => $city,
            'state'      => $state,
            'postcode'   => $postcode,
        ], fn ($v) => $v !== '');

        if ($billing !== []) {
            $base['billing'] = $billing;
            $shipping = array_diff_key($billing, ['phone' => true]);
            if ($shipping !== []) {
                $base['shipping'] = $shipping;
            }
        }

        return $this->mergeExtraJson($context, $base);
    }

    private function buildOrderAddress(NodeContext $context): array
    {
        return array_filter([
            'first_name' => $this->str($context, 'billing_first_name'),
            'last_name'  => $this->str($context, 'billing_last_name'),
            'phone'      => $this->str($context, 'billing_phone'),
            'email'      => $this->str($context, 'billing_email'),
            'address_1'  => $this->str($context, 'address_line'),
            'city'       => $this->str($context, 'city'),
            'state'      => $this->str($context, 'state'),
            'postcode'   => $this->str($context, 'postcode'),
        ], fn ($v) => $v !== '');
    }

    private function populateProductPayload(NodeContext $context, array $payload, bool $isCreate): array
    {
        if (($regPrice = $this->cleanPrice($this->str($context, 'regular_price'))) !== null) {
            $payload['regular_price'] = $regPrice;
        }

        $saleKey = $isCreate ? 'sale_price_create' : 'sale_price';
        if (($salePrice = $this->cleanPrice($this->str($context, $saleKey))) !== null) {
            $payload['sale_price'] = $salePrice;
        }

        if (($desc = $this->str($context, 'product_description')) !== '') {
            $payload['description'] = $desc;
        }

        if (($shortDesc = $this->str($context, 'short_description')) !== '') {
            $payload['short_description'] = $shortDesc;
        }

        if (($sku = $this->str($context, 'sku')) !== '') {
            $payload['sku'] = $sku;
        }

        if (($status = $this->str($context, 'product_status')) !== '') {
            $payload['status'] = $status;
        }

        $stockKey = $isCreate ? 'stock_quantity_create' : 'stock_quantity';
        $stockRaw = $this->str($context, $stockKey);
        if ($stockRaw !== '' && is_numeric($stockRaw)) {
            $payload['manage_stock'] = true;
            $payload['stock_quantity'] = (int) $stockRaw;
        }

        if (($stockStatus = $this->str($context, 'stock_status')) !== '') {
            $payload['stock_status'] = $stockStatus;
        }

        if (($imageUrls = $this->str($context, 'image_urls')) !== '') {
            $urls = array_values(array_filter(array_map('trim', preg_split('/[\r\n,،]+/', $imageUrls) ?: [])));
            if ($urls !== []) {
                $payload['images'] = array_map(fn (string $u) => ['src' => $u], $urls);
            }
        }

        if (($catIds = $this->str($context, 'category_ids')) !== '') {
            $ids = array_values(array_filter(
                array_map('trim', preg_split('/[,،]+/', $catIds) ?: []),
                fn ($id) => is_numeric($id) && (int) $id > 0
            ));
            if ($ids !== []) {
                $payload['categories'] = array_map(fn ($id) => ['id' => (int) $id], $ids);
            }
        }

        return $this->mergeExtraJson($context, $payload);
    }

    private function parseLineItems(string $raw): array
    {
        if ($raw === '') {
            throw NodeExecutionException::because('اقلام سفارش (Line Items) الزامی است.');
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = isset($decoded['product_id']) ? [$decoded] : $decoded;

            if ($items === []) {
                throw NodeExecutionException::because('لیست اقلام سفارش نمی‌تواند خالی باشد.');
            }

            return array_values($items);
        }

        $items = [];
        $parts = preg_split('/[\r\n,،]+/', $raw) ?: [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            [$prodId, $qty] = array_pad(array_map('trim', explode(':', $part, 2)), 2, '1');

            if (is_numeric($prodId) && (int) $prodId > 0) {
                $items[] = [
                    'product_id' => (int) $prodId,
                    'quantity'   => max(1, (int) ($qty ?: 1)),
                ];
            }
        }

        if ($items === []) {
            throw NodeExecutionException::because('فرمت اقلام سفارش معتبر نیست. یک آرایه JSON مانند [{"product_id": 100, "quantity": 1}] یا فرمت 100:1 وارد کنید.');
        }

        return $items;
    }

    private function mergeExtraJson(NodeContext $context, array $payload): array
    {
        $raw = $this->str($context, 'extra_json');

        if ($raw === '') {
            return $payload;
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw NodeExecutionException::because('ساختار JSON واردشده در بخش «فیلدهای سفارشی بیشتر» معتبر نیست.');
        }

        return array_replace_recursive($payload, $decoded);
    }

    private function paginationQuery(NodeContext $context): array
    {
        return [
            'per_page' => max(1, min(100, (int) ($context->config['limit'] ?? 20))),
            'page'     => max(1, (int) ($context->config['page'] ?? 1)),
        ];
    }

    private function singleResult(array $item): NodeResult
    {
        return NodeResult::of([
            ...$item,
            'raw' => $item,
        ]);
    }

    private function listResult(array $items): NodeResult
    {
        $list = array_is_list($items) ? $items : [$items];

        return NodeResult::of([
            'count' => count($list),
            'items' => $list,
            'rows'  => $list,
            'first' => $list[0] ?? null,
        ]);
    }

    private function str(NodeContext $context, string $key, string $default = ''): string
    {
        return trim((string) ($context->config[$key] ?? $default));
    }

    private function bool(NodeContext $context, string $key, bool $default = false): bool
    {
        $val = $context->config[$key] ?? $default;

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    private function requireId(NodeContext $context, string $key, string $message): int
    {
        $raw = $this->str($context, $key);

        if ($raw === '' || ! is_numeric($raw) || (int) $raw <= 0) {
            throw NodeExecutionException::because($message);
        }

        return (int) $raw;
    }

    private function cleanPrice(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $normalized = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ',' => '', '٬' => '', ' ' => '',
        ]);

        return is_numeric($normalized) ? $normalized : null;
    }
}
