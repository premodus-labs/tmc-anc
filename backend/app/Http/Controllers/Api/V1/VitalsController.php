<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreVitalsRequest;
use App\Http\Resources\V1\VitalsResource;
use App\Models\Visitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VitalsController extends Controller
{
    /**
     * Vital signs taken during a visit, latest first.
     */
    public function index(Visitation $visitation): AnonymousResourceCollection
    {
        Gate::authorize('view', $visitation);

        return VitalsResource::collection($visitation->vitals);
    }

    /**
     * Record a set of vital signs. Only open visits take new readings.
     */
    public function store(StoreVitalsRequest $request, Visitation $visitation): JsonResponse
    {
        if (! $visitation->isOpen()) {
            throw ValidationException::withMessages([
                'visit' => "This visit is {$visitation->status} and can no longer take vitals.",
            ]);
        }

        $vitals = $visitation->vitals()->make($request->validated());
        $vitals->recorded_by = $request->user()->id;
        $vitals->recorded_at = now();
        $vitals->save();

        return VitalsResource::make($vitals)->response()->setStatusCode(201);
    }
}
