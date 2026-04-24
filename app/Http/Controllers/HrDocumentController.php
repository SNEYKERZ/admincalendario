<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\CompanySettings;
use App\Models\HrDocument;
use App\Models\HrDocumentAudit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrDocumentController extends Controller
{
    protected array $auditedFields = [
        'title',
        'document_type',
        'document_mode',
        'status',
        'user_id',
        'template_id',
        'area_id',
        'signature_status',
        'signature_method',
        'signature_name',
        'signature_requested_at',
        'signed_at',
        'signed_by',
        'rejected_at',
        'rejection_reason',
        'is_personal_upload',
        'start_date',
        'end_date',
        'expires_at',
        'notes',
        'template_content',
        'rendered_content',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $isAdmin = $this->isAdmin($authUser);
        $isSuperAdmin = $this->isSuperAdmin($authUser);

        $query = HrDocument::with([
            'user:id,name,email',
            'uploader:id,name',
            'template:id,title,document_mode',
            'area:id,name',
            'signer:id,name,email',
        ]);

        if (! $isAdmin) {
            $query->where(function ($builder) use ($authUser) {
                $builder->where('user_id', $authUser->id)
                    ->orWhere('uploaded_by', $authUser->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('template', fn ($t) => $t->where('title', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('document_mode') && $request->document_mode !== 'all') {
            $query->where('document_mode', $request->document_mode);
        }

        if ($request->filled('document_type') && $request->document_type !== 'all') {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('signature_status') && $request->signature_status !== 'all') {
            $query->where('signature_status', $request->signature_status);
        }

        if ($isAdmin && $request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', (int) $request->user_id);
        }

        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $query->where('area_id', (int) $request->area_id);
        }

        $documents = $query->latest()->limit(200)->get()->map(function (HrDocument $document) {
            return [
                'id' => $document->id,
                'title' => $document->title,
                'document_type' => $document->document_type,
                'document_mode' => $document->document_mode,
                'status' => $document->status,
                'user' => $document->user ? [
                    'id' => $document->user->id,
                    'name' => $document->user->name,
                    'email' => $document->user->email,
                ] : null,
                'template' => $document->template ? [
                    'id' => $document->template->id,
                    'title' => $document->template->title,
                ] : null,
                'area' => $document->area ? [
                    'id' => $document->area->id,
                    'name' => $document->area->name,
                ] : null,
                'uploader' => $document->uploader ? [
                    'id' => $document->uploader->id,
                    'name' => $document->uploader->name,
                ] : null,
                'signer' => $document->signer ? [
                    'id' => $document->signer->id,
                    'name' => $document->signer->name,
                    'email' => $document->signer->email,
                ] : null,
                'signature_status' => $document->signature_status,
                'signature_method' => $document->signature_method,
                'signature_name' => $document->signature_name,
                'signature_data' => $document->signature_data,
                'signature_requested_at' => $document->signature_requested_at?->toDateTimeString(),
                'signed_at' => $document->signed_at?->toDateTimeString(),
                'rejected_at' => $document->rejected_at?->toDateTimeString(),
                'rejection_reason' => $document->rejection_reason,
                'is_personal_upload' => $document->is_personal_upload,
                'start_date' => $document->start_date?->toDateString(),
                'end_date' => $document->end_date?->toDateString(),
                'expires_at' => $document->expires_at?->toDateString(),
                'notes' => $document->notes,
                'template_content' => $document->template_content,
                'rendered_content' => $document->rendered_content,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'download_url' => route('documents.download', $document),
                'created_at' => $document->created_at->toDateTimeString(),
            ];
        });

        $soonDate = now()->addDays(30)->toDateString();

        $metricsQuery = HrDocument::query();
        if (! $isAdmin) {
            $metricsQuery->where(function ($builder) use ($authUser) {
                $builder->where('user_id', $authUser->id)
                    ->orWhere('uploaded_by', $authUser->id);
            });
        }

        return response()->json([
            'documents' => $documents,
            'metrics' => [
                'total' => (clone $metricsQuery)->count(),
                'active' => (clone $metricsQuery)->where('status', 'activo')->count(),
                'pending_signature' => (clone $metricsQuery)->where('signature_status', 'pending')->count(),
                'signed' => (clone $metricsQuery)->where('signature_status', 'signed')->count(),
                'rejected' => (clone $metricsQuery)->where('signature_status', 'rejected')->count(),
                'expiring_soon' => (clone $metricsQuery)->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now()->toDateString(), $soonDate])
                    ->count(),
                'expired' => (clone $metricsQuery)->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', now()->toDateString())
                    ->count(),
            ],
            'users' => $isAdmin
                ? User::where('role', UserRole::COLLABORATOR->value)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email', 'area_id'])
                : [],
            'areas' => $isAdmin || $isSuperAdmin
                ? Area::active()->ordered()->get(['id', 'name'])
                : [],
            'templates' => $isAdmin
                ? HrDocument::query()
                    ->where('document_mode', 'template')
                    ->orderBy('title')
                    ->get(['id', 'title', 'area_id', 'template_content'])
                : [],
            'template_variables' => $this->templateVariablesCatalog(),
            'permissions' => [
                'is_admin' => $isAdmin,
                'is_superadmin' => $isSuperAdmin,
                'can_manage_templates' => $isSuperAdmin,
                'can_sign' => true,
                'max_personal_uploads' => 5,
            ],
            'current_user_id' => $authUser->id,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $isAdmin = $this->isAdmin($authUser);
        $isSuperAdmin = $this->isSuperAdmin($authUser);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:40'],
            'document_mode' => [
                'required',
                Rule::in($isAdmin ? ['template', 'derived', 'user'] : ['user']),
            ],
            'status' => ['nullable', 'in:activo,borrador,vencido,pendiente,firmado,rechazado'],
            'user_id' => ['nullable', 'exists:users,id'],
            'template_id' => ['nullable', 'exists:hr_documents,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'signature_status' => ['nullable', 'in:not_required,pending,signed,rejected'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'template_content' => ['nullable', 'string'],
            'is_personal_upload' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        if (! $isAdmin) {
            $personalUploads = HrDocument::query()
                ->where('uploaded_by', $authUser->id)
                ->where(function ($query) use ($authUser) {
                    $query->where('is_personal_upload', true)
                        ->orWhere('user_id', $authUser->id);
                })
                ->count();

            if ($personalUploads >= 5) {
                throw ValidationException::withMessages([
                    'file' => 'Has alcanzado el máximo de 5 documentos personales.',
                ]);
            }
        }

        if (! $isSuperAdmin && $data['document_mode'] === 'template') {
            abort(403, 'Solo el superadministrador puede gestionar plantillas generales.');
        }

        if ($data['document_mode'] === 'derived') {
            if (empty($data['template_id'])) {
                throw ValidationException::withMessages([
                    'template_id' => 'Un documento derivado debe referenciar una plantilla base.',
                ]);
            }

            $template = HrDocument::query()->findOrFail((int) $data['template_id']);
            if ($template->document_mode !== 'template') {
                throw ValidationException::withMessages([
                    'template_id' => 'La referencia seleccionada no es una plantilla válida.',
                ]);
            }
        }

        if (! empty($data['template_id']) && $data['document_mode'] !== 'template') {
            $template = HrDocument::query()->findOrFail((int) $data['template_id']);
            if ($template->document_mode !== 'template') {
                throw ValidationException::withMessages([
                    'template_id' => 'La referencia seleccionada no es una plantilla válida.',
                ]);
            }
        }

        if ($data['document_mode'] === 'template') {
            $data['user_id'] = null;
            $data['template_id'] = null;
            $data['signature_status'] = 'not_required';
            $data['status'] = $data['status'] ?? 'borrador';
        }

        if ($data['document_mode'] === 'derived' && empty($data['user_id'])) {
            throw ValidationException::withMessages([
                'user_id' => 'Los documentos derivados deben asignarse a un colaborador.',
            ]);
        }

        if ($isAdmin && $data['document_mode'] === 'user' && empty($data['user_id'])) {
            throw ValidationException::withMessages([
                'user_id' => 'El documento por usuario debe asignarse a un colaborador.',
            ]);
        }

        if (! $isAdmin) {
            $data['document_mode'] = 'user';
            $data['user_id'] = $authUser->id;
            $data['template_id'] = null;
            $data['area_id'] = $authUser->area_id;
            $data['status'] = $data['status'] ?? 'pendiente';
            $data['signature_status'] = 'not_required';
            $data['is_personal_upload'] = true;
        } else {
            $data['is_personal_upload'] = (bool) ($data['is_personal_upload'] ?? false);
            $data['signature_status'] = $data['signature_status'] ?? 'not_required';
            $data['status'] = $data['status']
                ?? ($data['document_mode'] === 'template' ? 'borrador' : 'pendiente');
        }

        $templateContent = trim((string) ($data['template_content'] ?? ''));
        $renderedContent = null;
        $templateDocument = null;
        $targetUser = null;

        if (! empty($data['template_id']) && $data['document_mode'] !== 'template') {
            $templateDocument = HrDocument::query()->findOrFail((int) $data['template_id']);
            $targetUserId = (int) ($data['user_id'] ?? 0);
            $targetUser = $targetUserId > 0 ? User::query()->find($targetUserId) : null;

            $baseTemplateContent = $this->resolveTemplateSourceContent($templateDocument);
            if ($baseTemplateContent !== '') {
                $renderedContent = $this->renderTemplateContent(
                    $baseTemplateContent,
                    $targetUser,
                    $request,
                    $templateDocument
                );
            }
        }

        $hasFile = $request->hasFile('file');
        if (! $hasFile && $templateContent === '' && ($renderedContent === null || trim($renderedContent) === '')) {
            throw ValidationException::withMessages([
                'file' => 'Debes subir un archivo o escribir contenido de plantilla.',
            ]);
        }

        $fileMeta = $hasFile
            ? $this->storeUploadedFileMeta($request->file('file'))
            : $this->buildVirtualFileMeta($data['title'], $renderedContent !== null ? $renderedContent : $templateContent);

        if (! $hasFile && in_array($data['document_mode'], ['derived', 'user'], true) && ! empty($renderedContent)) {
            $fileMeta = $this->buildPdfFromHtml($data['title'], $this->buildContractHtml($renderedContent));
        }

        $document = HrDocument::create([
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'document_mode' => $data['document_mode'],
            'status' => $data['status'],
            'user_id' => $data['user_id'] ?? null,
            'template_id' => $data['template_id'] ?? null,
            'area_id' => $data['area_id'] ?? null,
            'signature_status' => $data['signature_status'],
            'is_personal_upload' => $data['is_personal_upload'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'template_content' => $data['document_mode'] === 'template' ? ($templateContent !== '' ? $templateContent : null) : null,
            'rendered_content' => $data['document_mode'] === 'template' ? null : $renderedContent,
            'file_path' => $fileMeta['file_path'],
            'original_name' => $fileMeta['original_name'],
            'mime_type' => $fileMeta['mime_type'],
            'file_size' => $fileMeta['file_size'],
            'uploaded_by' => $authUser->id,
        ]);

        $this->logAudit($document, 'created', $authUser->id, null, $document->only($this->auditedFields));

        return response()->json(['id' => $document->id], 201);
    }

    public function update(Request $request, HrDocument $document): JsonResponse
    {
        $authUser = $request->user();
        if (! $this->isAdmin($authUser)) {
            abort(403, 'No tienes permisos para editar este documento.');
        }

        if ($document->document_mode === 'template' && ! $this->isSuperAdmin($authUser)) {
            abort(403, 'Solo el superadministrador puede editar plantillas generales.');
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'document_type' => ['sometimes', 'string', 'max:40'],
            'document_mode' => ['sometimes', 'in:template,derived,user'],
            'status' => ['sometimes', 'in:activo,borrador,vencido,pendiente,firmado,rechazado'],
            'user_id' => ['nullable', 'exists:users,id'],
            'template_id' => ['nullable', 'exists:hr_documents,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'signature_status' => ['sometimes', 'in:not_required,pending,signed,rejected'],
            'is_personal_upload' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'template_content' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        if (array_key_exists('document_mode', $data) && $data['document_mode'] === 'template' && ! $this->isSuperAdmin($authUser)) {
            abort(403, 'Solo el superadministrador puede convertir documentos en plantillas.');
        }

        if (($data['document_mode'] ?? $document->document_mode) === 'derived') {
            $templateId = array_key_exists('template_id', $data) ? ($data['template_id'] ?? null) : $document->template_id;
            if (! $templateId) {
                throw ValidationException::withMessages([
                    'template_id' => 'Un documento derivado debe referenciar una plantilla base.',
                ]);
            }

            $template = HrDocument::query()->findOrFail((int) $templateId);
            if ($template->document_mode !== 'template') {
                throw ValidationException::withMessages([
                    'template_id' => 'La referencia seleccionada no es una plantilla válida.',
                ]);
            }
        }

        $resolvedMode = $data['document_mode'] ?? $document->document_mode;
        $resolvedTemplateId = array_key_exists('template_id', $data)
            ? ($data['template_id'] ?? null)
            : $document->template_id;

        if ($resolvedMode !== 'template' && $resolvedTemplateId) {
            $template = HrDocument::query()->findOrFail((int) $resolvedTemplateId);
            if ($template->document_mode !== 'template') {
                throw ValidationException::withMessages([
                    'template_id' => 'La referencia seleccionada no es una plantilla válida.',
                ]);
            }
        }

        $resolvedUserId = array_key_exists('user_id', $data) ? ($data['user_id'] ?? null) : $document->user_id;
        if ($resolvedMode === 'user' && ! $resolvedUserId) {
            throw ValidationException::withMessages([
                'user_id' => 'El documento por usuario debe asignarse a un colaborador.',
            ]);
        }

        $old = $document->only($this->auditedFields);
        $expiresChanged = array_key_exists('expires_at', $data) && (($data['expires_at'] ?? null) !== $document->expires_at?->toDateString());

        $resolvedTitle = $data['title'] ?? $document->title;
        $resolvedMode = $data['document_mode'] ?? $document->document_mode;
        $resolvedTemplateContent = array_key_exists('template_content', $data)
            ? trim((string) ($data['template_content'] ?? ''))
            : (string) ($document->template_content ?? '');
        $resolvedTemplateId = array_key_exists('template_id', $data)
            ? ($data['template_id'] ?? null)
            : $document->template_id;
        $resolvedUserId = array_key_exists('user_id', $data)
            ? ($data['user_id'] ?? null)
            : $document->user_id;

        if ($resolvedMode === 'template') {
            $data['template_content'] = $resolvedTemplateContent !== '' ? $resolvedTemplateContent : null;
            $data['rendered_content'] = null;
            $data['template_id'] = null;
            $data['user_id'] = null;
        } else {
            $data['template_content'] = null;
        }

        if (in_array($resolvedMode, ['derived', 'user'], true) && $resolvedTemplateId) {
            $templateDocument = HrDocument::query()->findOrFail((int) $resolvedTemplateId);
            $targetUser = $resolvedUserId ? User::query()->find((int) $resolvedUserId) : null;
            $baseTemplateContent = $this->resolveTemplateSourceContent($templateDocument);
            $data['rendered_content'] = $baseTemplateContent !== ''
                ? $this->renderTemplateContent($baseTemplateContent, $targetUser, $request, $templateDocument)
                : null;
        } elseif ($resolvedMode !== 'template' && array_key_exists('template_id', $data) && empty($resolvedTemplateId)) {
            $data['rendered_content'] = null;
        }

        if ($request->hasFile('file')) {
            if (! empty($document->file_path)) {
                Storage::delete($document->file_path);
            }
            $fileMeta = $this->storeUploadedFileMeta($request->file('file'));
            $data['file_path'] = $fileMeta['file_path'];
            $data['original_name'] = $fileMeta['original_name'];
            $data['mime_type'] = $fileMeta['mime_type'];
            $data['file_size'] = $fileMeta['file_size'];
        } elseif (
            $resolvedMode === 'template' &&
            $resolvedTemplateContent !== '' &&
            (! $document->file_path || ! Storage::exists($document->file_path))
        ) {
            $virtualMeta = $this->buildVirtualFileMeta($resolvedTitle, $resolvedTemplateContent);
            $data['file_path'] = $virtualMeta['file_path'];
            $data['original_name'] = $virtualMeta['original_name'];
            $data['mime_type'] = $virtualMeta['mime_type'];
            $data['file_size'] = $virtualMeta['file_size'];
        } elseif (
            in_array($resolvedMode, ['derived', 'user'], true) &&
            ! $document->file_path &&
            ! empty($data['rendered_content'])
        ) {
            $virtualMeta = $this->buildVirtualFileMeta($resolvedTitle, (string) $data['rendered_content']);
            $data['file_path'] = $virtualMeta['file_path'];
            $data['original_name'] = $virtualMeta['original_name'];
            $data['mime_type'] = $virtualMeta['mime_type'];
            $data['file_size'] = $virtualMeta['file_size'];
        }

        if (
            ! $request->hasFile('file') &&
            in_array($resolvedMode, ['derived', 'user'], true) &&
            ! empty($data['rendered_content']) &&
            (! $document->file_path || str_contains((string) $document->mime_type, 'text/html'))
        ) {
            if (! empty($document->file_path)) {
                Storage::delete($document->file_path);
            }

            $pdfMeta = $this->buildPdfFromHtml(
                $resolvedTitle,
                $this->buildContractHtml((string) $data['rendered_content'])
            );
            $data['file_path'] = $pdfMeta['file_path'];
            $data['original_name'] = $pdfMeta['original_name'];
            $data['mime_type'] = $pdfMeta['mime_type'];
            $data['file_size'] = $pdfMeta['file_size'];
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
            $authUser->id,
            $old,
            $document->only($this->auditedFields)
        );

        return response()->json(['ok' => true]);
    }

    public function requestSignature(Request $request, HrDocument $document): JsonResponse
    {
        $authUser = $request->user();
        if (! $this->isAdmin($authUser)) {
            abort(403, 'No tienes permisos para solicitar firma.');
        }

        if (! $document->user_id) {
            throw ValidationException::withMessages([
                'user_id' => 'Este documento no tiene colaborador asignado.',
            ]);
        }

        $old = $document->only($this->auditedFields);
        $document->update([
            'signature_status' => 'pending',
            'status' => 'pendiente',
            'signature_requested_at' => now(),
            'signed_at' => null,
            'signed_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
        $document->refresh();

        $this->logAudit($document, 'signature_requested', $authUser->id, $old, $document->only($this->auditedFields));

        return response()->json(['ok' => true]);
    }

    public function sign(Request $request, HrDocument $document): JsonResponse
    {
        $authUser = $request->user();
        if (! $this->canSignDocument($authUser, $document)) {
            abort(403, 'No tienes permisos para firmar este documento.');
        }

        $data = $request->validate([
            'signature_method' => ['required', 'in:draw,type'],
            'signature_data' => ['nullable', 'string', 'max:2000000'],
            'signature_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['signature_method'] === 'draw' && empty($data['signature_data'])) {
            throw ValidationException::withMessages([
                'signature_data' => 'Debes dibujar la firma para continuar.',
            ]);
        }

        if ($data['signature_method'] === 'type' && empty($data['signature_name'])) {
            throw ValidationException::withMessages([
                'signature_name' => 'Debes escribir tu nombre para firmar.',
            ]);
        }

        $old = $document->only($this->auditedFields);
        $signatureDisplayName = $data['signature_method'] === 'type'
            ? $data['signature_name']
            : ($data['signature_name'] ?? $authUser->name);

        $signaturePayload = [
            'method' => $data['signature_method'],
            'name' => $signatureDisplayName,
            'image' => $data['signature_method'] === 'draw' ? ($data['signature_data'] ?? null) : null,
            'signed_at' => now()->toDateTimeString(),
            'signed_by' => $authUser->name,
        ];

        $finalRenderedContent = $document->rendered_content ?: $document->template_content;
        $signedPdfMeta = null;
        if (! empty($finalRenderedContent)) {
            $signedPdfMeta = $this->buildPdfFromHtml(
                $document->title.'_firmado',
                $this->buildContractHtml((string) $finalRenderedContent, $signaturePayload)
            );

            if (! empty($document->file_path) && $document->file_path !== $signedPdfMeta['file_path']) {
                Storage::delete($document->file_path);
            }
        }

        $document->update([
            'signature_status' => 'signed',
            'status' => 'firmado',
            'signature_method' => $data['signature_method'],
            'signature_data' => $data['signature_method'] === 'draw' ? $data['signature_data'] : null,
            'signature_name' => $data['signature_method'] === 'type'
                ? $data['signature_name']
                : ($data['signature_name'] ?? $authUser->name),
            'signed_at' => now(),
            'signed_by' => $authUser->id,
            'rejected_at' => null,
            'rejection_reason' => null,
            'file_path' => $signedPdfMeta['file_path'] ?? $document->file_path,
            'original_name' => $signedPdfMeta['original_name'] ?? $document->original_name,
            'mime_type' => $signedPdfMeta['mime_type'] ?? $document->mime_type,
            'file_size' => $signedPdfMeta['file_size'] ?? $document->file_size,
        ]);
        $document->refresh();

        $this->logAudit($document, 'signed', $authUser->id, $old, $document->only($this->auditedFields));

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, HrDocument $document): JsonResponse
    {
        $authUser = $request->user();
        if (! $this->canSignDocument($authUser, $document)) {
            abort(403, 'No tienes permisos para rechazar este documento.');
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $document->only($this->auditedFields);
        $document->update([
            'signature_status' => 'rejected',
            'status' => 'rechazado',
            'rejected_at' => now(),
            'rejection_reason' => $data['reason'] ?? null,
            'signed_at' => null,
            'signed_by' => null,
            'signature_data' => null,
            'signature_name' => null,
            'signature_method' => null,
        ]);
        $document->refresh();

        $this->logAudit($document, 'rejected', $authUser->id, $old, $document->only($this->auditedFields));

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, HrDocument $document): JsonResponse
    {
        $authUser = $request->user();
        if (! $this->isAdmin($authUser)) {
            abort(403, 'No tienes permisos para eliminar este documento.');
        }

        if ($document->document_mode === 'template' && ! $this->isSuperAdmin($authUser)) {
            abort(403, 'Solo el superadministrador puede eliminar plantillas generales.');
        }

        $old = $document->only($this->auditedFields);
        $this->logAudit($document, 'deleted', $authUser->id, $old, null);

        if (! empty($document->file_path)) {
            Storage::delete($document->file_path);
        }
        $document->delete();

        return response()->json(['ok' => true]);
    }

    public function download(Request $request, HrDocument $document): StreamedResponse
    {
        $authUser = $request->user();
        if (! $this->canViewDocument($authUser, $document)) {
            abort(403, 'No tienes permisos para descargar este documento.');
        }

        $this->logAudit($document, 'downloaded', $authUser->id, null, [
            'original_name' => $document->original_name,
        ]);

        if (! empty($document->file_path) && Storage::exists($document->file_path)) {
            return Storage::download($document->file_path, $document->original_name);
        }

        $content = $document->rendered_content ?: $document->template_content;
        abort_if($content === null || trim($content) === '', 404, 'Archivo no encontrado');

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $document->original_name ?: 'documento_generado.html', [
            'Content-Type' => $document->mime_type ?: 'text/html; charset=UTF-8',
        ]);
    }

    public function audits(Request $request, HrDocument $document): JsonResponse
    {
        $authUser = $request->user();
        if (! $this->canViewDocument($authUser, $document)) {
            abort(403, 'No tienes permisos para consultar el historial.');
        }

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

    protected function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    protected function canViewDocument(User $user, HrDocument $document): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $document->user_id === $user->id || $document->uploaded_by === $user->id;
    }

    protected function canSignDocument(User $user, HrDocument $document): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $document->user_id === $user->id;
    }

    protected function storeUploadedFileMeta(UploadedFile $file): array
    {
        $disk = Storage::disk('local');
        $directory = 'hr-documents';

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $originalBaseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName = Str::slug($originalBaseName);
        if ($safeBaseName === '') {
            $safeBaseName = 'documento';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('Ymd_His');
        $suffix = Str::lower(Str::random(6));
        $fileName = "{$safeBaseName}_{$timestamp}_{$suffix}";
        if ($extension !== '') {
            $fileName .= ".{$extension}";
        }

        $storedPath = $disk->putFileAs($directory, $file, $fileName);

        return [
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize() ?? 0,
        ];
    }

    protected function buildVirtualFileMeta(string $title, string $content): array
    {
        $baseName = Str::slug($title);
        if ($baseName === '') {
            $baseName = 'documento';
        }

        return [
            'file_path' => '',
            'original_name' => $baseName.'.html',
            'mime_type' => 'text/html; charset=UTF-8',
            'file_size' => strlen($content),
        ];
    }

    protected function buildPdfFromHtml(string $title, string $html): array
    {
        $disk = Storage::disk('local');
        $directory = 'hr-documents';
        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $baseName = Str::slug($title);
        if ($baseName === '') {
            $baseName = 'documento';
        }

        $timestamp = now()->format('Ymd_His');
        $suffix = Str::lower(Str::random(6));
        $fileName = "{$baseName}_{$timestamp}_{$suffix}.pdf";
        $filePath = $directory.'/'.$fileName;

        $pdfBinary = Pdf::loadHTML($html)
            ->setPaper('letter')
            ->output();
        $disk->put($filePath, $pdfBinary);

        return [
            'file_path' => $filePath,
            'original_name' => $fileName,
            'mime_type' => 'application/pdf',
            'file_size' => strlen($pdfBinary),
        ];
    }

    protected function buildContractHtml(string $body, ?array $signature = null): string
    {
        $signatureHtml = '';
        if ($signature !== null) {
            $signatureImage = '';
            if (! empty($signature['image'])) {
                $signatureImage = '<img src="'.e((string) $signature['image']).'" alt="Firma" style="max-width:220px; max-height:100px;" />';
            }

            $signatureText = $signature['method'] === 'type'
                ? '<p style="font-family: cursive; font-size: 22px; margin: 0;">'.e((string) ($signature['name'] ?? '')).'</p>'
                : $signatureImage;

            $signatureHtml = '
                <div style="margin-top: 36px; padding-top: 14px; border-top: 1px solid #d1d5db;">
                    <h3 style="margin: 0 0 8px; font-size: 14px;">Firma digital</h3>
                    '.$signatureText.'
                    <p style="margin: 8px 0 0; font-size: 12px; color: #4b5563;">
                        Firmado por '.e((string) ($signature['signed_by'] ?? '')).' el '.e((string) ($signature['signed_at'] ?? '')).'
                    </p>
                </div>
            ';
        }

        return '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8" />
                <style>
                    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; line-height: 1.5; }
                    .doc-wrap { max-width: 100%; }
                    p { margin: 0 0 8px; }
                </style>
            </head>
            <body>
                <div class="doc-wrap">'.$body.'</div>
                '.$signatureHtml.'
            </body>
            </html>
        ';
    }

    protected function resolveTemplateSourceContent(HrDocument $templateDocument): string
    {
        if (! empty($templateDocument->template_content)) {
            return (string) $templateDocument->template_content;
        }

        if (! empty($templateDocument->rendered_content)) {
            return (string) $templateDocument->rendered_content;
        }

        return '';
    }

    protected function renderTemplateContent(
        string $templateContent,
        ?User $user,
        Request $request,
        ?HrDocument $templateDocument = null
    ): string {
        $company = CompanySettings::query()->first();

        $values = [
            'user.name' => $user?->name ?? '',
            'user.first_name' => $user?->first_name ?? '',
            'user.last_name' => $user?->last_name ?? '',
            'user.identification' => $user?->identification ?? '',
            'user.email' => $user?->email ?? '',
            'user.phone' => $user?->phone ?? '',
            'company.name' => $company?->company_name ?? config('app.name'),
            'company.identification' => $company?->company_identification ?? '',
            'company.email' => $company?->company_email ?? '',
            'company.phone' => $company?->company_phone ?? '',
            'today.date' => now()->toDateString(),
            'today.datetime' => now()->toDateTimeString(),
            'contract.start_date' => (string) ($request->input('start_date') ?? ''),
            'contract.end_date' => (string) ($request->input('end_date') ?? ''),
            'contract.expires_at' => (string) ($request->input('expires_at') ?? ''),
            'template.title' => $templateDocument?->title ?? '',
        ];

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function ($matches) use ($values) {
            $key = $matches[1];
            return array_key_exists($key, $values) ? (string) $values[$key] : $matches[0];
        }, $templateContent) ?? $templateContent;
    }

    protected function templateVariablesCatalog(): array
    {
        return [
            ['key' => 'user.name', 'label' => 'Nombre completo del colaborador'],
            ['key' => 'user.identification', 'label' => 'Identificación del colaborador'],
            ['key' => 'user.email', 'label' => 'Correo del colaborador'],
            ['key' => 'user.phone', 'label' => 'Teléfono del colaborador'],
            ['key' => 'company.name', 'label' => 'Nombre de la empresa'],
            ['key' => 'company.identification', 'label' => 'NIT o identificación de la empresa'],
            ['key' => 'contract.start_date', 'label' => 'Fecha de inicio del contrato'],
            ['key' => 'contract.end_date', 'label' => 'Fecha de fin del contrato'],
            ['key' => 'contract.expires_at', 'label' => 'Fecha de vencimiento'],
            ['key' => 'today.date', 'label' => 'Fecha actual'],
        ];
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
