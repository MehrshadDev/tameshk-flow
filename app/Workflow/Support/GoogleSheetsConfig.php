<?php

namespace App\Workflow\Support;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\Exceptions\NodeExecutionException;

final class GoogleSheetsConfig
{
    public static function schema(): array
    {
        return [
            'spreadsheet_id' => [
                'type'        => 'dynamic_select',
                'source'      => 'google_sheets_documents',
                'label'       => 'سند (Spreadsheet)',
                'required'    => true,
                'placeholder' => 'https://docs.google.com/spreadsheets/d/...',
            ],
            'sheet' => [
                'type'        => 'dynamic_select',
                'source'      => 'google_sheets_sheets',
                'label'       => 'برگه (Sheet)',
                'required'    => true,
                'placeholder' => 'Sheet1',
            ],
        ];
    }

    public static function resolveSpreadsheet(NodeContext|array $source): string
    {
        $config = self::extractConfig($source);
        $value = trim((string) (
            $config['spreadsheet_id']
            ?? $config['spreadsheet_url']
            ?? ''
        ));

        if ($value === '') {
            throw NodeExecutionException::because('سند (اسپردشیت) انتخاب نشده است.');
        }

        return GoogleSheetsClient::spreadsheetId($value);
    }

    public static function resolveSheet(NodeContext|array $source): string
    {
        $config = self::extractConfig($source);
        $value = trim((string) (
            $config['sheet']
            ?? $config['sheet_ref']
            ?? ''
        ));

        if ($value === '') {
            throw NodeExecutionException::because('برگه (شیت) انتخاب نشده است.');
        }

        return $value;
    }

    public static function rangeFor(NodeContext|array $source, ?string $key, string $default): string
    {
        $config = self::extractConfig($source);
        $sheet = self::resolveSheet($config);
        $parts = explode('!', $sheet, 2);
        $sheetName = trim($parts[0], "'");
        $embedded = $parts[1] ?? '';

        $range = $key !== null ? trim((string) ($config[$key] ?? '')) : '';

        if ($range === '') {
            $range = $embedded !== '' ? $embedded : $default;
        }

        if (str_contains($range, '!')) {
            return $range;
        }

        $quoted = preg_match('/^[A-Za-z0-9_]+$/', $sheetName) === 1
            ? $sheetName
            : "'" . str_replace("'", "''", $sheetName) . "'";

        return $quoted . '!' . $range;
    }

    public static function normalizeRow(array $row): array
    {
        return array_map(function ($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            if (is_bool($value)) {
                return $value ? 'TRUE' : 'FALSE';
            }

            return $value === null ? '' : (string) $value;
        }, array_values($row));
    }

    public static function combineRow(array $headers, array $row): array
    {
        $result = [];

        foreach ($headers as $index => $header) {
            $key = trim((string) $header);
            $key = $key !== '' ? $key : 'column_' . ($index + 1);
            $result[$key] = $row[$index] ?? '';
        }

        return $result;
    }

    private static function extractConfig(NodeContext|array $source): array
    {
        return $source instanceof NodeContext ? $source->config : $source;
    }
}
