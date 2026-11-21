<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $f = [
            'q' => trim((string) $request->input('q', '')),
            'action' => $request->input('action'),
            'subject_type' => $request->input('subject_type'),
            'user_id' => (int) $request->input('user_id') ?: null,
            'dari' => $request->input('dari'),
            'sampai' => $request->input('sampai'),
        ];

        $logs = AuditLog::with('user:id,name')
            ->when($f['q'] !== '', fn ($q) => $q->where('description', 'like', '%'.$f['q'].'%'))
            ->when($f['action'], fn ($q, $v) => $q->where('action', $v))
            ->when($f['subject_type'], fn ($q, $v) => $q->where('subject_type', $v))
            ->when($f['user_id'], fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['dari'], fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($f['sampai'], fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.masterdata.audit.index', [
            'logs' => $logs,
            'filters' => $f,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'types' => AuditLog::query()->distinct()->orderBy('subject_type')->pluck('subject_type'),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
