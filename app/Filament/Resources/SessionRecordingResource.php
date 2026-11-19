<?php

namespace App\Filament\Resources;

use App\Enums\EncodingStatus;
use App\Enums\RecordingStatus;
use App\Filament\Resources\SessionRecordingResource\Pages\CreateSessionRecording;
use App\Filament\Resources\SessionRecordingResource\Pages\EditSessionRecording;
use App\Filament\Resources\SessionRecordingResource\Pages\ListSessionRecordings;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\AuditLog;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SessionRecordingResource extends Resource
{
    protected static ?string $model = SessionRecording::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-film';

    protected static string|UnitEnum|null $navigationGroup = 'Physical Sessions & Delivery';

    protected static ?string $navigationLabel = 'Session Recordings';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session Recording & Self-Hosted HLS Review (ADM-08, D-017)')
                    ->description('Uploaded recordings are saved on private company server storage and transcoded via FFmpeg into HLS playlists.')
                    ->columns(2)
                    ->schema([
                        Select::make('training_session_id')
                            ->label('Physical Training Session')
                            ->options(fn (): array => TrainingSession::query()->orderByDesc('starts_at')->get()->mapWithKeys(fn (TrainingSession $s) => [$s->id => "{$s->session_code} — {$s->title}"])->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('title')
                            ->label('Recording Title')
                            ->required()
                            ->maxLength(200),
                        FileUpload::make('source_storage_key')
                            ->label('Upload Recording Video (MP4, MOV, MKV, WebM — up to 2 GB)')
                            ->disk('recordings')
                            ->directory('sources')
                            ->visibility('private')
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes([
                                'video/mp4',
                                'video/quicktime',
                                'video/x-matroska',
                                'video/webm',
                                'application/octet-stream',
                            ])
                            ->maxSize((int) config('etms.max_upload_kb', 2097152))
                            ->columnSpanFull(),
                        TextInput::make('provider_video_id')
                            ->label('Stream Identifier')
                            ->helperText('Auto-generated if left blank on creation.')
                            ->maxLength(100),
                        TextInput::make('hls_manifest_key')
                            ->label('HLS Manifest Path (.m3u8)')
                            ->maxLength(500),
                        TextInput::make('duration_seconds')
                            ->label('Duration (Seconds)')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                        Select::make('encoding_status')
                            ->label('HLS Encoding Status')
                            ->options(collect(EncodingStatus::cases())->mapWithKeys(fn (EncodingStatus $s) => [$s->value => $s->label()]))
                            ->default(EncodingStatus::Pending->value)
                            ->required(),
                        Select::make('status')
                            ->label('Review & Publication Status')
                            ->options(collect(RecordingStatus::cases())->mapWithKeys(fn (RecordingStatus $s) => [$s->value => $s->label()]))
                            ->default(RecordingStatus::PendingReview->value)
                            ->required(),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('review_notes')
                            ->label('Administrator Review Notes / Rejection Reason')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('trainingSession.session_code')
                    ->label('Session')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('submitter.name')
                    ->label('Submitted By')
                    ->placeholder('—'),
                TextColumn::make('provider_video_id')
                    ->label('Stream ID')
                    ->fontFamily('mono'),
                TextColumn::make('hls_manifest_key')
                    ->label('HLS Manifest')
                    ->fontFamily('mono')
                    ->placeholder('Pending FFmpeg')
                    ->limit(30),
                TextColumn::make('encoding_status')
                    ->label('HLS Status')
                    ->badge()
                    ->formatStateUsing(fn (EncodingStatus|string $state): string => $state instanceof EncodingStatus ? $state->label() : ucfirst((string) $state)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RecordingStatus|string $state): string => $state instanceof RecordingStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (RecordingStatus|string $state): string => ($state instanceof RecordingStatus ? $state : RecordingStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('published_at')
                    ->label('Published (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('training_session_id')
                    ->label('Training Session')
                    ->relationship('trainingSession', 'title'),
                SelectFilter::make('status')
                    ->options(collect(RecordingStatus::cases())->mapWithKeys(fn (RecordingStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approveAndPublish')
                    ->label('Approve & Publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SessionRecording $record): bool => $record->status !== RecordingStatus::Published)
                    ->action(function (SessionRecording $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => RecordingStatus::Published->value,
                            'encoding_status' => filled($record->hls_manifest_key) || filled($record->source_storage_key)
                                ? EncodingStatus::Ready->value
                                : $record->encoding_status->value,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                            'published_at' => $record->published_at ?? now(),
                        ]);

                        AuditLog::record(
                            event: 'recording.published',
                            auditableType: SessionRecording::class,
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => RecordingStatus::Published->value],
                            reason: 'Administrator approved and published session recording.',
                        );

                        Notification::make()->title('Session recording approved and published')->success()->send();
                    }),
                Action::make('unpublishRecording')
                    ->label('Unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn (SessionRecording $record): bool => $record->status === RecordingStatus::Published)
                    ->action(function (SessionRecording $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => RecordingStatus::Archived->value,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        AuditLog::record(
                            event: 'recording.unpublished',
                            auditableType: SessionRecording::class,
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => RecordingStatus::Archived->value],
                            reason: 'Administrator unpublished/archived session recording.',
                        );

                        Notification::make()->title('Session recording unpublished')->warning()->send();
                    }),
                Action::make('rejectRecording')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (SessionRecording $record): bool => $record->status === RecordingStatus::PendingReview)
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Rejection Reason / Feedback for Instructor')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (SessionRecording $record, array $data): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => RecordingStatus::Rejected->value,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                            'review_notes' => (string) $data['review_notes'],
                        ]);

                        AuditLog::record(
                            event: 'recording.rejected',
                            auditableType: SessionRecording::class,
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => RecordingStatus::Rejected->value],
                            reason: (string) $data['review_notes'],
                        );

                        Notification::make()->title('Session recording rejected')->danger()->send();
                    }),
                Action::make('transcodeHls')
                    ->label('Transcode HLS')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (SessionRecording $record): bool => filled($record->source_storage_key))
                    ->action(function (SessionRecording $record): void {
                        ProcessVideoToHlsJob::dispatch(SessionRecording::class, $record->id);
                        Notification::make()->title('FFmpeg HLS transcoding job queued')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSessionRecordings::route('/'),
            'create' => CreateSessionRecording::route('/create'),
            'edit' => EditSessionRecording::route('/{record}/edit'),
        ];
    }
}
