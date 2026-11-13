<?php

namespace App\Backup;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PureGitClient
{
    public function verifyRemote(string $remoteUrl, string $branch = 'main'): array
    {
        $info = $this->discoverRefs($remoteUrl, 'git-receive-pack');
        $headSha = $info['refs']["refs/heads/$branch"] ?? str_repeat('0', 40);

        return [
            'ok' => true,
            'head_sha' => $headSha,
            'is_empty' => $headSha === str_repeat('0', 40),
        ];
    }

    public function buildTreeObjects(string $dataDir): array
    {
        $objects = [];

        $addObject = function (int $type, string $typeName, string $content) use (&$objects): string {
            $full = "$typeName " . strlen($content) . "\0" . $content;
            $sha = sha1($full);
            $objects[$sha] = [
                'type' => $type,
                'content' => $content,
            ];

            return $sha;
        };

        $dataEntries = [];

        if (File::isDirectory($dataDir)) {
            $tableDirs = File::directories($dataDir);

            foreach ($tableDirs as $tableDir) {
                $tableName = basename($tableDir);
                $files = File::files($tableDir);
                $tableEntries = [];

                foreach ($files as $file) {
                    if ($file->getExtension() !== 'enc') {
                        continue;
                    }

                    $fileName = $file->getFilename();
                    $fileContent = File::get($file->getRealPath());
                    $blobSha = $addObject(3, 'blob', $fileContent);

                    $tableEntries[$fileName] = "100644 $fileName\0" . hex2bin($blobSha);
                }

                if ($tableEntries === []) {
                    continue;
                }

                uksort($tableEntries, 'strcmp');
                $tableTreeSha = $addObject(2, 'tree', implode('', $tableEntries));
                $dataEntries[$tableName] = [
                    'is_dir' => true,
                    'raw' => "40000 $tableName\0" . hex2bin($tableTreeSha),
                ];
            }
        }

        if ($dataEntries === []) {
            $keepSha = $addObject(3, 'blob', '');
            $dataEntries['.gitkeep'] = [
                'is_dir' => false,
                'raw' => "100644 .gitkeep\0" . hex2bin($keepSha),
            ];
        }

        uksort($dataEntries, function (string $a, string $b) use ($dataEntries): int {
            $keyA = $dataEntries[$a]['is_dir'] ? "$a/" : $a;
            $keyB = $dataEntries[$b]['is_dir'] ? "$b/" : $b;

            return strcmp($keyA, $keyB);
        });

        $dataTreeRaw = implode('', array_column($dataEntries, 'raw'));
        $dataTreeSha = $addObject(2, 'tree', $dataTreeRaw);

        $rootTreeRaw = "40000 data\0" . hex2bin($dataTreeSha);
        $rootTreeSha = $addObject(2, 'tree', $rootTreeRaw);

        return [
            'root_tree_sha' => $rootTreeSha,
            'objects' => $objects,
        ];
    }

