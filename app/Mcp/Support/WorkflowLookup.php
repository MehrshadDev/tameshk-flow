<?php

namespace App\Mcp\Support;

use App\Mcp\Exceptions\McpToolException;
use App\Models\User;
use App\Models\Workflow;

final class WorkflowLookup
{
    public function findForUser(User $user, mixed $identifier): Workflow
    {
        $raw = trim((string) ($identifier ?? ''));

        if ($raw === '') {
            throw McpToolException::because('شناسه ورکفلو (workflow_id) الزامی است.');
        }

        $query = $user->workflows();

        $workflow = ctype_digit($raw)
            ? $query->where('id', (int) $raw)->first()
            : $query->where('public_id', $raw)->first();

        if (! $workflow) {
            throw McpToolException::because("ورکفلویی با شناسه [$raw] در حساب کاربری یافت نشد.");
        }

        return $workflow;
    }

    public function truncatePayload(mixed $data, int $maxStringLength = 3000, int $depth = 0): mixed
    {
        if ($depth > 8) {
            return '[max depth reached]';
        }

        if (is_string($data)) {
            if (mb_strlen($data) > $maxStringLength) {
                return mb_substr($data, 0, $maxStringLength) . '... [truncated]';
            }

            return $data;
        }

        if (is_array($data)) {
            $result = [];
            $count = 0;

            foreach ($data as $key => $value) {
                if (++$count > 100) {
                    $result['_truncated_items'] = count($data) - 100;
                    break;
                }

                $result[$key] = $this->truncatePayload($value, $maxStringLength, $depth + 1);
            }

            return $result;
        }

        return $data;
    }
}
