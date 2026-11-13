<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CloseVisitRequest;
use App\Http\Requests\V1\MoveVisitRequest;
use App\Http\Requests\V1\StoreVisitRequest;
use App\Http\Resources\V1\VisitResource;
use App\Models\Visitation;
use App\Services\VisitFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VisitController extends Controller
{
    public function __construct(private readonly VisitFlow $flow) {}

    /**
     * List visits, newest first. Filter by status, patient, current checkpoint or arrival date.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Visitation::class);

        $request->validate([
            'status' => ['sometimes', Rule::in(Visitation::STATUSES)],
            'patient_id' => ['sometimes', 'integer'],
            'checkpoint' => ['sometimes', 'string'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $visits = Visitation::query()
            ->with(['patient', 'currentCheckpoint'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('patient_id'), fn ($q, $id) => $q->where('patient_id', $id))
            ->when($request->input('checkpoint'), fn ($q, $code) => $q->whereRelation('currentCheckpoint', 'code', $code))
            ->when($request->input('date'), fn ($q, $date) => $q->whereDate('arrived_at', $date))
            ->latest('arrived_at')
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return VisitResource::collection($visits);
    }

    /**
     * Check a patient in. The visit opens at the first checkpoint (reception).
     */
    public function store(StoreVisitRequest $request): JsonResponse
    {
        $visit = $this->flow->checkIn($request->visitData(), $request->user());

        return $this->detail($visit)->response()->setStatusCode(201);
    }

    /**
     * A visit with its patient, checkpoint history and vitals.
     */
    public function show(Visitation $visitation): VisitResource
    {
        Gate::authorize('view', $visitation);

        return $this->detail($visitation);
    }

    /**
     * Send the patient to another checkpoint, clearing the current one.
     */
    public function move(MoveVisitRequest $request, Visitation $visitation): VisitResource
    {
        $visit = $this->flow->moveTo(
            $visitation,
            $request->target(),
            $request->user(),
            $request->boolean('skipped'),
            $request->validated('note'),
        );

        return $this->detail($visit);
    }

    /**
     * Close the visit as completed or cancelled. It leaves the queue.
     */
    public function close(CloseVisitRequest $request, Visitation $visitation): VisitResource
    {
        $visit = $this->flow->close(
            $visitation,
            $request->validated('status'),
            $request->user(),
            $request->validated('closure_reason'),
        );

        return $this->detail($visit);
    }

    private function detail(Visitation $visit): VisitResource
    {
        return VisitResource::make($visit->load(['patient', 'currentCheckpoint', 'checkpointLog.checkpoint', 'vitals']));
    }
}
