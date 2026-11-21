<?php

namespace App\Support;

use App\Models\AuditLog;

/** Catat satu aktivitas ke log. Gagal mencatat tidak boleh menggagalkan proses utamanya. */
class Audit
{
    public static function log(string $action, string $subjectType, ?int $subjectId, string $description, ?array $changes = null): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => mb_substr($description, 0, 255),
                'changes' => $changes ?: null,
                'ip' => app()->runningInConsole() ? null : request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
