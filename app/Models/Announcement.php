<?php

namespace App\Models;

use App\Enums\AnnouncementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'title',
        'body',
        'audience_type',
        'training_session_id',
        'module_id',
        'target_role',
        'send_email',
        'send_in_app',
        'status',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'send_email' => 'boolean',
            'send_in_app' => 'boolean',
            'status' => AnnouncementStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function audienceLabel(): string
    {
        return match ($this->audience_type) {
            'session_participants' => 'Session Participants',
            'module_entitled_users' => 'Module Entitled Users',
            'role' => 'Specific Role ('.($this->target_role ?: '—').')',
            'selected_users' => 'Selected Users',
            default => 'All Active Customers',
        };
    }
}
