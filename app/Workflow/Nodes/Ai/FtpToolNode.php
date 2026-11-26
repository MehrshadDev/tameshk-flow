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
use App\Workflow\Support\FtpClient;

class FtpToolNode implements NodeContract, ToolContract
{
    private const MAX_AGENT_TEXT_CHARS = 16000;

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.ftp',
            name: 'FTP Tool',
            category: NodeCategory::Ai,
            description: 'ابزار مشاهده فایل‌ها، خواندن فایل‌های متنی و مدیریت پوشه‌ها روی سرور FTP توسط ایجنت هوش مصنوعی.',
            icon: 'server',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'ftp',
                    'label'           => 'اعتبارنامه FTP / FTPS',
                    'required'        => true,
                ],
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'ftp_manager',
                    'required'    => true,
                    'placeholder' => 'ftp_manager',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'مشاهده لیست فایل‌ها و پوشه‌ها، خواندن فایل‌های متنی و مدیریت فایل‌ها در سرور FTP.',
                    'required' => true,
                ],
                'allowed_scope' => [
                    'type'     => 'select',
                    'label'    => 'سطح دسترسی ایجنت (Access Mode)',
                    'options'  => [
                        ['value' => 'read_only', 'label' => 'فقط خواندن و لیست‌گیری'],
                        ['value' => 'read_write', 'label' => 'خواندن و نوشتن'],
                    ],
                    'default'  => 'read_only',
                    'required' => true,
                ],
                'base_path' => [
                    'type'        => 'text',
                    'label'       => 'پوشه محدوده مجاز (Base Path)',
                    'default'     => '/',
                    'placeholder' => '/public_html/reports',
                    'help'        => 'ایجنت فقط به فایل‌های داخل این مسیر دسترسی خواهد داشت و امکان خروج از آن را ندارد.',
                    'advanced'    => true,
                ],
            ],
            color: 'cyan',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'ftp_manager'));
    }

    public function toolDescription(array $config): string
    {
        $base = trim((string) ($config['tool_description'] ?? 'مدیریت فایل‌ها در سرور FTP'));
        $basePath = trim((string) ($config['base_path'] ?? '/'));

        if ($basePath !== '' && $basePath !== '/') {
            return $base . " (مسیر پایه محدود به «{$basePath}» است.)";
        }

        return $base;
    }

    public function toolParameters(array $config): array
    {
        $readOnly = ($config['allowed_scope'] ?? 'read_only') === 'read_only';

        $actions = $readOnly
            ? '"list" (مشاهده لیست فایل‌ها و پوشه‌ها)، "download_text" (خواندن محتوای یک فایل متنی)'
            : '"list" (لیست فایل‌ها)، "download_text" (خواندن فایل متنی)، "upload_text" (ذخیره فایل متنی)، "create_folder" (ساخت پوشه)، "rename" (تغییر نام یا انتقال)، "delete" (حذف فایل)';

        $properties = [
            'action' => [
                'type'        => 'STRING',
                'description' => "نوع عملیات: $actions",
            ],
            'path' => [
                'type'        => 'STRING',
                'description' => 'مسیر فایل یا پوشه در سرور FTP (برای list، download_text، upload_text، create_folder و delete)',
            ],
        ];

        if (! $readOnly) {
            $properties['content'] = [
                'type'        => 'STRING',
                'description' => 'محتوای متنی فایل برای عملیات upload_text',
            ];
            $properties['new_path'] = [
                'type'        => 'STRING',
                'description' => 'مسیر جدید فایل یا پوشه برای عملیات rename',
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
            throw NodeExecutionException::because('اعتبارنامه FTP برای ابزار ایجنت انتخاب نشده است.');
        }

        $client = new FtpClient($credential);
        $readOnly = ($context->config['allowed_scope'] ?? 'read_only') === 'read_only';
        $basePath = trim((string) ($context->config['base_path'] ?? '/')) ?: '/';
        $action = strtolower(trim((string) ($arguments['action'] ?? 'list')));

        if ($readOnly && in_array($action, ['upload_text', 'create_folder', 'rename', 'delete'], true)) {
            return ['error' => 'این ابزار روی حالت «فقط خواندن» تنظیم شده است و اجازه تغییر یا حذف فایل‌ها را ندارد.'];
        }

        $rawPath = trim((string) ($arguments['path'] ?? ''));

        return match ($action) {
            'list' => $client->list(
                $client->resolveSandboxedPath($rawPath, $basePath, allowBase: true),
            ),

            'download_text' => (function () use ($client, $rawPath, $basePath) {
                $target = $client->resolveSandboxedPath($rawPath, $basePath);
                $downloaded = $client->download($target);

                if ($downloaded['text'] === null) {
                    return [
                        'path'      => $downloaded['path'],
                        'file_name' => $downloaded['file_name'],
                        'size'      => $downloaded['size'],
                        'mime_type' => $downloaded['mime_type'],
                        'error'     => 'این فایل باینری یا بسیار حجیم است و امکان خواندن مستقیم آن به صورت متن وجود ندارد.',
                    ];
                }

                $text = (string) $downloaded['text'];
                $truncated = mb_strlen($text) > self::MAX_AGENT_TEXT_CHARS;

                return [
                    'path'      => $downloaded['path'],
                    'file_name' => $downloaded['file_name'],
                    'size'      => $downloaded['size'],
                    'mime_type' => $downloaded['mime_type'],
                    'content'   => mb_substr($text, 0, self::MAX_AGENT_TEXT_CHARS),
                    'truncated' => $truncated,
                ];
            })(),

            'upload_text' => $client->upload(
                $client->resolveSandboxedPath($rawPath, $basePath),
                (string) ($arguments['content'] ?? ''),
            ),

            'create_folder' => $client->createFolder(
                $client->resolveSandboxedPath($rawPath, $basePath),
            ),

            'rename' => $client->rename(
                $client->resolveSandboxedPath($rawPath, $basePath),
                $client->resolveSandboxedPath((string) ($arguments['new_path'] ?? ''), $basePath),
            ),

            'delete' => $client->delete(
                $client->resolveSandboxedPath($rawPath, $basePath)
            ),

            default => ['error' => "عملیات ناشناخته FTP: $action"],
        };
    }
}
