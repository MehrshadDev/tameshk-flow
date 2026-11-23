<?php

namespace App\Workflow\Nodes\Triggers;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\DateTimeHelper;
use Carbon\Carbon;

class FormTriggerNode implements NodeContract, PublishableTriggerContract
{
    public static function defaultFields(): array
    {
        return [
            [
                'name'        => 'name',
                'label'       => 'نام و نام خانوادگی',
                'type'        => 'text',
                'placeholder' => 'مثلاً: علی محمدی',
                'required'    => true,
                'options'     => '',
            ],
            [
                'name'        => 'email',
                'label'       => 'آدرس ایمیل',
                'type'        => 'email',
                'placeholder' => 'example@gmail.com',
                'required'    => true,
                'options'     => '',
            ],
            [
                'name'        => 'message',
                'label'       => 'متن پیام یا درخواست',
                'type'        => 'textarea',
                'placeholder' => 'پیام خود را اینجا بنویسید...',
                'required'    => true,
                'options'     => '',
            ],
        ];
    }

    public static function normalizedFields(array $config): array
    {
        $raw = $config['fields'] ?? null;

        if (! is_array($raw) || $raw === []) {
            return self::defaultFields();
        }

        $allowedTypes = ['text', 'email', 'tel', 'number', 'textarea', 'select'];
        $fields = [];
        $usedNames = [];

        foreach (array_values($raw) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim(strip_tags((string) ($item['label'] ?? '')));
            $rawName = trim((string) ($item['name'] ?? ''));
            $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '_', $rawName) ?? '';
            $cleanName = trim($cleanName, '_');

            if ($cleanName === '') {
                $cleanName = 'field_' . ($index + 1);
            }

            if (isset($usedNames[$cleanName])) {
                $cleanName .= '_' . ($index + 1);
            }

            $usedNames[$cleanName] = true;

            if ($label === '') {
                $label = $cleanName;
            }

            $type = (string) ($item['type'] ?? 'text');
            if (! in_array($type, $allowedTypes, true)) {
                $type = 'text';
            }

            $optionsRaw = trim((string) ($item['options'] ?? ''));
            $optionsList = $type === 'select' && $optionsRaw !== ''
                ? array_values(array_filter(array_map('trim', preg_split('/[,،\r\n]+/u', $optionsRaw) ?: [])))
                : [];

            $fields[] = [
                'name'         => $cleanName,
                'label'        => mb_substr($label, 0, 100),
                'type'         => $type,
                'placeholder'  => mb_substr(trim(strip_tags((string) ($item['placeholder'] ?? ''))), 0, 150),
                'required'     => filter_var($item['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'options'      => $optionsRaw,
                'options_list' => $optionsList,
            ];
        }

        return $fields !== [] ? $fields : self::defaultFields();
    }

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'trigger.form',
            name: 'On Form Submission',
            category: NodeCategory::Trigger,
            description: 'ساخت فرم آنلاین عمومی و اجرای خودکار ورکفلو به محض پر شدن و ارسال فرم توسط کاربر.',
            icon: 'clipboard-list',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'title' => [
                    'type'        => 'text',
                    'label'       => 'عنوان فرم',
                    'default'     => 'فرم تماس با ما',
                    'placeholder' => 'فرم تماس با ما',
                    'required'    => true,
                ],
                'description' => [
                    'type'        => 'textarea',
                    'label'       => 'توضیحات بالای فرم (اختیاری)',
                    'default'     => 'لطفاً اطلاعات و پیام خود را وارد کنید؛ در کوتاه‌ترین زمان ممکن پاسخگوی شما خواهیم بود.',
                    'placeholder' => 'توضیح کوتاهی برای راهنمایی تکمیل‌کننده فرم بنویسید...',
                    'required'    => false,
                ],
                'fields' => [
                    'type'    => 'form_fields',
                    'label'   => 'فیلدهای فرم (Form Fields)',
                    'default' => self::defaultFields(),
                ],
                'submit_label' => [
                    'type'        => 'text',
                    'label'       => 'متن دکمه ارسال',
                    'default'     => 'ارسال پیام',
                    'placeholder' => 'ارسال پیام',
                    'advanced'    => true,
                ],
                'response_message' => [
                    'type'        => 'textarea',
                    'label'       => 'پیام تشکر پس از ثبت فرم',
                    'default'     => 'پیام شما با موفقیت ثبت شد. از همراهی شما سپاسگزاریم!',
                    'placeholder' => 'پیام شما با موفقیت ثبت شد.',
                    'advanced'    => true,
                ],
            ],
            color: 'emerald',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['submitted_at'], $input['persian_date'])) {
            $context->log('دریافت اطلاعات فرم');

            return NodeResult::of($input);
        }

        $fields = self::normalizedFields($context->config);
        $cleanValues = [];

        foreach ($fields as $field) {
            $key = $field['name'];
            $val = $input[$key] ?? '';
            $cleanValues[$key] = is_scalar($val) ? trim((string) $val) : '';
        }

        $context->log('دریافت اطلاعات فرم');

        return NodeResult::of(self::buildSubmissionPayload($cleanValues));
    }

    public static function buildSubmissionPayload(array $submittedValues, ?string $ip = null): array
    {
        $now = Carbon::now('Asia/Tehran');
        $persianDate = DateTimeHelper::toPersianDate($now, 'yyyy/MM/dd HH:mm');

        $payload = [
            ...$submittedValues,
            'submitted_at' => $now->toIso8601String(),
            'persian_date' => $persianDate,
        ];

        if ($ip !== null) {
            $payload['ip'] = $ip;
        }

        return $payload;
    }
}
