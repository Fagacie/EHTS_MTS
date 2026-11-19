<?php

namespace App\Filament\Resources;

use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Filament\Resources\ModuleVideoResource\Pages\CreateModuleVideo;
use App\Filament\Resources\ModuleVideoResource\Pages\EditModuleVideo;
use App\Filament\Resources\ModuleVideoResource\Pages\ListModuleVideos;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\ModuleVideo;
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

class ModuleVideoResource extends Resource
{
    protected static ?string $model = ModuleVideo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-video-camera';

    protected static string|UnitEnum|null $navigationGroup = 'Training & Catalogue';

    protected static ?string $navigationLabel = 'Module Videos (Self-Hosted)';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module Video Curriculum & Self-Hosted HLS Storage (FR-07, ADM-05)')
                    ->description('Uploaded videos are stored privately on the company server and automatically transcoded via FFmpeg into protected HLS streams.')
                    ->columns(2)
                    ->schema([
                        Select::make('module_id')
                            ->label('Training Module')
                            ->options(fn (): array => Module::query()->orderBy('code')->get()->mapWithKeys(fn (Module $m) => [$m->id => "{$m->code} — {$m->title}"])->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('title')
                            ->label('Video Title')
                            ->required()
                            ->maxLength(200),
                        FileUpload::make('source_storage_key')
                            ->label('Upload Video File (MP4, MOV, MKV, WebM — up to 2 GB)')
                            ->disk('videos')
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
                            ->label('Video Stream Identifier')
                            ->helperText('Auto-generated if left blank on creation.')
                            ->maxLength(100),
                        TextInput::make('hls_manifest_key')
                            ->label('HLS Manifest Path (.m3u8)')
                            ->helperText('Populated automatically after FFmpeg HLS transcoding completes.')
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
                            ->label('Publication Status')
                            ->options(collect(MaterialStatus::cases())->mapWithKeys(fn (MaterialStatus $s) => [$s->value => $s->label()]))
                            ->default(MaterialStatus::Published->value)
                            ->required(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('module.code')
                    ->label('Module')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('provider_video_id')
                    ->label('Stream ID')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('hls_manifest_key')
                    ->label('HLS Manifest')
                    ->fontFamily('mono')
                    ->placeholder('Pending FFmpeg')
                    ->limit(32),
                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn (ModuleVideo $record): string => $record->formattedDuration()),
                TextColumn::make('encoding_status')
                    ->label('HLS Status')
                    ->badge()
                    ->formatStateUsing(fn (EncodingStatus|string $state): string => $state instanceof EncodingStatus ? $state->label() : ucfirst((string) $state)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (MaterialStatus|string $state): string => $state instanceof MaterialStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (MaterialStatus|string $state): string => ($state instanceof MaterialStatus ? $state : MaterialStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'title'),
                SelectFilter::make('status')
                    ->options(collect(MaterialStatus::cases())->mapWithKeys(fn (MaterialStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('transcodeHls')
                    ->label('Transcode HLS')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (ModuleVideo $record): bool => filled($record->source_storage_key))
                    ->action(function (ModuleVideo $record): void {
                        ProcessVideoToHlsJob::dispatch(ModuleVideo::class, $record->id);
                        Notification::make()->title('FFmpeg HLS transcoding job queued')->success()->send();
                    }),
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->visible(fn (ModuleVideo $record): bool => $record->status !== MaterialStatus::Published)
                    ->action(function (ModuleVideo $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => MaterialStatus::Published->value,
                            'published_at' => $record->published_at ?? now(),
                        ]);

                        AuditLog::record(
                            event: 'module_video.published',
                            auditableType: ModuleVideo::class,
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => MaterialStatus::Published->value],
                            reason: 'Administrator published module video lesson.',
                            actor: Auth::user(),
                        );

                        Notification::make()->title('Module video published')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModuleVideos::route('/'),
            'create' => CreateModuleVideo::route('/create'),
            'edit' => EditModuleVideo::route('/{record}/edit'),
        ];
    }
}
