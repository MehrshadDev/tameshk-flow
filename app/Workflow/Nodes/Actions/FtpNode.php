<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\FtpClient;
use App\Workflow\Support\SafeHttpClient;
use Illuminate\Support\Facades\Http;
use Throwable;

class FtpNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.ftp',
            name: 'FTP',
            category: NodeCategory::Action,
            description: 'مدیریت کامل فایل‌ها و پوشه‌ها روی سرور از طریق پروتکل FTP و FTPS (لیست، دانلود، آپلود، حذف، تغییر نام و ساخت پوشه).',
            icon: 'server',
            group: 'ftp',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'ftp',
                    'label'           => 'اعتبارنامه FTP / FTPS',
                    'required'        => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات (Operation)',
                    'options'  => [
                        ['value' => 'list', 'label' => 'لیست فایل‌ها و پوشه‌ها (List)'],
                        ['value' => 'download', 'label' => 'دانلود فایل (Download)'],
                        ['value' => 'upload', 'label' => 'آپلود فایل (Upload)'],
                        ['value' => 'delete', 'label' => 'حذف فایل یا پوشه (Delete)'],
                        ['value' => 'rename', 'label' => 'تغییر نام یا جابجایی (Rename / Move)'],
                        ['value' => 'create_folder', 'label' => 'ایجاد پوشه جدید (Create Folder)'],
                    ],
                    'default'  => 'list',
                    'required' => true,
                ],

                'directory_path' => [
                    'type'        => 'text',
                    'label'       => 'مسیر پوشه (Directory Path)',
                    'default'     => '/',
                    'placeholder' => '/public_html/uploads',
                    'required'    => true,
                    'depends_on'  => ['operation' => 'list'],
                ],
                'recursive' => [
                    'type'       => 'boolean',
                    'label'      => 'جستجوی بازگشتی در زیرپوشه‌ها (Recursive)',
                    'default'    => false,
                    'depends_on' => ['operation' => 'list'],
                ],

                'path' => [
                    'type'        => 'text',
                    'label'       => 'مسیر فایل یا پوشه (Path)',
                    'required'    => true,
                    'placeholder' => '/public_html/files/report.txt',
                    'depends_on'  => [
                        'operation' => ['download', 'upload', 'delete', 'create_folder'],
                    ],
                ],

                'input_mode' => [
                    'type'       => 'select',
                    'label'      => 'منبع داده فایل (Input Mode)',
                    'options'    => [
                        ['value' => 'binary', 'label' => 'فایل باینری / Data URL / Base64 یا لینک'],
                        ['value' => 'text', 'label' => 'متن مستقیم (Text / JSON / CSV)'],
                    ],
                    'default'    => 'binary',
                    'required'   => true,
                    'depends_on' => ['operation' => 'upload'],
                ],
                'binary_data' => [
                    'type'        => 'text',
                    'label'       => 'داده فایل یا آدرس (Data URL / Base64 / URL)',
                    'required'    => false,
                    'default'     => '{{ input.data_url }}',
                    'placeholder' => '{{ input.data_url }} یا {{ input.base64 }} یا https://example.com/file.pdf',
                    'help'        => 'می‌توانید خروجی فایل نودهای تلگرام، بله، جمینی یا یک لینک اینترنتی معتبر را وارد کنید.',
                    'depends_on'  => [
                        'operation'  => 'upload',
                        'input_mode' => 'binary',
                    ],
                ],
                'file_content' => [
                    'type'        => 'textarea',
                    'label'       => 'محتوای متنی فایل (File Content)',
                    'required'    => true,
                    'placeholder' => 'متن، JSON یا CSV مورد نظر برای ذخیره در فایل...',
                    'depends_on'  => [
                        'operation'  => 'upload',
                        'input_mode' => 'text',
                    ],
                ],

                'delete_type' => [
                    'type'       => 'select',
                    'label'      => 'نوع مورد برای حذف',
                    'options'    => [
                        ['value' => 'file', 'label' => 'فایل (File)'],
                        ['value' => 'folder', 'label' => 'پوشه (Folder)'],
                    ],
                    'default'    => 'file',
                    'required'   => true,
                    'depends_on' => ['operation' => 'delete'],
                ],
                'delete_recursive' => [
                    'type'       => 'boolean',
                    'label'      => 'حذف پوشه به همراه تمام محتویات داخل آن (Recursive)',
                    'default'    => false,
                    'depends_on' => [
                        'operation'   => 'delete',
                        'delete_type' => 'folder',
                    ],
                ],

                'old_path' => [
                    'type'        => 'text',
                    'label'       => 'مسیر فعلی (Old Path)',
                    'required'    => true,
                    'placeholder' => '/public_html/old-name.txt',
                    'depends_on'  => ['operation' => 'rename'],
                ],
                'new_path' => [
                    'type'        => 'text',
                    'label'       => 'مسیر جدید (New Path)',
                    'required'    => true,
                    'placeholder' => '/public_html/archive/new-name.txt',
                    'depends_on'  => ['operation' => 'rename'],
                ],

                'create_directories' => [
                    'type'       => 'boolean',
                    'label'      => 'ایجاد خودکار پوشه‌های والد در صورت وجود نداشتن',
                    'default'    => true,
                    'advanced'   => true,
                    'depends_on' => [
                        'operation' => ['upload', 'rename', 'create_folder'],
                    ],
                ],
            ],
            presets: [
                [
                    'key'         => 'list',
                    'name'        => 'List files & folders',
                    'description' => 'دریافت فهرست فایل‌ها و پوشه‌های یک مسیر در سرور FTP.',
                    'config'      => ['operation' => 'list', 'directory_path' => '/'],
                ],
                [
                    'key'         => 'download',
                    'name'        => 'Download a file',
                    'description' => 'دانلود فایل از سرور FTP با خروجی Base64، Data URL و متن.',
                    'config'      => ['operation' => 'download'],
                ],
                [
                    'key'         => 'upload',
                    'name'        => 'Upload a file',
                    'description' => 'آپلود فایل باینری یا محتوای متنی به مسیر دلخواه روی سرور FTP.',
                    'config'      => ['operation' => 'upload', 'input_mode' => 'binary'],
                ],
                [
                    'key'         => 'delete',
                    'name'        => 'Delete a file or folder',
                    'description' => 'حذف یک فایل یا پوشه (تکی یا بازگشتی) از سرور FTP.',
                    'config'      => ['operation' => 'delete', 'delete_type' => 'file'],
                ],
                [
                    'key'         => 'rename',
                    'name'        => 'Rename or move',
                    'description' => 'تغییر نام یا انتقال فایل و پوشه به مسیر جدید روی سرور FTP.',
                    'config'      => ['operation' => 'rename'],
                ],
                [
                    'key'         => 'create_folder',
                    'name'        => 'Create a folder',
                    'description' => 'ایجاد پوشه جدید (تکی یا تودرتو) روی سرور FTP.',
                    'config'      => ['operation' => 'create_folder'],
                ],
            ],
            color: 'cyan',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه FTP انتخاب نشده است.');
        }

        $client = new FtpClient($credential);
        $operation = (string) ($context->config['operation'] ?? 'list');

        return match ($operation) {
            'list'          => $this->handleList($context, $client),
            'download'      => $this->handleDownload($context, $client),
            'upload'        => $this->handleUpload($context, $client),
            'delete'        => $this->handleDelete($context, $client),
            'rename'        => $this->handleRename($context, $client),
            'create_folder' => $this->handleCreateFolder($context, $client),
            default         => throw NodeExecutionException::because("عملیات FTP نامعتبر است: $operation"),
        };
    }

    private function handleList(NodeContext $context, FtpClient $client): NodeResult
    {
        $path = trim((string) ($context->config['directory_path'] ?? $context->config['path'] ?? '/'));
        $recursive = filter_var($context->config['recursive'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $context->log("دریافت لیست فایل‌های FTP در مسیر $path");

        return NodeResult::of($client->list($path !== '' ? $path : '/', $recursive));
    }

    private function handleDownload(NodeContext $context, FtpClient $client): NodeResult
    {
        $path = $this->requireField($context, 'path', 'مسیر فایل برای دانلود الزامی است.');

        $context->log("دانلود فایل از FTP: $path");

        return NodeResult::of($client->download($path));
    }

    private function handleUpload(NodeContext $context, FtpClient $client): NodeResult
    {
        $path = $this->requireField($context, 'path', 'مسیر مقصد برای آپلود فایل الزامی است.');
        $mode = (string) ($context->config['input_mode'] ?? 'binary');
        $createDirs = filter_var($context->config['create_directories'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $binary = $mode === 'text'
            ? (string) ($context->config['file_content'] ?? '')
            : $this->resolveBinaryContent($context);

        $context->log("آپلود فایل به FTP: $path");

        return NodeResult::of($client->upload($path, $binary, $createDirs));
    }

    private function handleDelete(NodeContext $context, FtpClient $client): NodeResult
    {
        $path = $this->requireField($context, 'path', 'مسیر فایل یا پوشه برای حذف الزامی است.');
        $isFolder = ((string) ($context->config['delete_type'] ?? 'file')) === 'folder';
        $recursive = $isFolder && filter_var($context->config['delete_recursive'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $context->log("حذف از FTP: $path");

        return NodeResult::of($client->delete($path, $isFolder, $recursive));
    }

    private function handleRename(NodeContext $context, FtpClient $client): NodeResult
    {
        $oldPath = $this->requireField($context, 'old_path', 'مسیر فعلی (Old Path) الزامی است.');
        $newPath = $this->requireField($context, 'new_path', 'مسیر جدید (New Path) الزامی است.');
        $createDirs = filter_var($context->config['create_directories'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $context->log("تغییر نام در FTP: $oldPath ← $newPath");

        return NodeResult::of($client->rename($oldPath, $newPath, $createDirs));
    }

    private function handleCreateFolder(NodeContext $context, FtpClient $client): NodeResult
    {
        $path = $this->requireField($context, 'path', 'مسیر پوشه جدید الزامی است.');
        $recursive = filter_var($context->config['create_directories'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $context->log("ایجاد پوشه در FTP: $path");

        return NodeResult::of($client->createFolder($path, $recursive));
    }

    private function resolveBinaryContent(NodeContext $context): string
    {
        $source = trim((string) ($context->config['binary_data'] ?? ''));

        if ($source === '' && is_array($context->input)) {
            foreach (['data_url', 'base64', 'url', 'file_url', 'media_url'] as $key) {
                $candidate = $context->input[$key] ?? null;
                if (is_string($candidate) && trim($candidate) !== '') {
                    $source = trim($candidate);
                    break;
                }
            }
        }

        if ($source === '') {
            throw NodeExecutionException::because('داده باینری یا آدرس فایل برای آپلود خالی است.');
        }

        if (str_starts_with($source, 'data:')) {
            $markerPos = strpos($source, ';base64,');
            if ($markerPos === false) {
                throw NodeExecutionException::because('فرمت Data URL واردشده معتبر نیست.');
            }

            $payload = str_replace(["\r", "\n", ' '], '', substr($source, $markerPos + 8));
            $decoded = base64_decode($payload, true);

            if ($decoded === false) {
                throw NodeExecutionException::because('رمزگشایی محتوای Base64 ناموفق بود.');
            }

            return $decoded;
        }

        if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
            return $this->downloadRemoteFile($source);
        }

        $cleanBase64 = str_replace(["\r", "\n", ' '], '', $source);
        $decoded = base64_decode($cleanBase64, true);

        if ($decoded !== false && $decoded !== '' && base64_encode($decoded) === $cleanBase64) {
            return $decoded;
        }

        return $source;
    }

    private function downloadRemoteFile(string $url): string
    {
        (new SafeHttpClient())->assertSafeUrl($url);

        try {
            $response = Http::withOptions([
                'allow_redirects' => false,
                'verify'          => true,
            ])->timeout(30)->get($url);
        } catch (Throwable) {
            throw NodeExecutionException::because('امکان دانلود فایل از آدرس اینترنتی برای آپلود به FTP وجود ندارد.');
        }

        if ($response->failed()) {
            throw NodeExecutionException::because("دانلود فایل مبدأ با کد خطای HTTP {$response->status()} ناموفق بود.");
        }

        $body = $response->body();

        if (strlen($body) > FtpClient::MAX_FILE_BYTES) {
            $maxMb = (int) (FtpClient::MAX_FILE_BYTES / 1048576);
            throw NodeExecutionException::because("حجم فایل مبدأ بیش از سقف مجاز ($maxMb مگابایت) است.");
        }

        return $body;
    }

    private function requireField(NodeContext $context, string $key, string $message): string
    {
        $val = trim((string) ($context->config[$key] ?? ''));

        if ($val === '') {
            throw NodeExecutionException::because($message);
        }

        return $val;
    }
}
