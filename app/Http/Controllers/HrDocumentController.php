<?php

namespace App\Http\Controllers;

use App\Models\HrDocument;
use App\Models\HrDocumentAudit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrDocumentController extends Controller
{
    protected array $auditedFields = [
        'title',
        'document_type',
        'status',
        'user_id',
        'start_date',
        'end_date',
        'expires_at',
        'notes',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = HrDocument::with(['user:id,name,email', 'uploader:id,name']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('document_type') && $request->document_type !== 'all') {
            $query->where('document_type', $request->document_type);
        }

        $documents = $query->latest()->limit(200)->get()->map(function (HrDocument $document) {
            return [
                'id' => $document->id,
                'title' => $document->title,
                'document_type' => $document->document_type,
                'status' => $document->status,
                'user' => $document->user ? [
                    'id' => $document->user->id,
                    'name' => $document->user->name,
                    'email' => $document->user->email,
                ] : null,
                'uploader' => $document->uploader ? [
                    'id' => $document->uploader->id,
                    'name' => $document->uploader->name,
                ] : null,
                'start_date' => $document->start_date?->toDateString(),
                'end_date' => $document->end_date?->toDateString(),
                'expires_at' => $document->expires_at?->toDateString(),
                'notes' => $document->notes,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'download_url' => route('documents.download', $document),
                'created_at' => $document->created_at->toDateTimeString(),
            ];
        });

        $soonDate = now()->addDays(30)->toDateString();

        return response()->json([
            'documents' => $documents,
            'metrics' => [
                'total' => HrDocument::count(),
                'active' => HrDocument::where('status', 'activo')->count(),
                'expiring_soon' => HrDocument::whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now()->toDateString(), $soonDate])
                    ->count(),
                'expired' => HrDocument::whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', now()->toDateString())
                    ->count(),
            ],
            'users' => User::whereNotIn('role', ['admin', 'superadmin'])
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:40'],
            'status' => ['nullable', 'in:activo,borrador,vencido'],
            'user_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $path = $request->file('file')->store('private/hr-documents');

        $document = HrDocument::create([
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'status' => $data['status'] ?? 'activo',
            'user_id' => $data['user_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'file_path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'mime_type' => $request->file('file')->getClientMimeType() ?? 'application/octet-stream',
            'file_size' => $request->file('file')->getSize() ?? 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $this->logAudit($document, 'created', $request->user()->id, null, $document->only($this->auditedFields));

        return response()->json(['id' => $document->id], 201);
    }

    public function update(Request $request, HrDocument $document): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'document_type' => ['sometimes', 'string', 'max:40'],
            'status' => ['sometimes', 'in:activo,borrador,vencido'],
            'user_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $old = $document->only($this->auditedFields);
        $expiresChanged = array_key_exists('expires_at', $data) && (($data['expires_at'] ?? null) !== $document->expires_at?->toDateString());

        if ($request->hasFile('file')) {
            Storage::delete($document->file_path);
            $path = $request->file('file')->store('private/hr-documents');
            $data['file_path'] = $path;
            $data['original_name'] = $request->file('file')->getClientOriginalName();
            $data['mime_type'] = $request->file('file')->getClientMimeType() ?? 'application/octet-stream';
            $data['file_size'] = $request->file('file')->getSize() ?? 0;
        }

        if ($expiresChanged) {
            $data['last_alert_sent_at'] = null;
            $data['last_alert_type'] = null;
        }

        $document->update($data);
        $document->refresh();

        $this->logAudit(
            $document,
            'updated',
            $request->user()?->id,
            $old,
            $document->only($this->auditedFields)
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(HrDocument $document): JsonResponse
    {
        $old = $document->only($this->auditedFields);
        $this->logAudit($document, 'deleted', auth()->id(), $old, null);

        Storage::delete($document->file_path);
        $document->delete();

        return response()->json(['ok' => true]);
    }

    public function download(HrDocument $document): StreamedResponse
    {
        abort_if(! Storage::exists($document->file_path), 404, 'Archivo no encontrado');

        $this->logAudit($document, 'downloaded', auth()->id(), null, [
            'original_name' => $document->original_name,
        ]);

        return Storage::download($document->file_path, $document->original_name);
    }

    public function audits(HrDocument $document): JsonResponse
    {
        $audits = $document->audits()
            ->with('user:id,name,email')
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (HrDocumentAudit $audit) {
                return [
                    'id' => $audit->id,
                    'action' => $audit->action,
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'user' => $audit->user ? [
                        'id' => $audit->user->id,
                        'name' => $audit->user->name,
                        'email' => $audit->user->email,
                    ] : null,
                    'created_at' => $audit->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'document_id' => $document->id,
            'audits' => $audits,
        ]);
    }

    protected function logAudit(
        HrDocument $document,
        string $action,
        ?int $userId,
        ?array $oldValues,
        ?array $newValues
    ): void {
        HrDocumentAudit::create([
            'hr_document_id' => $document->id,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
