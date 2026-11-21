<?php

namespace App\Models\Concerns;

use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;

/**
 * Catat tambah/ubah/hapus model ke log aktivitas.
 * Model memakai trait ini dengan auditType() dan auditName().
 */
trait Auditable
{
    protected static array $auditHidden = ['password', 'remember_token', 'created_at', 'updated_at'];

    public static function bootAuditable(): void
    {
        static::created(function (Model $m) {
            Audit::log('tambah', $m->auditType(), $m->getKey(), $m->auditType().' '.$m->auditName().' ditambahkan', self::auditValues($m->getAttributes(), null));
        });
        static::updated(function (Model $m) {
            $changes = [];
            foreach ($m->getChanges() as $field => $new) {
                if (in_array($field, static::$auditHidden, true)) {
                    continue;
                }
                $changes[$field] = [$m->getOriginal($field), $new];
            }
            if ($changes) {
                Audit::log('ubah', $m->auditType(), $m->getKey(), $m->auditType().' '.$m->auditName().' diubah', $changes);
            }
        });
        static::deleted(function (Model $m) {
            Audit::log('hapus', $m->auditType(), $m->getKey(), $m->auditType().' '.$m->auditName().' dihapus', self::auditValues($m->getOriginal(), true));
        });
    }

    private static function auditValues(array $attrs, ?bool $old): array
    {
        $out = [];
        foreach ($attrs as $k => $v) {
            if (in_array($k, static::$auditHidden, true) || $k === 'id') {
                continue;
            }
            $out[$k] = $old ? [$v, null] : [null, $v];
        }
        return $out;
    }
}
