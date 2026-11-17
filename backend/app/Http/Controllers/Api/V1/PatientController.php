<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StorePatientRequest;
use App\Http\Requests\V1\UpdatePatientRequest;
use App\Http\Resources\V1\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    /**
     * List patients, newest first. Search matches name, patient number or national ID.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Patient::class);

        $request->validate([
            'search' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $patients = Patient::query()
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where(
                fn ($q) => $q->whereLike('first_name', "%{$search}%")
                    ->orWhereLike('last_name', "%{$search}%")
                    ->orWhereLike('patient_number', "%{$search}%")
                    ->orWhereLike('national_id', "%{$search}%")
            ))
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return PatientResource::collection($patients);
    }

    /**
     * Register a patient.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = new Patient($request->validated());
        $patient->created_by = $request->user()->id;
        $patient->save();

        return PatientResource::make($patient->refresh())
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient): PatientResource
    {
        Gate::authorize('view', $patient);

        return PatientResource::make($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): PatientResource
    {
        $patient->update($request->validated());

        return PatientResource::make($patient);
    }
}
