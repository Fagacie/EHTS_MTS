<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\CustomerType;
use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->record;
        $profile = $record->customerProfile;

        if ($profile !== null) {
            $data['profile_customer_type'] = $profile->customer_type instanceof CustomerType
                ? $profile->customer_type->value
                : ($profile->customer_type ?: CustomerType::Individual->value);
            $data['profile_phone'] = $profile->phone;
            $data['profile_job_title'] = $profile->job_title;
            $data['profile_company_name'] = $profile->company_name;
            $data['profile_company_registration_no'] = $profile->company_registration_no;
            $data['profile_billing_contact_name'] = $profile->billing_contact_name;
            $data['profile_billing_email'] = $profile->billing_email;
            $data['profile_billing_phone'] = $profile->billing_phone;
            $data['profile_billing_address_line_1'] = $profile->billing_address_line_1;
            $data['profile_billing_address_line_2'] = $profile->billing_address_line_2;
            $data['profile_billing_city'] = $profile->billing_city;
            $data['profile_billing_state'] = $profile->billing_state;
            $data['profile_billing_postcode'] = $profile->billing_postcode;
            $data['profile_billing_country'] = $profile->billing_country ?: 'MY';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->record;
        $oldRoles = $record->roles()->pluck('slug')->all();
        $roleSlugs = $this->data['role_slugs'] ?? [];

        if (is_array($roleSlugs) && $roleSlugs !== []) {
            $record->syncRolesBySlugs($roleSlugs, Auth::id());

            if ($oldRoles !== $roleSlugs) {
                AuditLog::record(
                    event: 'user.roles_updated',
                    auditableType: 'users',
                    auditableId: $record->id,
                    oldValues: ['roles' => $oldRoles],
                    newValues: ['roles' => $roleSlugs],
                );
            }
        }

        UserResource::syncCustomerProfileFromFormData($record, $this->data);
    }
}
