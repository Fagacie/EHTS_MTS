<?php

namespace App\Filament\Resources\TrainingSessionResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\Resources\TrainingSessionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTrainingSession extends CreateRecord
{
    protected static string $resource = TrainingSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['currency'] = 'MYR';
        $data['reserved_seats_count'] = 0;
        $data['confirmed_seats_count'] = 0;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        if (($data['status'] ?? null) === SessionStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
