<?php

namespace App\Filament\Resources\TrainingSessionResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\Resources\TrainingSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTrainingSession extends EditRecord
{
    protected static string $resource = TrainingSessionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        if (($data['status'] ?? null) === SessionStatus::Published->value && empty($this->record->published_at)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->record->bookings()->exists()),
        ];
    }
}
