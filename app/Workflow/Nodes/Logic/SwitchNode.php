<?php

namespace App\Workflow\Nodes\Logic;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\ConditionEvaluator;

class SwitchNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'logic.switch',
            name: 'Switch',
            category: NodeCategory::Logic,
            description: 'مسیریابی چندشاخه‌ای پویا بر اساس قوانین شرطی (مشابه n8n) با قابلیت افزودن و نام‌گذاری خروجی‌ها.',
            icon: 'git-branch',
            inputs: [HandleType::Main->value],
            outputs: [
                HandleType::Case1->value,
                HandleType::Default->value,
            ],
            configSchema: [
                'rules' => [
                    'type'    => 'switch_rules',
                    'label'   => 'قوانین مسیریابی (Routing Rules)',
                    'default' => [
                        [
                            'id'            => 'case_1',
                            'data_type'     => 'string',
                            'field'         => '',
                            'operator'      => 'eq',
                            'value'         => '',
                            'rename_output' => false,
                            'output_name'   => '',
                        ],
                    ],
                ],
                'fallback_output' => [
                    'type'    => 'select',
                    'label'   => 'مسیر پیش‌فرض (Fallback Output)',
                    'options' => [
                        ['value' => 'extra', 'label' => 'ارسال به خروجی «پیش‌فرض» در صورت عدم تطابق'],
                        ['value' => 'none', 'label' => 'توقف مسیر (بدون خروجی پیش‌فرض)'],
                    ],
                    'default'  => 'extra',
                    'advanced' => true,
                ],
            ],
            color: 'orange',
        );
    }

    public static function allowedOutputsForConfig(array $config): array
    {
        $handles = [];
        $rules = $config['rules'] ?? null;

        if (is_array($rules) && $rules !== []) {
            foreach (array_values($rules) as $index => $rule) {
                $id = is_array($rule) && ! empty($rule['id']) && is_string($rule['id'])
                    ? trim($rule['id'])
                    : 'case_' . ($index + 1);

                if (preg_match('/^case_\d+$/', $id)) {
                    $handles[] = $id;
                }
            }
        } else {
            $handles = [
                HandleType::Case1->value,
                HandleType::Case2->value,
                HandleType::Case3->value,
            ];
        }

        if (($config['fallback_output'] ?? 'extra') !== 'none') {
            $handles[] = HandleType::Default->value;
        }

        return array_values(array_unique($handles));
    }

    public function execute(NodeContext $context): NodeResult
    {
        $rules = $this->resolveRules($context->config);
        $fallbackMode = (string) ($context->config['fallback_output'] ?? 'extra');

        $matchedHandle = null;
        $matchedLabel = null;

        foreach ($rules as $index => $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $handle = ! empty($rule['id']) && is_string($rule['id'])
                ? trim($rule['id'])
                : 'case_' . ($index + 1);

            $rawField = trim((string) ($rule['field'] ?? ''));
            $operator = (string) ($rule['operator'] ?? 'eq');
            $expected = $rule['value'] ?? '';
            $dataType = isset($rule['data_type']) && is_string($rule['data_type']) ? $rule['data_type'] : null;

            if ($rawField === '' && ! in_array($operator, ConditionEvaluator::UNARY_OPERATORS, true) && trim((string) $expected) === '') {
                continue;
            }

            $actual = ConditionEvaluator::resolveValue($rawField, $context->input);

            if (ConditionEvaluator::compare($actual, $operator, $expected, $dataType)) {
                $matchedHandle = $handle;
                $customName = ! empty($rule['rename_output']) ? trim((string) ($rule['output_name'] ?? '')) : '';
                $matchedLabel = $customName !== '' ? $customName : 'حالت ' . ($index + 1);
                break;
            }
        }

        if ($matchedHandle === null) {
            if ($fallbackMode === 'none') {
                $context->log('مسیریابی Switch ← هیچ قانونی صدق نکرد (توقف مسیر)');

                return NodeResult::of(
                    output: is_array($context->input)
                        ? [...$context->input, '_matched_case' => null]
                        : ['value' => $context->input, '_matched_case' => null],
                    handle: HandleType::Stopped->value,
                    meta: ['taken_handle' => HandleType::Stopped->value],
                );
            }

            $matchedHandle = HandleType::Default->value;
            $matchedLabel = 'پیش‌فرض';
        }

        $context->log("مسیریابی Switch → $matchedLabel ($matchedHandle)");

        return NodeResult::of(
            output: is_array($context->input)
                ? [...$context->input, '_matched_case' => $matchedHandle, '_matched_label' => $matchedLabel]
                : ['value' => $context->input, '_matched_case' => $matchedHandle, '_matched_label' => $matchedLabel],
            handle: $matchedHandle,
            meta: [
                'taken_handle' => $matchedHandle,
                'taken_label'  => $matchedLabel,
            ],
        );
    }

    private function resolveRules(array $config): array
    {
        if (! empty($config['rules']) && is_array($config['rules'])) {
            return array_values($config['rules']);
        }

        $valueToMatch = trim((string) ($config['value_to_match'] ?? ''));
        $operator = (string) ($config['operator'] ?? 'eq');
        $legacyRules = [];

        foreach (['case_1', 'case_2', 'case_3'] as $caseKey) {
            $expected = trim((string) ($config[$caseKey] ?? ''));
            if ($expected !== '') {
                $legacyRules[] = [
                    'id'        => $caseKey,
                    'data_type' => 'string',
                    'field'     => $valueToMatch,
                    'operator'  => $operator,
                    'value'     => $expected,
                ];
            }
        }

        return $legacyRules;
    }
}
