<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CheckpointResource;
use App\Models\Checkpoint;
use App\Models\Visitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    /**
     * Open visits grouped by the checkpoint they are waiting at, in clinic order.
     * Within a checkpoint: most urgent first, then by arrival. Every checkpoint is
     * listed, including empty ones, so screens can show a stable layout.
     *
     * Will read from the v_visit_queue view once the real schema lands.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Visitation::class);

        $request->validate([
            'checkpoint' => ['sometimes', 'string', Rule::exists('checkpoint', 'code')],
        ]);

        $checkpoints = Checkpoint::query()
            ->when($request->input('checkpoint'), fn ($q, $code) => $q->where('code', $code))
            ->orderBy('sequence')
            ->with(['openVisits' => fn ($q) => $q->queueOrder()->with(['patient', 'currentLog'])])
            ->get();

        return response()->json([
            'data' => $checkpoints->map(fn (Checkpoint $checkpoint) => [
                'checkpoint' => CheckpointResource::make($checkpoint),
                'count' => $checkpoint->openVisits->count(),
                'visits' => $checkpoint->openVisits->map(fn (Visitation $visit) => [
                    'id' => $visit->id,
                    'visit_number' => $visit->visit_number,
                    'visit_type' => $visit->visit_type,
                    'priority' => $visit->priority,
                    'arrived_at' => $visit->arrived_at?->toIso8601String(),
                    // When the patient joined this checkpoint's line
                    'waiting_since' => $visit->currentLog?->entered_at?->toIso8601String(),
                    'patient' => [
                        'id' => $visit->patient->id,
                        'patient_number' => $visit->patient->patient_number,
                        'first_name' => $visit->patient->first_name,
                        'last_name' => $visit->patient->last_name,
                        'sex' => $visit->patient->sex,
                    ],
                ])->values(),
            ])->values(),
        ]);
    }
}
