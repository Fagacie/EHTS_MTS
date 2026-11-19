<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email_verified_at'] ??= now();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User $record */
        $record = $this->record;
        $roleSlugs = $this->data['role_slugs'] ?? [];

        if (is_array($roleSlugs) && $roleSlugs !== []) {
            $record->syncRolesBySlugs($roleSlugs, Auth::id());
        }

        UserResource::syncCustomerProfileFromFormData($record, $this->data);

        AuditLog::record(
            event: 'user.created_by_admin',
            auditableType: 'users',
            auditableId: $record->id,
            newValues: [
                'email' => $record->email,
                'roles' => $roleSlugs,
            ],
        );
    }
}
