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
        return (string) preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) use ($context) {
            $result = self::evaluateExpression(trim($matches[1]), $context);

            if ($result === null) {
                return '';
            }

            return self::formatValue($result);
        }, $template);
    }

    private static function evaluateExpression(string $rawExpr, array $context): mixed
    {
        $expr = self::stripOuterParens(trim($rawExpr));

        if ($expr === '') {
            return null;
        }

        $chain = self::splitFallbackChain($expr);

        if (count($chain) === 1) {
            return self::evaluateSingle($chain[0]['expr'], $context);
        }

        $lastValue = null;

        foreach ($chain as $segment) {
            $val = self::evaluateExpression($segment['expr'], $context);
            $op = $segment['op'];

            if ($op === null) {
                return $val;
            }

            if ($op === '??') {
                if ($val !== null) {
                    return $val;
                }
            } elseif (! self::isFalsy($val)) {
                return $val;
            }

            $lastValue = $val;
        }

        return $lastValue;
    }

    private static function evaluateSingle(string $rawExpr, array $context): mixed
    {
        $expr = self::stripOuterParens(trim($rawExpr));

        if ($expr === '') {
            return null;
        }

        [$isLiteral, $literalValue] = self::resolveLiteral($expr);

        if ($isLiteral) {
            return $literalValue;
        }

        $direct = self::resolveVariable($expr, $context);

        if ($direct !== null) {
            return $direct;
        }

        $mathResult = self::tryEvaluateMath($expr, $context);

        if ($mathResult !== null) {
            return $mathResult;
        }

        return null;
    }

    private static function splitFallbackChain(string $expr): array
    {
        $len = strlen($expr);
        $segments = [];
        $current = '';
        $quote = null;
        $parenDepth = 0;
        $bracketDepth = 0;

        for ($i = 0; $i < $len; $i++) {
            $ch = $expr[$i];

            if ($quote !== null) {
                $current .= $ch;
                if ($ch === '\\' && $i + 1 < $len) {
                    $current .= $expr[++$i];
                } elseif ($ch === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($ch === "'" || $ch === '"') {
                $quote = $ch;
                $current .= $ch;
                continue;
            }

            if ($ch === '(') {
                $parenDepth++;
                $current .= $ch;
                continue;
            }

            if ($ch === ')' && $parenDepth > 0) {
                $parenDepth--;
                $current .= $ch;
                continue;
            }

            if ($ch === '[') {
                $bracketDepth++;
                $current .= $ch;
                continue;
            }

            if ($ch === ']' && $bracketDepth > 0) {
                $bracketDepth--;
                $current .= $ch;
                continue;
            }

            if ($parenDepth === 0 && $bracketDepth === 0) {
                $next = $expr[$i + 1] ?? '';

                if ($ch === '|' && $next === '|') {
                    $segments[] = ['expr' => trim($current), 'op' => '||'];
                    $current = '';
                    $i++;
                    continue;
                }

                if ($ch === '?' && $next === '?') {
                    $segments[] = ['expr' => trim($current), 'op' => '??'];
                    $current = '';
                    $i++;
                    continue;
                }
            }

            $current .= $ch;
        }

        $segments[] = ['expr' => trim($current), 'op' => null];

        return $segments;
    }

    private static function stripOuterParens(string $expr): string
    {
        while (strlen($expr) >= 2 && $expr[0] === '(' && $expr[strlen($expr) - 1] === ')') {
            $len = strlen($expr);
            $depth = 0;
            $quote = null;
            $wrapsAll = true;

            for ($i = 0; $i < $len; $i++) {
                $ch = $expr[$i];

                if ($quote !== null) {
                    if ($ch === '\\' && $i + 1 < $len) {
                        $i++;
                    } elseif ($ch === $quote) {
                        $quote = null;
                    }
                    continue;
                }

                if ($ch === "'" || $ch === '"') {
                    $quote = $ch;
                    continue;
                }

                if ($ch === '(') {
                    $depth++;
                } elseif ($ch === ')') {
                    $depth--;
                    if ($depth === 0 && $i < $len - 1) {
                        $wrapsAll = false;
                        break;
                    }
                }
            }

            if (! $wrapsAll || $depth !== 0) {
                break;
            }

            $expr = trim(substr($expr, 1, -1));
        }

        return $expr;
    }

    private static function resolveLiteral(string $expr): array
    {
        if (preg_match("/^'((?:\\\\.|[^'\\\\])*)'$/s", $expr, $m)) {
            return [true, str_replace(["\\'", '\\\\'], ["'", '\\'], $m[1])];
        }

        if (preg_match('/^"((?:\\\\.|[^"\\\\])*)"$/s', $expr, $m)) {
            return [true, str_replace(['\"', '\\\\', '\n', '\r', '\t'], ['"', '\\', "\n", "\r", "\t"], $m[1])];
        }

        $lower = strtolower($expr);

        if ($lower === 'null' || $lower === 'undefined') {
            return [true, null];
        }

        if ($lower === 'true') {
            return [true, true];
        }

        if ($lower === 'false') {
            return [true, false];
        }

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $expr)) {
            return [true, str_contains($expr, '.') ? (float) $expr : (int) $expr];
        }

        return [false, null];
    }

    private static function isFalsy(mixed $val): bool
    {
        if ($val === null || $val === false || $val === '' || $val === [] || $val === 0 || $val === 0.0) {
            return true;
        }

        return is_string($val) && trim($val) === '';
    }

    private static function resolveVariable(string $rawExpr, array $context): mixed
    {
        $rawExpr = trim($rawExpr);

        if (preg_match(
            '/^\$\([\'"](.+?)[\'"]\)(?:\.(?:item|first\(\)|last\(\)))?(?:\.json)?((?:\.[^\s+\-*\/%^()|?\[\]]+|\[\d+\]|\[[\'"][^\'"]+[\'"]\])*)$/u',
            $rawExpr,
            $nodeMatch
        )) {
            $nodeName = $nodeMatch[1];
            $propPath = $nodeMatch[2] ?? '';
            $nodes = is_array($context['nodes'] ?? null) ? $context['nodes'] : [];
            $underscored = str_replace(' ', '_', $nodeName);

            if (array_key_exists($nodeName, $nodes)) {
                $nodeData = $nodes[$nodeName];
            } elseif (array_key_exists($underscored, $nodes)) {
                $nodeData = $nodes[$underscored];
            } elseif (array_key_exists($nodeName, $context)) {
                $nodeData = $context[$nodeName];
            } else {
                return null;
            }

            return $propPath !== '' ? self::getByPath($nodeData, $propPath) : $nodeData;
        }

        if (preg_match(
            '/^\$(?:json|input(?:\.(?:item|first\(\)|last\(\)))?(?:\.json)?)((?:\.[^\s+\-*\/%^()|?\[\]]+|\[\d+\]|\[[\'"][^\'"]+[\'"]\])*)$/u',
            $rawExpr,
            $jsonMatch
        )) {
            $propPath = $jsonMatch[1] ?? '';

            return $propPath !== ''
                ? self::getByPath($context['input'] ?? null, $propPath)
                : ($context['input'] ?? null);
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
        if ($target === null) {
            return null;
        }

        if ($path === '') {
            return $target;
        }

        $normalized = preg_replace('/\[(\d+)\]/', '.$1', $path) ?? $path;
        $normalized = preg_replace('/\[[\'"]([^\'"]+)[\'"]\]/', '.$1', $normalized) ?? $normalized;
        $normalized = ltrim($normalized, '.');

        if ($normalized === '') {
            return $target;
        }

        return data_get($target, $normalized);
    }

    private static function tryEvaluateMath(string $expr, array $context): float|int|null
    {
        if (! preg_match('/[+\-*\/%^()]|\b(?:sqrt|abs|round|floor|ceil|sin|cos|min|max)\s*\(/i', $expr)) {
            return null;
        }

        $unresolved = false;

        $resolvedExpr = preg_replace_callback(
            '/\$\([\'"][^\'"]+[\'"]\)(?:\.(?:item|first\(\)|last\(\)))?(?:\.json)?(?:\.[a-zA-Z0-9_.\[\]]+|\[[\'"][^\'"]+[\'"]\])*/',
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
            '/\$(?:json|input(?:\.(?:item|first\(\)|last\(\)))?(?:\.json)?)(?:\.[a-zA-Z0-9_.\[\]]+|\[[\'"][^\'"]+[\'"]\])*/',
            function (array $m) use ($context, &$unresolved): string {
                $num = self::toNumericString(self::resolveVariable($m[0], $context));
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

        $resolvedExpr = preg_replace_callback(
            '/\b([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+|\[\d+\])*)/',
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

        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }

        return (string) ($val ?? '');
    }
}
