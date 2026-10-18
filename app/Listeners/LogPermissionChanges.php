<?php

namespace App\Listeners;

use App\Support\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Jejak audit pemberian dan pencabutan peran/permission (OWASP A09).
 * Membutuhkan `events_enabled` = true di config/permission.php.
 */
class LogPermissionChanges
{
    public function handleRoleAttached(RoleAttachedEvent $event): void
    {
        $this->record('permission.role_attached', $event->model, $this->names($event->rolesOrIds, Role::class));
    }

    public function handleRoleDetached(RoleDetachedEvent $event): void
    {
        $this->record('permission.role_detached', $event->model, $this->names($event->rolesOrIds, Role::class));
    }

    public function handlePermissionAttached(PermissionAttachedEvent $event): void
    {
        $this->record('permission.permission_attached', $event->model, $this->names($event->permissionsOrIds, Permission::class));
    }

    public function handlePermissionDetached(PermissionDetachedEvent $event): void
    {
        $this->record('permission.permission_detached', $event->model, $this->names($event->permissionsOrIds, Permission::class));
    }

    private function record(string $event, Model $model, string $names): void
    {
        $actor = auth()->id();

        AuditLog::record($event, [
            'subject_type' => class_basename($model),
            'subject_id' => is_int($model->getKey()) || is_string($model->getKey()) ? $model->getKey() : null,
            'names' => $names,
            'actor_id' => is_int($actor) || is_string($actor) ? $actor : null,
        ]);
    }

    /**
     * Event bisa membawa ID, model, atau koleksi; semuanya diubah menjadi daftar nama.
     *
     * @param  class-string<Role|Permission>  $modelClass
     */
    private function names(mixed $items, string $modelClass): string
    {
        $items = Collection::wrap($items instanceof Collection ? $items->all() : $items);

        $ids = $items->reject(fn (mixed $item) => $item instanceof Model)->filter(fn (mixed $item) => is_int($item) || is_string($item));
        $names = $items->filter(fn (mixed $item) => $item instanceof Model)->map(fn (Model $item) => (string) $item->getAttribute('name'));

        if ($ids->isNotEmpty()) {
            $names = $names->merge($modelClass::query()->whereKey($ids->all())->pluck('name'));
        }

        return $names->filter()->unique()->sort()->implode(',');
    }
}
