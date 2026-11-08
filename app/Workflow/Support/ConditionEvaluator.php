<?php

namespace App\Workflow\Support;

use Carbon\Carbon;
use Throwable;

final class ConditionEvaluator
{
    public const OPERATORS = [
        'exists',
        'not_exists',
        'empty',
        'not_empty',
        'eq',
        'neq',
        'gt',
        'gte',
        'lt',
        'lte',
        'contains',
        'not_contains',
        'starts_with',
        'not_starts_with',
        'ends_with',
        'not_ends_with',
        'regex',
        'not_regex',
        'is_true',
        'is_false',
        'date_after',
        'date_before',
        'date_after_or_eq',
        'date_before_or_eq',
        'array_contains',
        'array_not_contains',
        'array_length_eq',
        'array_length_neq',
        'array_length_gt',
        'array_length_gte',
        'array_length_lt',
        'array_length_lte',
    ];

    public const UNARY_OPERATORS = [
        'exists',
        'not_exists',
        'empty',
        'not_empty',
        'is_true',
        'is_false',
    ];

    public static function evaluateRules(array $conditions, string $combinator, mixed $contextData): bool
    {
        $results = [];

        foreach ($conditions as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $rawField = trim((string) ($rule['field'] ?? ''));
            $operator = (string) ($rule['operator'] ?? 'eq');
            $expected = $rule['value'] ?? null;
            $dataType = isset($rule['data_type']) && is_string($rule['data_type']) ? $rule['data_type'] : null;

            $actual = self::resolveValue($rawField, $contextData);
            $results[] = self::compare($actual, $operator, $expected, $dataType);
        }

        if ($results === []) {
            return true;
        }

        if ($combinator === 'or') {
            return in_array(true, $results, true);
        }

        return ! in_array(false, $results, true);
    }

