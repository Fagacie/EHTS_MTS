<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Enums\AnnouncementStatus;
use App\Filament\Resources\AnnouncementResource;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendNow')
                ->label('Send Announcement Now')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== AnnouncementStatus::Sent)
                ->action(function (EtmsNotificationService $notifier): void {
                    /** @var User $admin */
                    $admin = Auth::user();
                    $count = $notifier->dispatchAnnouncement($this->record, $admin);
                    Notification::make()
                        ->title("Announcement dispatched to {$count} recipient(s).")
                        ->success()
                        ->send();
                }),
        ];
    }
}
