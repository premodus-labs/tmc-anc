<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CheckpointResource;
use App\Models\Checkpoint;
use App\Models\Visitation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CheckpointController extends Controller
{
    /**
     * The checkpoints a visit can move through, in clinic order.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Visitation::class);

        return CheckpointResource::collection(Checkpoint::orderBy('sequence')->get());
    }
}
