<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EncodingStatus;
use App\Enums\RecordingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\AuditLog;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRecordingController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', SessionRecording::class);
        return Inertia::render('Admin/Recordings/Form', [
            'mode' => 'create',
            'sessions' => TrainingSession::query()->orderByDesc('starts_at')->get(['id', 'session_code', 'title'])->map(fn (TrainingSession $session): array => [
                'value' => (string) $session->id,
                'label' => "{$session->session_code} — {$session->title}",
            ])->values()->all(),
            'statuses' => collect(RecordingStatus::cases())->map(fn (RecordingStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SessionRecording::class);
        $data = $request->validate([
            'training_session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', array_column(RecordingStatus::cases(), 'value'))],
            'review_notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,application/octet-stream', 'max:2097152'],
        ]);

        $file = $request->file('file');
        $key = $file->store('sources', 'recordings');
        $status = RecordingStatus::from($data['status']);
        $recording = SessionRecording::query()->create([
            'training_session_id' => $data['training_session_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'provider' => 'local_hls',
            'provider_library_id' => 'local',
            'provider_video_id' => 'rec-ses-'.strtolower(\Illuminate\Support\Str::random(10)),
            'storage_disk' => 'recordings',
            'source_storage_key' => $key,
            'original_filename' => $file->getClientOriginalName(),
            'file_size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'encoding_status' => EncodingStatus::Pending,
            'status' => $status,
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $status === RecordingStatus::Published ? $request->user()->id : null,
            'reviewed_at' => $status === RecordingStatus::Published ? now() : null,
            'published_at' => $status === RecordingStatus::Published ? now() : null,
        ]);
        ProcessVideoToHlsJob::dispatch(SessionRecording::class, $recording->id);
        AuditLog::record(event: 'recording.created', auditableType: SessionRecording::class, auditableId: $recording->id, newValues: ['title' => $recording->title, 'status' => $status->value], actor: $request->user());
        return back()->with('success', 'Recording created and HLS transcoding queued.');
    }

    public function edit(SessionRecording $recording): Response
    {
        $this->authorize('update', $recording);
        return Inertia::render('Admin/Recordings/Form', [
            'mode' => 'edit',
            'recording' => [
                'id' => $recording->id,
                'training_session_id' => (string) $recording->training_session_id,
                'title' => $recording->title,
                'description' => $recording->description,
                'status' => $recording->status->value,
                'review_notes' => $recording->review_notes,
                'original_filename' => $recording->original_filename,
                'encoding_status' => $recording->encoding_status->label(),
            ],
            'sessions' => TrainingSession::query()->orderByDesc('starts_at')->get(['id', 'session_code', 'title'])->map(fn (TrainingSession $session): array => [
                'value' => (string) $session->id,
                'label' => "{$session->session_code} — {$session->title}",
            ])->values()->all(),
            'statuses' => collect(RecordingStatus::cases())->map(fn (RecordingStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values()->all(),
        ]);
    }

    public function update(Request $request, SessionRecording $recording): RedirectResponse
    {
        $this->authorize('update', $recording);
        $data = $request->validate([
            'training_session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', array_column(RecordingStatus::cases(), 'value'))],
            'review_notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,application/octet-stream', 'max:2097152'],
        ]);
        $oldStatus = $recording->status->value;
        $status = RecordingStatus::from($data['status']);
        $oldKey = $recording->source_storage_key;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $recording->source_storage_key = $file->store('sources', 'recordings');
            $recording->original_filename = $file->getClientOriginalName();
            $recording->file_size_bytes = $file->getSize();
            $recording->mime_type = $file->getClientMimeType();
            $recording->encoding_status = EncodingStatus::Pending;
            $recording->hls_manifest_key = null;
        }
        $recording->fill([
            'training_session_id' => $data['training_session_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => in_array($status, [RecordingStatus::Published, RecordingStatus::Rejected, RecordingStatus::Archived], true) ? $request->user()->id : $recording->reviewed_by,
            'reviewed_at' => in_array($status, [RecordingStatus::Published, RecordingStatus::Rejected, RecordingStatus::Archived], true) ? now() : $recording->reviewed_at,
            'published_at' => $status === RecordingStatus::Published ? ($recording->published_at ?? now()) : $recording->published_at,
        ]);
        $recording->save();
        if ($request->hasFile('file') && $oldKey && $oldKey !== $recording->source_storage_key) {
            \Illuminate\Support\Facades\Storage::disk('recordings')->delete($oldKey);
            ProcessVideoToHlsJob::dispatch(SessionRecording::class, $recording->id);
        }
        AuditLog::record(event: 'recording.updated', auditableType: SessionRecording::class, auditableId: $recording->id, oldValues: ['status' => $oldStatus], newValues: ['status' => $status->value], actor: $request->user());
        return redirect()->route('admin-vue.recordings.index')->with('success', 'Recording updated.');
    }

    public function destroy(Request $request, SessionRecording $recording): RedirectResponse
    {
        $this->authorize('delete', $recording);
        $key = $recording->source_storage_key;
        $manifest = $recording->hls_manifest_key;
        $recording->delete();
        if ($key) {
            \Illuminate\Support\Facades\Storage::disk('recordings')->delete($key);
        }
        if ($manifest) {
            \Illuminate\Support\Facades\Storage::disk('recordings')->delete($manifest);
        }
        AuditLog::record(event: 'recording.deleted', auditableType: SessionRecording::class, auditableId: $recording->id, oldValues: ['title' => $recording->title], actor: $request->user());
        return redirect()->route('admin-vue.recordings.index')->with('success', 'Recording deleted.');
    }
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $recordings = SessionRecording::query()
            ->with('trainingSession:id,session_code,title')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('trainingSession', fn ($session) => $session
                        ->where('session_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%"));
            }))
            ->when(RecordingStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (SessionRecording $recording): array => [
                'id' => $recording->id,
                'session' => $recording->trainingSession?->session_code,
                'sessionTitle' => $recording->trainingSession?->title,
                'title' => $recording->title,
                'status' => $recording->status?->value,
                'statusLabel' => $recording->status?->label(),
                'encodingStatus' => $recording->encoding_status?->label(),
                'submittedAt' => $recording->submitted_at?->toIso8601String(),
                'hasSource' => filled($recording->source_storage_key),
                'editUrl' => route('admin-vue.recordings.edit', $recording),
            ]);

        return Inertia::render('Admin/Recordings/Index', [
            'recordings' => $recordings,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(RecordingStatus::cases())->map(fn (RecordingStatus $item): array => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
        ]);
    }

    public function approve(SessionRecording $recording, Request $request): RedirectResponse
    {
        $oldStatus = $recording->status->value;
        $recording->update([
            'status' => RecordingStatus::Published,
            'encoding_status' => filled($recording->hls_manifest_key) || filled($recording->source_storage_key)
                ? EncodingStatus::Ready
                : $recording->encoding_status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'published_at' => $recording->published_at ?? now(),
        ]);
        AuditLog::record(
            event: 'recording.published',
            auditableType: SessionRecording::class,
            auditableId: $recording->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => RecordingStatus::Published->value],
            reason: 'Administrator approved and published session recording.',
            actor: $request->user(),
        );

        return back()->with('success', 'Recording approved and published.');
    }

    public function unpublish(SessionRecording $recording, Request $request): RedirectResponse
    {
        $oldStatus = $recording->status->value;
        $recording->update(['status' => RecordingStatus::Archived, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        AuditLog::record(
            event: 'recording.unpublished',
            auditableType: SessionRecording::class,
            auditableId: $recording->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => RecordingStatus::Archived->value],
            reason: 'Administrator unpublished/archived session recording.',
            actor: $request->user(),
        );

        return back()->with('success', 'Recording unpublished.');
    }

    public function reject(Request $request, SessionRecording $recording): RedirectResponse
    {
        $data = $request->validate(['review_notes' => ['required', 'string', 'max:500']]);
        $oldStatus = $recording->status->value;
        $recording->update([
            'status' => RecordingStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);
        AuditLog::record(
            event: 'recording.rejected',
            auditableType: SessionRecording::class,
            auditableId: $recording->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => RecordingStatus::Rejected->value],
            reason: $data['review_notes'],
            actor: $request->user(),
        );

        return back()->with('success', 'Recording rejected.');
    }

    public function transcode(SessionRecording $recording): RedirectResponse
    {
        if (! filled($recording->source_storage_key)) {
            throw new DomainException('This recording has no source file to transcode.');
        }

        ProcessVideoToHlsJob::dispatch(SessionRecording::class, $recording->id);

        return back()->with('success', 'HLS transcoding job queued.');
    }
}
