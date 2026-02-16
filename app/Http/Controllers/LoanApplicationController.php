<?php

namespace App\Http\Controllers;

use App\Models\LoanActivityLog;
use App\Models\LoanApplication;
use App\Models\LoanApproval;
use App\Models\LoanApprovalMatrix;
use App\Models\LoanCollateral;
use App\Models\LoanCovenant;
use App\Models\LoanDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $query = LoanApplication::query()->with(['approvals']);

        if ($request->filled('stage')) {
            $query->where('current_stage', $request->string('stage'));
        }
        if ($request->filled('segment')) {
            $query->where('segment', $request->string('segment'));
        }
        if ($request->filled('q')) {
            $keyword = '%' . trim((string) $request->string('q')) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('application_number', 'like', $keyword)
                    ->orWhere('customer_name', 'like', $keyword)
                    ->orWhere('cif_number', 'like', $keyword);
            });
        }

        $applications = $query->latest()->paginate(12)->withQueryString();

        $metrics = [
            'total' => LoanApplication::count(),
            'draft' => LoanApplication::where('current_stage', 'draft')->count(),
            'review' => LoanApplication::where('current_stage', 'review')->count(),
            'approved' => LoanApplication::where('current_stage', 'approved')->count(),
            'rejected' => LoanApplication::where('current_stage', 'rejected')->count(),
            'plafond' => (float) LoanApplication::sum('plafond_amount'),
        ];

        $slaDashboard = $this->buildSlaDashboard();
        $matrices = LoanApprovalMatrix::query()
            ->orderBy('division')
            ->orderBy('segment')
            ->orderBy('sequence_no')
            ->get();

        $divisionOptions = LoanApprovalMatrix::query()
            ->select('division')
            ->distinct()
            ->orderBy('division')
            ->pluck('division');

        if ($divisionOptions->isEmpty()) {
            $divisionOptions = collect(['Corporate', 'Commercial', 'Commex']);
        }

        return view('los.applications.index', compact('applications', 'metrics', 'slaDashboard', 'matrices', 'divisionOptions'));
    }

    public function monitoring(): View
    {
        $slaDashboard = $this->buildSlaDashboard();
        return view('los.monitoring.index', compact('slaDashboard'));
    }

    public function show(LoanApplication $loanApplication): JsonResponse
    {
        $loanApplication->load([
            'collaterals',
            'documents',
            'approvals',
            'covenants',
            'activityLogs',
        ]);

        $loanApplication->documents->transform(function (LoanDocument $doc) {
            $doc->download_url = ($doc->file_disk && $doc->file_path)
                ? route('los.documents.download', $doc)
                : null;
            return $doc;
        });

        return response()->json([
            'code' => 200,
            'message' => 'ok success',
            'data' => $loanApplication,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cif_number' => 'nullable|string|max:50',
            'customer_name' => 'required|string|max:255',
            'division' => 'required|string|max:120',
            'segment' => 'required|in:corporate,commercial,commex',
            'loan_type' => 'required|string|max:100',
            'apk_type' => 'nullable|string|max:100',
            'purpose' => 'nullable|string',
            'plafond_amount' => 'required|numeric|min:0',
            'tenor_months' => 'nullable|integer|min:1|max:360',
            'bwmk_type' => 'nullable|in:non_deviasi,deviasi',
            'rm_name' => 'nullable|string|max:120',
            'branch_name' => 'nullable|string|max:120',
            'expected_disbursement_date' => 'nullable|date',
        ]);

        if (!$this->canCreateForDivision($data['division'], $data['segment'])) {
            return back()->with('error', 'Role Anda tidak memiliki akses maker untuk divisi/segment ini.');
        }

        $data['created_by'] = auth()->id();
        $data['current_stage'] = 'draft';
        $data['plafond_amount'] = $this->normalizeMoney($data['plafond_amount']);

        $application = LoanApplication::create($data);

        $this->createDefaultDocuments($application);
        $this->writeLog($application, 'CREATE_APPLICATION', 'Aplikasi kredit dibuat');

        return redirect()
            ->route('los.applications.index')
            ->with('success', 'Loan application berhasil dibuat');
    }

    public function update(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        if (!in_array($loanApplication->current_stage, ['draft', 'rejected'], true)) {
            return back()->with('error', 'Hanya aplikasi draft/rejected yang bisa diubah');
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'division' => 'required|string|max:120',
            'segment' => 'required|in:corporate,commercial,commex',
            'loan_type' => 'required|string|max:100',
            'apk_type' => 'nullable|string|max:100',
            'purpose' => 'nullable|string',
            'plafond_amount' => 'required|numeric|min:0',
            'tenor_months' => 'nullable|integer|min:1|max:360',
            'bwmk_type' => 'nullable|in:non_deviasi,deviasi',
            'rm_name' => 'nullable|string|max:120',
            'branch_name' => 'nullable|string|max:120',
            'ideb_result_status' => 'nullable|in:pending,clear,watchlist',
            'expected_disbursement_date' => 'nullable|date',
        ]);

        $data['plafond_amount'] = $this->normalizeMoney($data['plafond_amount']);
        $loanApplication->update($data);

        $this->writeLog($loanApplication, 'UPDATE_APPLICATION', 'Aplikasi kredit diubah');

        return back()->with('success', 'Loan application berhasil diupdate');
    }

    public function submit(LoanApplication $loanApplication): RedirectResponse
    {
        if (!$this->canCreateForDivision($loanApplication->division, $loanApplication->segment)) {
            return back()->with('error', 'Role Anda tidak memiliki akses submit maker untuk aplikasi ini.');
        }

        if ($loanApplication->current_stage === 'approved') {
            return back()->with('error', 'Aplikasi sudah approved');
        }

        if ($loanApplication->collaterals()->count() === 0) {
            return back()->with('error', 'Minimal 1 collateral wajib diisi sebelum submit');
        }

        $missingRequiredDocuments = $loanApplication->documents()
            ->where('is_required', true)
            ->where('is_uploaded', false)
            ->count();

        if ($missingRequiredDocuments > 0) {
            return back()->with('error', 'Semua dokumen wajib harus uploaded sebelum submit');
        }

        $loanApplication->update([
            'current_stage' => 'review',
            'submitted_at' => Carbon::now(),
            'rejected_at' => null,
            'rejection_notes' => null,
        ]);

        $loanApplication->approvals()->delete();
        $this->seedApprovalFlow($loanApplication);
        $this->startNextPendingApproval($loanApplication);

        $this->writeLog($loanApplication, 'SUBMIT_REVIEW', 'Aplikasi dikirim ke tahap review');

        return back()->with('success', 'Aplikasi berhasil disubmit ke reviewer');
    }

    public function approve(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
            'approver_name' => 'nullable|string|max:120',
        ]);

        $pendingApproval = $loanApplication->approvals()
            ->where('decision', 'pending')
            ->orderBy('sequence_no')
            ->first();

        if (!$pendingApproval) {
            return back()->with('error', 'Tidak ada approval step yang pending');
        }

        if (!$this->canTakeApprovalStep($pendingApproval)) {
            return back()->with('error', 'Role Anda tidak berhak memproses step approval ini.');
        }

        $pendingApproval->update([
            'decision' => 'approved',
            'decision_at' => Carbon::now(),
            'approver_name' => $request->input('approver_name') ?: (auth()->user()->name ?? null),
            'notes' => $request->input('notes'),
            'assigned_user_id' => auth()->id(),
            'completed_at' => Carbon::now(),
        ]);

        $nextPending = $this->startNextPendingApproval($loanApplication);

        if ($nextPending) {
            $loanApplication->update(['current_stage' => $this->stageFromActorType($nextPending->actor_type)]);
        } else {
            $loanApplication->update([
                'current_stage' => 'approved',
                'approved_at' => Carbon::now(),
                'approved_by' => auth()->id(),
            ]);
        }

        $this->writeLog($loanApplication, 'APPROVE_STEP', 'Approval sequence ' . $pendingApproval->sequence_no . ' disetujui');

        return back()->with('success', 'Approval berhasil diproses');
    }

    public function reject(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $data = $request->validate([
            'notes' => 'required|string|max:1000',
            'approver_name' => 'nullable|string|max:120',
        ]);

        $pendingApproval = $loanApplication->approvals()
            ->where('decision', 'pending')
            ->orderBy('sequence_no')
            ->first();

        if ($pendingApproval) {
            if (!$this->canTakeApprovalStep($pendingApproval)) {
                return back()->with('error', 'Role Anda tidak berhak menolak step approval ini.');
            }

            $pendingApproval->update([
                'decision' => 'rejected',
                'decision_at' => Carbon::now(),
                'approver_name' => $data['approver_name'] ?? (auth()->user()->name ?? null),
                'notes' => $data['notes'],
                'assigned_user_id' => auth()->id(),
                'completed_at' => Carbon::now(),
            ]);
        }

        $loanApplication->update([
            'current_stage' => 'rejected',
            'rejected_at' => Carbon::now(),
            'rejection_notes' => $data['notes'],
        ]);

        $this->writeLog($loanApplication, 'REJECT_APPLICATION', $data['notes']);

        return back()->with('success', 'Aplikasi ditolak dan dikembalikan ke maker');
    }

    public function addCollateral(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $data = $request->validate([
            'collateral_type' => 'required|in:property,non_property',
            'collateral_subtype' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'appraisal_value' => 'required|numeric|min:0',
            'liquidation_value' => 'required|numeric|min:0',
            'ownership_name' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'is_primary' => 'nullable|boolean',
        ]);

        $data['appraisal_value'] = $this->normalizeMoney($data['appraisal_value']);
        $data['liquidation_value'] = $this->normalizeMoney($data['liquidation_value']);
        $data['is_primary'] = (bool) ($data['is_primary'] ?? false);

        $loanApplication->collaterals()->create($data);
        $loanApplication->recalculateCollateralTotals();
        $this->writeLog($loanApplication, 'ADD_COLLATERAL', 'Collateral baru ditambahkan');

        return back()->with('success', 'Collateral berhasil ditambahkan');
    }

    public function upsertDocument(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $data = $request->validate([
            'document_name' => 'required|string|max:150',
            'document_category' => 'nullable|in:predefined,additional',
            'is_required' => 'nullable|boolean',
            'is_uploaded' => 'nullable|boolean',
            'verification_status' => 'nullable|in:pending,valid,invalid',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
        ]);

        $document = $loanApplication->documents()->firstOrNew(
            ['document_name' => $data['document_name']],
        );

        $document->document_category = $data['document_category'] ?? 'predefined';
        $document->is_required = (bool) ($data['is_required'] ?? true);
        $document->verification_status = $data['verification_status'] ?? 'pending';
        $document->notes = $data['notes'] ?? null;

        if ($request->hasFile('file')) {
            if ($document->file_disk && $document->file_path) {
                Storage::disk($document->file_disk)->delete($document->file_path);
            }

            $file = $request->file('file');
            $disk = 'public';
            $folder = 'los-documents/' . Str::slug($loanApplication->application_number);
            $storedPath = $file->store($folder, $disk);

            $document->file_disk = $disk;
            $document->file_path = $storedPath;
            $document->original_filename = $file->getClientOriginalName();
            $document->file_mime = $file->getClientMimeType();
            $document->file_size = $file->getSize();
            $document->is_uploaded = true;
            $document->uploaded_at = Carbon::now();
        } else {
            $document->is_uploaded = (bool) ($data['is_uploaded'] ?? false);
            $document->uploaded_at = $document->is_uploaded ? Carbon::now() : null;
        }

        $document->save();

        $this->writeLog($loanApplication, 'UPSERT_DOCUMENT', 'Dokumen ' . $document->document_name . ' diperbarui');

        return back()->with('success', 'Dokumen berhasil diupdate');
    }

    public function downloadDocument(LoanDocument $loanDocument): StreamedResponse
    {
        if (!$loanDocument->file_disk || !$loanDocument->file_path) {
            abort(404, 'Dokumen fisik belum tersedia.');
        }

        return Storage::disk($loanDocument->file_disk)->download(
            $loanDocument->file_path,
            $loanDocument->original_filename ?: basename($loanDocument->file_path)
        );
    }

    public function addCovenant(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $data = $request->validate([
            'covenant_phase' => 'required|in:pre_disbursement,at_disbursement,post_disbursement',
            'covenant_text' => 'required|string|max:2000',
            'is_mandatory' => 'nullable|boolean',
            'status' => 'nullable|in:open,fulfilled,waived',
            'due_date' => 'nullable|date',
        ]);

        if (($data['status'] ?? 'open') === 'fulfilled') {
            $data['fulfilled_at'] = Carbon::now();
        }
        $data['is_mandatory'] = (bool) ($data['is_mandatory'] ?? true);

        $loanApplication->covenants()->create($data);
        $this->writeLog($loanApplication, 'ADD_COVENANT', 'Covenant ditambahkan');

        return back()->with('success', 'Covenant berhasil ditambahkan');
    }

    public function storeMatrix(Request $request): RedirectResponse
    {
        if (!auth()->user() || !auth()->user()->hasAnyRole(['Administrator'])) {
            return back()->with('error', 'Hanya Administrator yang dapat mengatur approval matrix.');
        }

        $data = $request->validate([
            'division' => 'required|string|max:120',
            'segment' => 'required|in:corporate,commercial,commex',
            'bwmk_type' => 'nullable|in:non_deviasi,deviasi',
            'actor_type' => 'required|in:maker,checker,approver',
            'sequence_no' => 'required|integer|min:1|max:20',
            'role_name' => 'required|string|max:120',
            'sla_hours' => 'required|integer|min:1|max:720',
            'is_active' => 'nullable|boolean',
        ]);

        LoanApprovalMatrix::updateOrCreate(
            [
                'division' => $data['division'],
                'segment' => $data['segment'],
                'bwmk_type' => $data['bwmk_type'] ?? null,
                'actor_type' => $data['actor_type'],
                'sequence_no' => $data['sequence_no'],
                'role_name' => $data['role_name'],
            ],
            [
                'sla_hours' => $data['sla_hours'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Approval matrix berhasil disimpan.');
    }

    public function destroyMatrix(LoanApprovalMatrix $matrix): RedirectResponse
    {
        if (!auth()->user() || !auth()->user()->hasAnyRole(['Administrator'])) {
            return back()->with('error', 'Hanya Administrator yang dapat menghapus approval matrix.');
        }

        $matrix->delete();

        return back()->with('success', 'Approval matrix berhasil dihapus.');
    }

    private function seedApprovalFlow(LoanApplication $loanApplication): void
    {
        $steps = LoanApprovalMatrix::query()
            ->where('is_active', true)
            ->where('division', $loanApplication->division)
            ->where('segment', $loanApplication->segment)
            ->where(function ($q) use ($loanApplication) {
                $q->whereNull('bwmk_type');
                if ($loanApplication->bwmk_type) {
                    $q->orWhere('bwmk_type', $loanApplication->bwmk_type);
                }
            })
            ->orderBy('sequence_no')
            ->get();

        if ($steps->isEmpty()) {
            $fallback = [
                ['actor_type' => 'checker', 'role_name' => 'BM', 'sla_hours' => 24],
                ['actor_type' => 'approver', 'role_name' => 'BSM', 'sla_hours' => 24],
            ];
            foreach ($fallback as $idx => $step) {
                LoanApproval::create([
                    'loan_application_id' => $loanApplication->id,
                    'sequence_no' => $idx + 1,
                    'actor_type' => $step['actor_type'],
                    'approver_role' => $step['role_name'],
                    'decision' => 'pending',
                    'sla_hours' => $step['sla_hours'],
                ]);
            }
            return;
        }

        foreach ($steps as $step) {
            LoanApproval::create([
                'loan_application_id' => $loanApplication->id,
                'sequence_no' => $step->sequence_no,
                'actor_type' => $step->actor_type,
                'approver_role' => $step->role_name,
                'decision' => 'pending',
                'sla_hours' => $step->sla_hours,
            ]);
        }
    }

    private function createDefaultDocuments(LoanApplication $loanApplication): void
    {
        $docs = [
            'Akta Perusahaan',
            'Laporan Keuangan',
            'Rekening Koran',
            'Hasil IDEB',
            'Dokumen Agunan',
        ];

        foreach ($docs as $doc) {
            LoanDocument::create([
                'loan_application_id' => $loanApplication->id,
                'document_name' => $doc,
                'document_category' => 'predefined',
                'is_required' => true,
                'is_uploaded' => false,
                'verification_status' => 'pending',
            ]);
        }
    }

    private function writeLog(LoanApplication $loanApplication, string $action, ?string $detail = null): void
    {
        LoanActivityLog::create([
            'loan_application_id' => $loanApplication->id,
            'actor_name' => auth()->user()->name ?? 'system',
            'action' => $action,
            'detail' => $detail,
            'created_at' => Carbon::now(),
        ]);
    }

    private function normalizeMoney(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace([',', ' '], '', (string) $value);
        return (float) $clean;
    }

    private function stageFromActorType(string $actorType): string
    {
        return match ($actorType) {
            'maker' => 'draft',
            'checker' => 'review',
            'approver' => 'acceptance',
            default => 'review',
        };
    }

    private function canCreateForDivision(string $division, string $segment): bool
    {
        if (!auth()->check()) {
            return false;
        }
        if (auth()->user()->hasRole('Administrator')) {
            return true;
        }

        $makerRoles = LoanApprovalMatrix::query()
            ->where('is_active', true)
            ->where('division', $division)
            ->where('segment', $segment)
            ->where('actor_type', 'maker')
            ->pluck('role_name')
            ->unique()
            ->values()
            ->all();

        if (empty($makerRoles)) {
            $makerRoles = ['RM', 'BM'];
        }

        return auth()->user()->hasAnyRole($makerRoles);
    }

    private function canTakeApprovalStep(LoanApproval $pendingApproval): bool
    {
        if (!auth()->check()) {
            return false;
        }
        if (auth()->user()->hasRole('Administrator')) {
            return true;
        }
        return auth()->user()->hasRole($pendingApproval->approver_role);
    }

    private function startNextPendingApproval(LoanApplication $loanApplication): ?LoanApproval
    {
        $nextPending = $loanApplication->approvals()
            ->where('decision', 'pending')
            ->orderBy('sequence_no')
            ->first();

        if (!$nextPending) {
            return null;
        }

        if (!$nextPending->started_at) {
            $started = Carbon::now();
            $due = $nextPending->sla_hours ? $started->copy()->addHours($nextPending->sla_hours) : null;
            $nextPending->update([
                'started_at' => $started,
                'due_at' => $due,
            ]);
        }

        return $nextPending->fresh();
    }

    private function buildSlaDashboard(): array
    {
        $now = Carbon::now();

        $pendingApprovals = LoanApproval::query()
            ->with('application')
            ->where('decision', 'pending')
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at asc')
            ->limit(50)
            ->get()
            ->map(function (LoanApproval $approval) use ($now) {
                $agingHours = $approval->started_at ? $approval->started_at->diffInHours($now) : 0;
                $isBreached = $approval->due_at ? $now->greaterThan($approval->due_at) : false;

                return [
                    'id' => $approval->id,
                    'application_number' => $approval->application?->application_number,
                    'customer_name' => $approval->application?->customer_name,
                    'stage' => $approval->application?->current_stage,
                    'sequence_no' => $approval->sequence_no,
                    'actor_type' => $approval->actor_type,
                    'approver_role' => $approval->approver_role,
                    'started_at' => $approval->started_at,
                    'due_at' => $approval->due_at,
                    'sla_hours' => $approval->sla_hours,
                    'aging_hours' => $agingHours,
                    'is_breached' => $isBreached,
                ];
            });

        $stageAging = LoanApplication::query()
            ->select('current_stage', 'created_at', 'submitted_at', 'approved_at')
            ->get()
            ->groupBy('current_stage')
            ->map(function ($rows, $stage) use ($now) {
                $hours = $rows->map(function ($row) use ($now) {
                    $start = $row->submitted_at ?? $row->created_at;
                    $end = $row->approved_at ?? $now;
                    return $start ? $start->diffInHours($end) : 0;
                });

                return [
                    'stage' => $stage,
                    'count' => $rows->count(),
                    'avg_hours' => round($hours->avg() ?? 0, 1),
                    'max_hours' => (int) ($hours->max() ?? 0),
                ];
            })
            ->values();

        return [
            'pending_count' => $pendingApprovals->count(),
            'breached_count' => $pendingApprovals->where('is_breached', true)->count(),
            'avg_pending_aging_hours' => round($pendingApprovals->avg('aging_hours') ?? 0, 1),
            'pending_approvals' => $pendingApprovals,
            'stage_aging' => $stageAging,
            'approval_by_role' => LoanApproval::query()
                ->select('approver_role', DB::raw('count(*) as total'))
                ->groupBy('approver_role')
                ->orderByDesc('total')
                ->get(),
        ];
    }
}
