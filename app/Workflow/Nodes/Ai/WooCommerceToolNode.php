<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\WooCommerceClient;

class WooCommerceToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.woocommerce',
            name: 'WooCommerce Tool',
            category: NodeCategory::Ai,
            description: 'ابزار جستجو و مدیریت محصولات، سفارش‌ها و مشتریان فروشگاه ووکامرس توسط ایجنت هوش مصنوعی.',
            icon: 'brand:woocommerce',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'woocommerce',
                    'label'           => 'اعتبارنامه فروشگاه ووکامرس',
                    'required'        => true,
                ],
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'woocommerce_store',
                    'required'    => true,
                    'placeholder' => 'woocommerce_store',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'جستجو و بررسی موجودی و قیمت محصولات، استعلام وضعیت سفارش‌ها، ثبت سفارش جدید، به‌روزرسانی وضعیت سفارش یا تغییر قیمت و موجودی محصول در فروشگاه ووکامرس.',
                    'required' => true,
                ],
                'allowed_scope' => [
                    'type'     => 'select',
                    'label'    => 'دسترسی مجاز ایجنت',
                    'options'  => [
                        ['value' => 'all', 'label' => 'همه عملیات‌ها (خواندن و ویرایش محصولات، سفارش‌ها و مشتریان)'],
                        ['value' => 'read_only', 'label' => 'فقط خواندن و جستجو (بدون اجازه ثبت یا ویرایش)'],
                    ],
                    'default'  => 'all',
                    'required' => true,
                ],
            ],
            color: 'violet',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'woocommerce_store'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'مدیریت محصولات، سفارش‌ها و مشتریان در ووکامرس'));
    }

    public function toolParameters(array $config): array
    {
        $readOnly = ($config['allowed_scope'] ?? 'all') === 'read_only';

        $actions = $readOnly
            ? '"search_products" (جستجوی محصولات)، "get_product" (دریافت محصول با id)، "get_order" (دریافت سفارش با id)، "search_orders" (جستجوی سفارش‌ها)، "search_customers" (جستجوی مشتریان)'
            : '"search_products" (جستجوی محصولات)، "get_product" (دریافت محصول با id)، "update_product" (تغییر قیمت یا موجودی محصول با id)، "get_order" (دریافت سفارش با id)، "search_orders" (جستجوی سفارش‌ها)، "create_order" (ثبت سفارش جدید)، "update_order_status" (تغییر وضعیت سفارش با id)، "search_customers" (جستجوی مشتریان)';

        $properties = [
            'action' => [
                'type'        => 'STRING',
                'description' => "نوع عملیات: $actions",
            ],
            'id' => [
                'type'        => 'INTEGER',
                'description' => 'شناسه عددی سفارش، محصول یا مشتری (برای عملیات‌های تک‌آیتمی)',
            ],
            'search' => [
                'type'        => 'STRING',
                'description' => 'عبارت جستجو برای پیدا کردن محصول، سفارش یا مشتری',
            ],
        ];

        if (! $readOnly) {
            $properties['status'] = [
                'type'        => 'STRING',
                'description' => 'وضعیت جدید سفارش (pending, processing, on-hold, completed, cancelled)',
            ];
            $properties['regular_price'] = [
                'type'        => 'STRING',
                'description' => 'قیمت جدید محصول (برای update_product)',
            ];
            $properties['stock_quantity'] = [
                'type'        => 'INTEGER',
                'description' => 'موجودی انبار جدید محصول (برای update_product)',
            ];
            $properties['line_items_json'] = [
                'type'        => 'STRING',
                'description' => 'آرایه JSON اقلام برای ثبت سفارش جدید؛ مثل [{"product_id": 12, "quantity": 1}]',
            ];
            $properties['customer_name'] = [
                'type'        => 'STRING',
                'description' => 'نام مشتری برای ثبت سفارش جدید',
            ];
            $properties['customer_phone'] = [
                'type'        => 'STRING',
                'description' => 'شماره تماس مشتری برای ثبت سفارش جدید',
            ];
        }

        return [
            'type'       => 'OBJECT',
            'properties' => $properties,
            'required'   => ['action'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه ووکامرس برای ابزار WooCommerce انتخاب نشده است.');
        }

        $client = new WooCommerceClient($credential);
        $readOnly = ($context->config['allowed_scope'] ?? 'all') === 'read_only';
        $action = strtolower(trim((string) ($arguments['action'] ?? 'search_products')));
        $id = (int) ($arguments['id'] ?? 0);
        $search = trim((string) ($arguments['search'] ?? ''));

        if ($readOnly && in_array($action, ['update_product', 'create_order', 'update_order_status'], true)) {
            return ['error' => 'این ابزار روی حالت «فقط خواندن» تنظیم شده و اجازه تغییر یا ثبت داده ندارد.'];
        }

        return match ($action) {
            'search_products' => $this->summarizeProducts($client->get('products', array_filter([
                'search'   => $search,
                'per_page' => 10,
            ]))),

            'get_product' => $id > 0
                ? $client->get("products/$id")
                : ['error' => 'شناسه محصول (id) الزامی است.'],

            'update_product' => (function () use ($client, $id, $arguments) {
                if ($id <= 0) {
                    return ['error' => 'شناسه محصول (id) الزامی است.'];
                }
                $payload = [];
                if (! empty($arguments['regular_price'])) {
                    $payload['regular_price'] = (string) $arguments['regular_price'];
                }
                if (isset($arguments['stock_quantity']) && is_numeric($arguments['stock_quantity'])) {
                    $payload['manage_stock'] = true;
                    $payload['stock_quantity'] = (int) $arguments['stock_quantity'];
                }
                return $client->put("products/$id", $payload);
            })(),

            'get_order' => $id > 0
                ? $client->get("orders/$id")
                : ['error' => 'شناسه سفارش (id) الزامی است.'],

            'search_orders' => $this->summarizeOrders($client->get('orders', array_filter([
                'search'   => $search,
                'per_page' => 10,
            ]))),

            'update_order_status' => (function () use ($client, $id, $arguments) {
                if ($id <= 0 || empty($arguments['status'])) {
                    return ['error' => 'برای تغییر وضعیت سفارش، فیلدهای id و status الزامی هستند.'];
                }
                return $client->put("orders/$id", ['status' => (string) $arguments['status']]);
            })(),

            'create_order' => (function () use ($client, $arguments) {
                $items = json_decode((string) ($arguments['line_items_json'] ?? '[]'), true);
                if (! is_array($items) || $items === []) {
                    return ['error' => 'فیلد line_items_json معتبر الزامی است.'];
                }
                $payload = [
                    'status'     => (string) ($arguments['status'] ?? 'processing'),
                    'line_items' => $items,
                    'billing'    => array_filter([
                        'first_name' => (string) ($arguments['customer_name'] ?? ''),
                        'phone'      => (string) ($arguments['customer_phone'] ?? ''),
                    ]),
                ];
                return $client->post('orders', $payload);
            })(),

            'search_customers' => $client->get('customers', array_filter([
                'search'   => $search,
                'per_page' => 10,
            ])),

            default => ['error' => "عملیات ناشناخته ووکامرس: $action"],
        };
    }

    private function summarizeProducts(array $products): array
    {
        $items = [];
        foreach ($products as $p) {
            if (! is_array($p)) {
                continue;
            }
            $items[] = [
                'id'             => $p['id'] ?? null,
                'name'           => $p['name'] ?? '',
                'sku'            => $p['sku'] ?? '',
                'price'          => $p['price'] ?? '',
                'regular_price'  => $p['regular_price'] ?? '',
                'sale_price'     => $p['sale_price'] ?? '',
                'stock_status'   => $p['stock_status'] ?? '',
                'stock_quantity' => $p['stock_quantity'] ?? null,
                'permalink'      => $p['permalink'] ?? '',
            ];
        }

        return ['count' => count($items), 'products' => $items];
    }

    private function summarizeOrders(array $orders): array
    {
        $items = [];
        foreach ($orders as $o) {
            if (! is_array($o)) {
                continue;
            }
            $items[] = [
                'id'           => $o['id'] ?? null,
                'status'       => $o['status'] ?? '',
                'total'        => $o['total'] ?? '',
                'currency'     => $o['currency'] ?? '',
                'date_created' => $o['date_created'] ?? '',
                'billing_name' => trim(($o['billing']['first_name'] ?? '') . ' ' . ($o['billing']['last_name'] ?? '')),
                'phone'        => $o['billing']['phone'] ?? '',
            ];
        }

        return ['count' => count($items), 'orders' => $items];
    }
}
