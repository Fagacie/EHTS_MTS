<?php

namespace App\Models;

use App\Enums\RecordingAccessPolicy;
use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'module_id',
        'session_code',
        'title',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'timezone',
        'venue_name',
        'venue_address',
        'venue_city',
        'venue_state',
        'venue_postcode',
        'venue_map_url',
        'venue_notes',
        'capacity',
        'reserved_seats_count',
        'confirmed_seats_count',
        'registration_opens_at',
        'registration_closes_at',
        'price',
        'currency',
        'recording_access_policy',
        'status',
        'published_at',
        'closed_at',
        'completion_notes',
        'completion_submitted_by',
        'completion_submitted_at',
        'completed_at',
        'completed_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'capacity' => 'integer',
            'reserved_seats_count' => 'integer',
            'confirmed_seats_count' => 'integer',
            'price' => 'decimal:2',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'recording_access_policy' => RecordingAccessPolicy::class,
            'status' => SessionStatus::class,
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'completion_submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'session_instructors', 'training_session_id', 'instructor_id')
            ->withPivot(['id', 'role_label', 'assigned_by', 'assigned_at'])
            ->orderByPivot('id');
    }

    public function sessionInstructors(): HasMany
    {
        return $this->hasMany(SessionInstructor::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function confirmedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', 'confirmed');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(SessionRecording::class)->orderBy('sort_order')->orderBy('id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SessionStatus::Published->value,
            SessionStatus::Full->value,
            SessionStatus::RegistrationClosed->value,
        ])->whereHas('module', fn (Builder $q) => $q->published());
    }

    public function scopeUpcomingPublic(Builder $query): Builder
    {
        return $query->publicVisible()
            ->where('starts_at', '>', now())
            ->orderBy('starts_at');
    }

    public function occupiedSeats(): int
    {
        return (int) $this->reserved_seats_count + (int) $this->confirmed_seats_count;
    }

    public function remainingSeats(): int
    {
        return max(0, (int) $this->capacity - $this->occupiedSeats());
    }

    public function isRegistrationWindowOpen(): bool
    {
        $now = now();

        if ($this->registration_opens_at !== null && $now->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at !== null && $now->gte($this->registration_closes_at)) {
            return false;
        }

        return $this->starts_at !== null && $now->lt($this->starts_at);
    }

    public function isOpenForBooking(): bool
    {
        return $this->status === SessionStatus::Published
            && $this->remainingSeats() > 0
            && $this->isRegistrationWindowOpen();
    }

    public function formattedPrice(): string
    {
        return 'RM '.number_format((float) $this->price, 2);
    }

    public function formattedDuration(): string
    {
        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$minutes}m";
    }

    public function formattedSchedule(): string
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return 'TBA';
        }

        $tz = $this->timezone ?: 'Asia/Kuala_Lumpur';
        $start = $this->starts_at->copy()->timezone($tz);
        $end = $this->ends_at->copy()->timezone($tz);

        if ($start->isSameDay($end)) {
            return $start->format('d M Y').' · '.$start->format('h:i A').' – '.$end->format('h:i A').' MYT';
        }

        return $start->format('d M Y, h:i A').' – '.$end->format('d M Y, h:i A').' MYT';
    }
}