    public function pushSnapshot(
        string $remoteUrl,
        string $branch,
        string $rootTreeSha,
        array $treeObjects,
        string $botName,
        string $botEmail,
        string $commitMessage,
        ?string $knownTreeSha = null,
        ?string $knownCommitSha = null,
    ): array {
        $discovery = $this->discoverRefs($remoteUrl, 'git-receive-pack');
        $refName = "refs/heads/$branch";
        $remoteHeadSha = $discovery['refs'][$refName] ?? str_repeat('0', 40);

        if (
            $remoteHeadSha !== str_repeat('0', 40)
            && $knownTreeSha === $rootTreeSha
            && $knownCommitSha === $remoteHeadSha
        ) {
            return [
                'pushed' => false,
                'commit_sha' => $remoteHeadSha,
                'tree_sha' => $rootTreeSha,
            ];
        }

        $timestamp = time();
        $lines = ["tree $rootTreeSha"];

        if ($remoteHeadSha !== '' && $remoteHeadSha !== str_repeat('0', 40)) {
            $lines[] = "parent $remoteHeadSha";
        }

        $cleanName = str_replace(['<', '>', "\n", "\r"], '', $botName) ?: 'Tameshk Backup Bot';
        $cleanEmail = str_replace(['<', '>', "\n", "\r"], '', $botEmail) ?: 'backup@tameshkflow.ir';

        $lines[] = "author $cleanName <$cleanEmail> $timestamp +0330";
        $lines[] = "committer $cleanName <$cleanEmail> $timestamp +0330";
        $lines[] = '';
        $lines[] = trim($commitMessage);

        $commitContent = implode("\n", $lines) . "\n";
        $commitFull = 'commit ' . strlen($commitContent) . "\0" . $commitContent;
        $commitSha = sha1($commitFull);

        $allObjects = $treeObjects;
        $allObjects[$commitSha] = [
            'type' => 1,
            'content' => $commitContent,
        ];

        $packfile = $this->buildPackfile($allObjects);

        $commandLine = "$remoteHeadSha $commitSha $refName\0 report-status\n";
        $requestBody = $this->pktLine($commandLine) . $this->pktFlush() . $packfile;

        $parsed = $this->parseRemoteUrl($remoteUrl);

        $response = $this->httpClient($parsed, 90)
            ->withHeaders([
                'Content-Type' => 'application/x-git-receive-pack-request',
                'Accept' => 'application/x-git-receive-pack-result',
            ])
            ->withBody($requestBody, 'application/x-git-receive-pack-request')
            ->post($parsed['url'] . '/git-receive-pack');

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()} during git-receive-pack: " . substr($response->body(), 0, 300));
        }

        $body = $response->body();

        if (! str_contains($body, 'unpack ok') || ! str_contains($body, "ok $refName")) {
            $cleanBody = preg_replace('/[^\x20-\x7E\n]/', ' ', $body);
            throw new RuntimeException('Remote rejected push: ' . trim((string) $cleanBody));
        }

        return [
            'pushed' => true,
            'commit_sha' => $commitSha,
            'tree_sha' => $rootTreeSha,
        ];
    }

    public function fetchRemoteSnapshot(string $remoteUrl, string $branch, string $commitRef = 'HEAD'): array
    {
        $discovery = $this->discoverRefs($remoteUrl, 'git-upload-pack');
        $refName = "refs/heads/$branch";
        $headSha = $discovery['refs'][$refName] ?? ($discovery['refs']['HEAD'] ?? null);

        if (! $headSha || $headSha === str_repeat('0', 40)) {
            throw new RuntimeException("Branch [$branch] does not exist or is empty on remote.");
        }

        $isHead = strtoupper($commitRef) === 'HEAD';
        $wantSha = $headSha;
        $useShallow = $isHead && in_array('shallow', $discovery['capabilities'], true);

        if (! $isHead && preg_match('/^[0-9a-f]{40}$/i', $commitRef)) {
            $wantSha = strtolower($commitRef);
            $useShallow = in_array('shallow', $discovery['capabilities'], true)
                && (
                    in_array('allow-reachable-sha1-in-want', $discovery['capabilities'], true)
                    || in_array('allow-tip-sha1-in-want', $discovery['capabilities'], true)
                );
        }

        $objectsBySha = $this->downloadPackObjects($remoteUrl, $wantSha, $useShallow);

        $targetCommitSha = $this->resolveCommitSha($objectsBySha, $isHead ? $headSha : strtolower($commitRef));

        if ($targetCommitSha === null && ! $isHead && $wantSha !== $headSha) {
            $objectsBySha = $this->downloadPackObjects($remoteUrl, $headSha, false);
            $targetCommitSha = $this->resolveCommitSha($objectsBySha, strtolower($commitRef));
        }

        if ($targetCommitSha === null || ! isset($objectsBySha[$targetCommitSha])) {
            throw new RuntimeException("Commit [$commitRef] was not found in remote repository.");
        }

        $commitContent = $objectsBySha[$targetCommitSha]['content'];
        $commitMeta = $this->parseCommitObject($commitContent);

        return [
            'commit_sha' => $targetCommitSha,
            'tree_sha' => $commitMeta['tree'],
            'timestamp' => $commitMeta['timestamp'],
            'objects' => $objectsBySha,
        ];
    }

    public function extractSnapshotToDirectory(array $snapshot, string $repoPath): int
    {
        $objects = $snapshot['objects'];
        $rootTreeSha = $snapshot['tree_sha'];

        if (! isset($objects[$rootTreeSha])) {
            throw new RuntimeException("Root tree [$rootTreeSha] missing in unpacked objects.");
        }

        $dataDir = "$repoPath/data";
        if (File::isDirectory($dataDir)) {
            File::deleteDirectory($dataDir);
        }
        File::ensureDirectoryExists($dataDir);

        return $this->writeTreeRecursive($objects, $rootTreeSha, $repoPath);
    }

    public function discoverRefs(string $remoteUrl, string $service): array
    {
        $parsed = $this->parseRemoteUrl($remoteUrl);

        $response = $this->httpClient($parsed, 30)
            ->get($parsed['url'] . '/info/refs', ['service' => $service]);

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()} connecting to remote ({$this->maskUrl($remoteUrl)}).");
        }

        $lines = $this->parsePktLines($response->body());
        $refs = [];
        $capabilities = [];

        foreach ($lines as $line) {
            if ($line === null || str_starts_with($line, '#')) {
                continue;
            }

            $trimmed = rtrim($line, "\r\n");

            if (str_contains($trimmed, "\0")) {
                [$refPart, $capPart] = explode("\0", $trimmed, 2);
                $capabilities = array_values(array_filter(explode(' ', trim($capPart))));
            } else {
                $refPart = $trimmed;
            }

            $parts = explode(' ', trim($refPart), 2);
            if (count($parts) === 2 && preg_match('/^[0-9a-f]{40}$/i', $parts[0])) {
                $refs[$parts[1]] = strtolower($parts[0]);
            }
        }

        return [
            'refs' => $refs,
            'capabilities' => $capabilities,
        ];
    }

    private function downloadPackObjects(string $remoteUrl, string $wantSha, bool $shallow): array
    {
        $body = $this->pktLine("want $wantSha ofs-delta\n");

        if ($shallow) {
            $body .= $this->pktLine("deepen 1\n");
        }

        $body .= $this->pktFlush() . $this->pktLine("done\n");

        $parsed = $this->parseRemoteUrl($remoteUrl);

        $response = $this->httpClient($parsed, 90)
            ->withHeaders([
                'Content-Type' => 'application/x-git-upload-pack-request',
                'Accept' => 'application/x-git-upload-pack-result',
            ])
            ->withBody($body, 'application/x-git-upload-pack-request')
            ->post($parsed['url'] . '/git-upload-pack');

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()} during git-upload-pack.");
        }

        $raw = $response->body();
        $packPos = strpos($raw, 'PACK');

        if ($packPos === false) {
            throw new RuntimeException('No PACK stream found in git-upload-pack response.');
        }

        return $this->unpackPackfile(substr($raw, $packPos));
    }

    private function buildPackfile(array $objects): string
    {
        $pack = 'PACK' . pack('N', 2) . pack('N', count($objects));

        foreach ($objects as $obj) {
            $content = $obj['content'];
            $type = (int) $obj['type'];
            $pack .= $this->packObjectHeader($type, strlen($content));
            $pack .= gzcompress($content, 6);
        }

        return $pack . sha1($pack, true);
    }

    private function packObjectHeader(int $type, int $size): string
    {
        $byte = (($type & 0x07) << 4) | ($size & 0x0f);
        $size >>= 4;
        $header = '';

        while ($size > 0) {
            $header .= chr($byte | 0x80);
            $byte = $size & 0x7f;
            $size >>= 7;
        }

        return $header . chr($byte);
    }

    private function unpackPackfile(string $pack): array
    {
        if (strlen($pack) < 32 || substr($pack, 0, 4) !== 'PACK') {
            throw new RuntimeException('Invalid Git packfile header.');
        }

        $version = unpack('N', substr($pack, 4, 4))[1];
        $objectCount = unpack('N', substr($pack, 8, 4))[1];

        if ($version !== 2 && $version !== 3) {
            throw new RuntimeException("Unsupported packfile version: $version");
        }

        $offset = 12;
        $packLen = strlen($pack);
        $byOffset = [];
        $bySha = [];
        $pendingDeltas = [];

        for ($i = 0; $i < $objectCount; $i++) {
            $objStart = $offset;
            $byte = ord($pack[$offset++]);
            $type = ($byte >> 4) & 0x07;
            $shift = 4;

            while (($byte & 0x80) !== 0) {
                $byte = ord($pack[$offset++]);
                $shift += 7;
            }

            $baseOffset = null;
            $baseSha = null;

            if ($type === 6) {
                $b = ord($pack[$offset++]);
                $negOffset = $b & 0x7f;
                while (($b & 0x80) !== 0) {
                    $b = ord($pack[$offset++]);
                    $negOffset = (($negOffset + 1) << 7) | ($b & 0x7f);
                }
                $baseOffset = $objStart - $negOffset;
            } elseif ($type === 7) {
                $baseSha = bin2hex(substr($pack, $offset, 20));
                $offset += 20;
            }

            $inflateCtx = inflate_init(ZLIB_ENCODING_DEFLATE);
            if ($inflateCtx === false) {
                throw new RuntimeException('Failed to initialize zlib inflate.');
            }

            $inflated = '';
            $chunkPos = $offset;

            while ($chunkPos < $packLen) {
                $chunk = substr($pack, $chunkPos, 65536);
                $part = @inflate_add($inflateCtx, $chunk);
                if ($part === false) {
                    throw new RuntimeException("Corrupted zlib stream in packfile at offset $objStart.");
                }
                $inflated .= $part;
                if (inflate_get_status($inflateCtx) === ZLIB_STREAM_END) {
                    break;
                }
                $chunkPos += strlen($chunk);
            }

            $offset += inflate_get_read_len($inflateCtx);

            if (in_array($type, [1, 2, 3, 4], true)) {
                $typeName = $this->typeName($type);
                $sha = sha1("$typeName " . strlen($inflated) . "\0" . $inflated);
                $entry = [
                    'type' => $type,
                    'content' => $inflated,
                    'sha' => $sha,
                ];
                $byOffset[$objStart] = $entry;
                $bySha[$sha] = $entry;
            } elseif ($type === 6 || $type === 7) {
                $pendingDeltas[] = [
                    'offset' => $objStart,
                    'type' => $type,
                    'base_offset' => $baseOffset,
                    'base_sha' => $baseSha,
                    'delta' => $inflated,
                ];
            }
        }

        $maxPasses = count($pendingDeltas) + 2;
        $pass = 0;

        while ($pendingDeltas !== [] && $pass++ < $maxPasses) {
            $remaining = [];

            foreach ($pendingDeltas as $deltaItem) {
                $baseEntry = $deltaItem['type'] === 6
                    ? ($byOffset[$deltaItem['base_offset']] ?? null)
                    : ($bySha[$deltaItem['base_sha']] ?? null);

                if ($baseEntry === null) {
                    $remaining[] = $deltaItem;
                    continue;
                }

                $resolvedContent = $this->applyGitDelta($baseEntry['content'], $deltaItem['delta']);
                $typeName = $this->typeName($baseEntry['type']);
                $sha = sha1("$typeName " . strlen($resolvedContent) . "\0" . $resolvedContent);

                $entry = [
                    'type' => $baseEntry['type'],
                    'content' => $resolvedContent,
                    'sha' => $sha,
                ];

                $byOffset[$deltaItem['offset']] = $entry;
                $bySha[$sha] = $entry;
            }

            if (count($remaining) === count($pendingDeltas)) {
                throw new RuntimeException('Unresolvable delta object in Git packfile.');
            }

            $pendingDeltas = $remaining;
        }

        return $bySha;
    }

    private function applyGitDelta(string $base, string $delta): string
    {
        $pos = 0;
        $len = strlen($delta);

        $shift = 0;
        do {
            $byte = ord($delta[$pos++]);
            $shift += 7;
        } while (($byte & 0x80) !== 0 && $pos < $len);

        $shift = 0;
        do {
            $byte = ord($delta[$pos++]);
            $shift += 7;
        } while (($byte & 0x80) !== 0 && $pos < $len);

        $out = '';

        while ($pos < $len) {
            $cmd = ord($delta[$pos++]);

            if (($cmd & 0x80) !== 0) {
                $cpOffset = 0;
                $cpSize = 0;

                if (($cmd & 0x01) !== 0) $cpOffset |= ord($delta[$pos++]);
                if (($cmd & 0x02) !== 0) $cpOffset |= ord($delta[$pos++]) << 8;
                if (($cmd & 0x04) !== 0) $cpOffset |= ord($delta[$pos++]) << 16;
                if (($cmd & 0x08) !== 0) $cpOffset |= ord($delta[$pos++]) << 24;

                if (($cmd & 0x10) !== 0) $cpSize |= ord($delta[$pos++]);
                if (($cmd & 0x20) !== 0) $cpSize |= ord($delta[$pos++]) << 8;
                if (($cmd & 0x40) !== 0) $cpSize |= ord($delta[$pos++]) << 16;

                if ($cpSize === 0) {
                    $cpSize = 0x10000;
                }

                $out .= substr($base, $cpOffset, $cpSize);
            } elseif ($cmd > 0) {
                $out .= substr($delta, $pos, $cmd);
                $pos += $cmd;
            }
        }

        return $out;
    }

    private function writeTreeRecursive(array &$objects, string $treeSha, string $currentDir): int
    {
        $treeObj = $objects[$treeSha] ?? null;

        if ($treeObj === null || $treeObj['type'] !== 2) {
            return 0;
        }

        $entries = $this->parseTree($treeObj['content']);
        $writtenFiles = 0;

        foreach ($entries as $entry) {
            $name = $entry['name'];
            if ($name === '' || str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\')) {
                continue;
            }

            $targetPath = $currentDir . DIRECTORY_SEPARATOR . $name;
            $entrySha = $entry['sha'];

            if ($entry['mode'] === '40000' || $entry['mode'] === '040000') {
                File::ensureDirectoryExists($targetPath);
                $writtenFiles += $this->writeTreeRecursive($objects, $entrySha, $targetPath);
            } elseif (isset($objects[$entrySha]) && $objects[$entrySha]['type'] === 3) {
                File::ensureDirectoryExists(dirname($targetPath));
                File::put($targetPath, $objects[$entrySha]['content']);
                $writtenFiles++;
            }
        }

        return $writtenFiles;
    }

    private function parseTree(string $content): array
    {
        $entries = [];
        $len = strlen($content);
        $pos = 0;

        while ($pos < $len) {
            $spacePos = strpos($content, ' ', $pos);
            if ($spacePos === false) {
                break;
            }

            $mode = substr($content, $pos, $spacePos - $pos);
            $nullPos = strpos($content, "\0", $spacePos + 1);

            if ($nullPos === false || ($nullPos + 21) > $len) {
                break;
            }

            $name = substr($content, $spacePos + 1, $nullPos - ($spacePos + 1));
            $shaHex = bin2hex(substr($content, $nullPos + 1, 20));

            $entries[] = [
                'mode' => $mode,
                'name' => $name,
                'sha' => $shaHex,
            ];

            $pos = $nullPos + 21;
        }

        return $entries;
    }

    private function resolveCommitSha(array $objectsBySha, string $prefix): ?string
    {
        if (isset($objectsBySha[$prefix]) && $objectsBySha[$prefix]['type'] === 1) {
            return $prefix;
        }

        foreach ($objectsBySha as $sha => $obj) {
            if ($obj['type'] === 1 && str_starts_with($sha, $prefix)) {
                return $sha;
            }
        }

        return null;
    }

    private function parseCommitObject(string $content): array
    {
        $tree = '';
        $timestamp = 0;

        if (preg_match('/^tree\s+([0-9a-f]{40})/m', $content, $m)) {
            $tree = strtolower($m[1]);
        }

        if (preg_match('/^committer\s+.*?\s+(\d+)\s+[+-]\d{4}/m', $content, $m)) {
            $timestamp = (int) $m[1];
        }

        return [
            'tree' => $tree,
            'timestamp' => $timestamp,
        ];
    }

    private function typeName(int $type): string
    {
        return match ($type) {
            1 => 'commit',
            2 => 'tree',
            3 => 'blob',
            4 => 'tag',
            default => throw new RuntimeException("Unknown Git object type: $type"),
        };
    }

    private function pktLine(string $payload): string
    {
        return sprintf('%04x', strlen($payload) + 4) . $payload;
    }

    private function pktFlush(): string
    {
        return '0000';
    }

    private function parsePktLines(string $raw): array
    {
        $lines = [];
        $len = strlen($raw);
        $offset = 0;

        while ($offset + 4 <= $len) {
            $hex = substr($raw, $offset, 4);

            if ($hex === '0000' || $hex === '0001' || $hex === '0002') {
                $lines[] = null;
                $offset += 4;
                continue;
            }

            if (! ctype_xdigit($hex)) {
                break;
            }

            $pktLen = hexdec($hex);
            if ($pktLen < 4 || $offset + $pktLen > $len) {
                break;
            }

            $lines[] = substr($raw, $offset + 4, $pktLen - 4);
            $offset += $pktLen;
        }

        return $lines;
    }

    private function parseRemoteUrl(string $remoteUrl): array
    {
        $parsed = parse_url(trim($remoteUrl));

        if (! $parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            throw new RuntimeException('Invalid Git remote URL: ' . $this->maskUrl($remoteUrl));
        }

        $user = isset($parsed['user']) ? rawurldecode($parsed['user']) : '';
        $pass = isset($parsed['pass']) ? rawurldecode($parsed['pass']) : '';

        if ($user !== '' && $pass === '') {
            $pass = $user;
            $user = 'x-access-token';
        }

        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = rtrim($parsed['path'] ?? '', '/');

        return [
            'url' => "{$parsed['scheme']}://{$parsed['host']}$port$path",
            'user' => $user,
            'pass' => $pass,
        ];
    }

    private function httpClient(array $parsedRemote, int $timeout = 30): PendingRequest
    {
        $request = Http::withHeaders([
            'User-Agent' => 'git/2.40.0',
            'Accept' => '*/*',
        ])->timeout($timeout);

        if ($parsedRemote['user'] !== '' || $parsedRemote['pass'] !== '') {
            $request = $request->withBasicAuth($parsedRemote['user'], $parsedRemote['pass']);
        }

        return $request;
    }

    public function maskUrl(string $url): string
    {
        return (string) preg_replace('#(https?://)([^/@]+@)#i', '$1***@', $url);
    }
}