    public static function resolveValue(string $rawField, mixed $contextData): mixed
    {
        if ($rawField === '') {
            return null;
        }

        if (! is_array($contextData) && ! is_object($contextData)) {
            return $rawField;
        }

        if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $rawField, $matches)) {
            $expr = trim($matches[1]);
            $cleanPath = preg_replace('/^input\./', '', $expr);

            return data_get($contextData, $cleanPath);
        }

        if (str_starts_with($rawField, 'input.')) {
            $sub = substr($rawField, 6);

            return data_get($contextData, $sub);
        }

        if (is_array($contextData) && array_key_exists($rawField, $contextData)) {
            return $contextData[$rawField];
        }

        if (preg_match('/^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)+$/', $rawField)) {
            $nested = data_get($contextData, $rawField);
            if ($nested !== null) {
                return $nested;
            }
        }

        if (
            (str_starts_with($rawField, '[') && str_ends_with($rawField, ']')) ||
            (str_starts_with($rawField, '{') && str_ends_with($rawField, '}'))
        ) {
            $decoded = json_decode($rawField, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $rawField;
    }

    public static function compare(
        mixed $actual,
        string $operator,
        mixed $expected,
        ?string $dataType = null,
    ): bool {
        return match ($operator) {
            'exists'              => $actual !== null,
            'not_exists'          => $actual === null,
            'empty'               => self::isEmpty($actual),
            'not_empty'           => ! self::isEmpty($actual),
            'is_true'             => self::toBool($actual) === true,
            'is_false'            => self::toBool($actual) === false,
            'date_after'          => self::compareDates($actual, $expected, '>'),
            'date_before'         => self::compareDates($actual, $expected, '<'),
            'date_after_or_eq'    => self::compareDates($actual, $expected, '>='),
            'date_before_or_eq'   => self::compareDates($actual, $expected, '<='),
            'array_contains'      => self::arrayContains($actual, $expected),
            'array_not_contains'  => ! self::arrayContains($actual, $expected),
            'array_length_eq'     => self::compareArrayLength($actual, $expected, '=='),
            'array_length_neq'    => self::compareArrayLength($actual, $expected, '!='),
            'array_length_gt'     => self::compareArrayLength($actual, $expected, '>'),
            'array_length_gte'    => self::compareArrayLength($actual, $expected, '>='),
            'array_length_lt'     => self::compareArrayLength($actual, $expected, '<'),
            'array_length_lte'    => self::compareArrayLength($actual, $expected, '<='),
            default               => self::compareStandard($actual, $operator, $expected, $dataType),
        };
    }

    private static function compareStandard(
        mixed $actual,
        string $operator,
        mixed $expected,
        ?string $dataType,
    ): bool {
        if ($dataType === 'boolean' && in_array($operator, ['eq', 'neq'], true)) {
            $aBool = self::toBool($actual);
            $bBool = self::toBool($expected);

            if ($aBool !== null && $bBool !== null) {
                return $operator === 'eq' ? $aBool === $bBool : $aBool !== $bBool;
            }
        }

        if ($dataType === 'datetime' && in_array($operator, ['eq', 'neq'], true)) {
            return self::compareDates($actual, $expected, $operator === 'eq' ? '==' : '!=');
        }

        if (
            ($dataType === 'number' || (is_numeric($actual) && is_numeric($expected))) &&
            in_array($operator, ['eq', 'neq', 'gt', 'gte', 'lt', 'lte'], true)
        ) {
            if (! is_numeric($actual) || ! is_numeric($expected)) {
                return $operator === 'neq';
            }

            $actualNum = (float) $actual;
            $expectedNum = (float) $expected;

            return match ($operator) {
                'eq'  => $actualNum == $expectedNum,
                'neq' => $actualNum != $expectedNum,
                'gt'  => $actualNum > $expectedNum,
                'gte' => $actualNum >= $expectedNum,
                'lt'  => $actualNum < $expectedNum,
                'lte' => $actualNum <= $expectedNum,
            };
        }

        $actualStr = is_array($actual) || is_object($actual)
            ? (string) json_encode($actual, JSON_UNESCAPED_UNICODE)
            : trim((string) ($actual ?? ''));

        $expectedStr = is_array($expected) || is_object($expected)
            ? (string) json_encode($expected, JSON_UNESCAPED_UNICODE)
            : trim((string) ($expected ?? ''));

        return match ($operator) {
            'eq'              => $actualStr === $expectedStr,
            'neq'             => $actualStr !== $expectedStr,
            'gt'              => strcmp($actualStr, $expectedStr) > 0,
            'gte'             => strcmp($actualStr, $expectedStr) >= 0,
            'lt'              => strcmp($actualStr, $expectedStr) < 0,
            'lte'             => strcmp($actualStr, $expectedStr) <= 0,
            'contains'        => $expectedStr !== '' && mb_stripos($actualStr, $expectedStr) !== false,
            'not_contains'    => $expectedStr === '' || mb_stripos($actualStr, $expectedStr) === false,
            'starts_with'     => $expectedStr !== '' && str_starts_with(mb_strtolower($actualStr), mb_strtolower($expectedStr)),
            'not_starts_with' => $expectedStr === '' || ! str_starts_with(mb_strtolower($actualStr), mb_strtolower($expectedStr)),
            'ends_with'       => $expectedStr !== '' && str_ends_with(mb_strtolower($actualStr), mb_strtolower($expectedStr)),
            'not_ends_with'   => $expectedStr === '' || ! str_ends_with(mb_strtolower($actualStr), mb_strtolower($expectedStr)),
            'regex'           => self::matchesRegex($actualStr, $expectedStr),
            'not_regex'       => ! self::matchesRegex($actualStr, $expectedStr),
            default           => false,
        };
    }

    private static function toBool(mixed $val): ?bool
    {
        if (is_bool($val)) {
            return $val;
        }

        if (is_int($val) || is_float($val)) {
            return $val != 0;
        }

        if (is_string($val)) {
            $clean = strtolower(trim($val));
            if (in_array($clean, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($clean, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return null;
    }

    private static function compareDates(mixed $actual, mixed $expected, string $op): bool
    {
        $strA = trim((string) ($actual ?? ''));
        $strB = trim((string) ($expected ?? ''));

        if ($strA === '' || $strB === '') {
            return false;
        }

        try {
            $tsA = is_numeric($strA) ? (int) $strA : Carbon::parse($strA)->getTimestamp();
            $tsB = is_numeric($strB) ? (int) $strB : Carbon::parse($strB)->getTimestamp();

            return match ($op) {
                '>'  => $tsA > $tsB,
                '<'  => $tsA < $tsB,
                '>=' => $tsA >= $tsB,
                '<=' => $tsA <= $tsB,
                '==' => $tsA === $tsB,
                '!=' => $tsA !== $tsB,
                default => false,
            };
        } catch (Throwable) {
            return false;
        }
    }

    private static function toArrayList(mixed $val): ?array
    {
        if (is_array($val)) {
            return $val;
        }

        if (is_string($val)) {
            $trimmed = trim($val);
            if ($trimmed === '') {
                return [];
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private static function arrayContains(mixed $actual, mixed $expected): bool
    {
        $list = self::toArrayList($actual);

        if ($list === null) {
            return false;
        }

        $needle = trim((string) ($expected ?? ''));

        foreach ($list as $item) {
            if (is_scalar($item) && mb_strtolower(trim((string) $item)) === mb_strtolower($needle)) {
                return true;
            }

            if ((is_array($item) || is_object($item)) && mb_stripos((string) json_encode($item, JSON_UNESCAPED_UNICODE), $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function compareArrayLength(mixed $actual, mixed $expected, string $op): bool
    {
        $list = self::toArrayList($actual);

        if ($list === null || ! is_numeric($expected)) {
            return false;
        }

        $len = count($list);
        $target = (int) $expected;

        return match ($op) {
            '==' => $len === $target,
            '!=' => $len !== $target,
            '>'  => $len > $target,
            '>=' => $len >= $target,
            '<'  => $len < $target,
            '<=' => $len <= $target,
            default => false,
        };
    }

    private static function isEmpty(mixed $val): bool
    {
        if ($val === null || $val === false || $val === []) {
            return true;
        }

        if (is_string($val)) {
            $trimmed = trim($val);

            return $trimmed === '' || $trimmed === '[]' || $trimmed === '{}';
        }

        if (is_object($val)) {
            return count((array) $val) === 0;
        }

        return false;
    }

    private static function matchesRegex(string $subject, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        $delimited = preg_match('/^[\/#~].*[\/#~][imsxADSUXJu]*$/', $pattern)
            ? $pattern
            : '/' . str_replace('/', '\/', $pattern) . '/u';

        return @preg_match($delimited, $subject) === 1;
    }
}
