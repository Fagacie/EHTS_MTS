<?php

namespace App\Filament\Resources\ModuleEntitlementResource\Pages;

use App\Enums\EntitlementStatus;
use App\Filament\Resources\ModuleEntitlementResource;
use App\Models\AuditLog;
use App\Services\Notification\EtmsNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateModuleEntitlement extends CreateRecord
{
    protected static string $resource = ModuleEntitlementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['grant_source'] = 'admin_grant';
        $data['status'] = EntitlementStatus::Active->value;
        $data['order_id'] = null;
        $data['order_item_id'] = null;
        $data['granted_at'] = now();
        $data['granted_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditLog::record(
            event: 'module_entitlement.admin_granted',
            auditableType: $this->record::class,
            auditableId: $this->record->id,
            oldValues: null,
            newValues: [
                'user_id' => $this->record->user_id,
                'module_id' => $this->record->module_id,
                'grant_source' => 'admin_grant',
            ],
            reason: $this->record->grant_reason,
        );

        $this->record->loadMissing(['user', 'module']);
        if ($this->record->user && $this->record->module) {
            app(EtmsNotificationService::class)->notifyUser(
                user: $this->record->user,
                eventType: 'module.access_granted',
                title: "Module Learning Access Granted: {$this->record->module->title}",
                message: "You now have access to learning materials for {$this->record->module->title} ({$this->record->module->code}).",
                actionUrl: route('portal.modules.show', $this->record->module, false),
                actionLabel: 'Open Learning Materials',
                details: [
                    'Module' => "{$this->record->module->code} — {$this->record->module->title}",
                    'Grant Type' => 'Administrator Grant',
                ],
                reference: $this->record->module->code,
            );
        }
    }
}
