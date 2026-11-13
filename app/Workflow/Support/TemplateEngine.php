<?php

namespace App\Workflow\Support;

use Throwable;

final class TemplateEngine
{
    private const MATH_FUNCTIONS = ['sqrt', 'abs', 'round', 'floor', 'ceil', 'sin', 'cos', 'min', 'max'];

    public static function render(mixed $value, array $context): mixed
    {
        if (is_string($value)) {
            return self::interpolateString($value, $context);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = self::render($v, $context);
            }
            return $result;
        }

        return $value;
    }

    private static function interpolateString(string $template, array $context): string
    {
        return (string) preg_replace_callback('/\{\{\s*(.+?)\s*}}/', function ($matches) use ($context) {
            $rawExpr = trim($matches[1]);

            $direct = self::resolveVariable($rawExpr, $context);
            if ($direct !== null) {
                return self::formatValue($direct);
            }

            $mathResult = self::tryEvaluateMath($rawExpr, $context);
            if ($mathResult !== null) {
                return (string) $mathResult;
            }

            return '';
        }, $template);
    }

    private static function resolveVariable(string $rawExpr, array $context): mixed
    {
        $rawExpr = trim($rawExpr);

        if (preg_match('/^\$\([\'"](.+?)[\'"]\)(?:\.item)?(?:\.json)?(?:\.(.+))?$/', $rawExpr, $nodeMatch)) {
            $nodeName = $nodeMatch[1];
            $propPath = $nodeMatch[2] ?? '';

            $nodeData = data_get($context, "nodes.$nodeName")
                ?? data_get($context, 'nodes.' . str_replace(' ', '_', $nodeName))
                ?? data_get($context, $nodeName)
                ?? [];

            return $propPath !== '' ? self::getByPath($nodeData, $propPath) : $nodeData;
        }

        if (str_starts_with($rawExpr, 'nodes.')) {
            $sub = substr($rawExpr, 6);
            $val = self::getByPath($context['nodes'] ?? [], $sub);
            if ($val !== null) {
                return $val;
            }
        }

        if (str_starts_with($rawExpr, 'trigger.')) {
            $sub = substr($rawExpr, 8);
            $val = self::getByPath($context['trigger'] ?? [], $sub);
            if ($val !== null) {
                return $val;
            }
        }

        $cleanKey = preg_replace('/^input\./', '', $rawExpr);
        $val = self::getByPath($context['input'] ?? null, $cleanKey);

        if ($val === null) {
            $val = self::getByPath($context, $rawExpr);
        }

        if ($val === null) {
            $val = self::getByPath($context['trigger'] ?? null, $cleanKey);
        }

        if ($val === null && isset($context['nodes']) && is_array($context['nodes'])) {
            foreach ($context['nodes'] as $nodeOutput) {
                $candidate = self::getByPath($nodeOutput, $cleanKey);
                if ($candidate !== null) {
                    return $candidate;
                }
            }
        }

        return $val;
    }

    private static function getByPath(mixed $target, string $path): mixed
    {
        if ($path === '') {
            return $target;
        }

        $normalized = preg_replace('/\[(\d+)]/', '.$1', $path) ?? $path;
        $normalized = ltrim($normalized, '.');

        return data_get($target, $normalized);
    }

    private static function tryEvaluateMath(string $expr, array $context): float|int|null
    {
        if (! preg_match('/[+\-*\/%^()]|\b(?:sqrt|abs|round|floor|ceil|sin|cos|min|max)\s*\(/i', $expr)) {
            return null;
        }

        $unresolved = false;

        $resolvedExpr = preg_replace_callback(
            '/\$\([\'"][^\'"]+[\'"]\)(?:\.item)?(?:\.json)?(?:\.[a-zA-Z0-9_.\[\]]+)?/',
            function (array $m) use ($context, &$unresolved): string {
                $num = self::toNumericString(self::resolveVariable($m[0], $context));
                if ($num === null) {
                    $unresolved = true;
                    return '';
                }
                return $num;
            },
            $expr
        ) ?? $expr;

        if ($unresolved) {
            return null;
        }

        $resolvedExpr = preg_replace_callback(
            '/\b([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+|\[\d+])*)/',
            function (array $m) use ($context, &$unresolved): string {
                $token = $m[1];

                if (in_array(strtolower($token), self::MATH_FUNCTIONS, true)) {
                    return strtolower($token);
                }

                $num = self::toNumericString(self::resolveVariable($token, $context));
                if ($num === null) {
                    $unresolved = true;
                    return '';
                }

                return $num;
            },
            $resolvedExpr
        ) ?? $resolvedExpr;

        if ($unresolved) {
            return null;
        }

        try {
            return MathEvaluator::evaluate($resolvedExpr);
        } catch (Throwable) {
            return null;
        }
    }

    private static function toNumericString(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ',' => '', '٬' => '', ' ' => '',
        ]);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return $normalized;
    }

    private static function formatValue(mixed $val): string
    {
        if (is_array($val) || is_object($val)) {
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        }

        return (string) ($val ?? '');
    }
}
