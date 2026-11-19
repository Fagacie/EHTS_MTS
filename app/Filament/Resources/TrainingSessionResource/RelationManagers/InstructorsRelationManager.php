<?php

namespace App\Filament\Resources\TrainingSessionResource\RelationManagers;

use App\Enums\RoleSlug;
use App\Models\SessionInstructor;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InstructorsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessionInstructors';

    protected static ?string $title = 'Assigned Session Instructors (Multi-Instructor Support)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('instructor_id')
                    ->label('Instructor')
                    ->options(fn (): array => User::query()
                        ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Instructor->value))
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (User $u) => [
                            $u->id => ($u->instructorProfile?->display_name ?? $u->name)." ({$u->email})",
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('role_label')
                    ->label('Session Role Label')
                    ->default('Lead Instructor')
                    ->placeholder('Lead Instructor / Co-Instructor / Lab Facilitator')
                    ->required()
                    ->maxLength(80),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instructor.name')
                    ->label('Instructor Name')
                    ->formatStateUsing(fn (SessionInstructor $record): string => $record->instructor?->instructorProfile?->display_name ?? $record->instructor?->name ?? '—')
                    ->weight('semibold'),
                TextColumn::make('instructor.email')
                    ->label('Email')
                    ->fontFamily('mono'),
                TextColumn::make('role_label')
                    ->label('Session Role')
                    ->badge()
                    ->color('info'),
                TextColumn::make('assigned_at')
                    ->label('Assigned (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Assign Instructor')
                    ->mutateDataUsing(function (array $data): array {
                        $data['assigned_by'] = Auth::id();
                        $data['assigned_at'] = now();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Remove'),
            ]);
    }
}
