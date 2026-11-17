<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\Checkpoint;
use App\Models\Visitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The rules for moving a visit through the clinic: check in, move between
 * checkpoints, close. Every change keeps visitation.current_checkpoint_id and
 * the open checkpoint log row in step, inside one transaction.
 */
class VisitFlow
{
    /**
     * Open a visit and put the patient at the first checkpoint.
     *
     * @param  array{patient_id: int, visit_type: string, priority: string, patient_consent: bool}  $data
     */
    public function checkIn(array $data, AppUser $by): Visitation
    {
        return DB::transaction(function () use ($data, $by) {
            // Lock the patient row so two receptionists cannot open two visits at once
            DB::table('patient')->where('id', $data['patient_id'])->lockForUpdate()->first();

            if (Visitation::open()->where('patient_id', $data['patient_id'])->exists()) {
                throw ValidationException::withMessages([
                    'patient_id' => 'This patient already has an open visit. Close it before checking in again.',
                ]);
            }

            $entry = Checkpoint::entry();
            $now = now();

            $visit = new Visitation($data);
            $visit->status = Visitation::STATUS_OPEN;
            $visit->current_checkpoint_id = $entry->id;
            $visit->arrived_at = $now;
            $visit->registered_by = $by->id;
            $visit->save();

            $visit->checkpointLog()->create(['checkpoint_id' => $entry->id, 'entered_at' => $now]);

            return $visit;
        });
    }

    /**
     * Clear the current checkpoint and enter another one. Any checkpoint can be
     * the target, so going back (e.g. to the lab after a review) and jumping
     * ahead both work. $skipped marks the current one as left without service.
     */
    public function moveTo(Visitation $visit, Checkpoint $to, AppUser $by, bool $skipped = false, ?string $note = null): Visitation
    {
        return DB::transaction(function () use ($visit, $to, $by, $skipped, $note) {
            $visit = Visitation::lockForUpdate()->findOrFail($visit->id);
            $this->ensureOpen($visit);

            if ($visit->current_checkpoint_id === $to->id) {
                throw ValidationException::withMessages([
                    'checkpoint' => "The visit is already at {$to->name}.",
                ]);
            }

            $now = now();
            $this->clearCurrent($visit, $by, $skipped, $note);

            $visit->checkpointLog()->create(['checkpoint_id' => $to->id, 'entered_at' => $now]);
            $visit->current_checkpoint_id = $to->id;
            $visit->save();

            return $visit;
        });
    }

    /**
     * Escalate or downgrade a waiting patient's priority, e.g. when their
     * condition changes while still in the queue. Does not touch the checkpoint.
     */
    public function reprioritize(Visitation $visit, string $priority): Visitation
    {
        return DB::transaction(function () use ($visit, $priority) {
            $visit = Visitation::lockForUpdate()->findOrFail($visit->id);
            $this->ensureOpen($visit);

            $visit->priority = $priority;
            $visit->save();

            return $visit;
        });
    }

    /**
     * End the visit: clear its checkpoint and take it off the queue.
     */
    public function close(Visitation $visit, string $status, AppUser $by, ?string $reason = null): Visitation
    {
        return DB::transaction(function () use ($visit, $status, $by, $reason) {
            $visit = Visitation::lockForUpdate()->findOrFail($visit->id);
            $this->ensureOpen($visit);

            $this->clearCurrent($visit, $by, skipped: false, note: null);

            $visit->status = $status;
            $visit->current_checkpoint_id = null;
            $visit->closed_at = now();
            $visit->closed_by = $by->id;
            $visit->closure_reason = $reason;
            $visit->save();

            return $visit;
        });
    }

    private function ensureOpen(Visitation $visit): void
    {
        if (! $visit->isOpen()) {
            throw ValidationException::withMessages([
                'visit' => "This visit is {$visit->status} and can no longer change.",
            ]);
        }
    }

    private function clearCurrent(Visitation $visit, AppUser $by, bool $skipped, ?string $note): void
    {
        $visit->checkpointLog()
            ->whereNull('cleared_at')
            ->update([
                'cleared_at' => now(),
                'cleared_by' => $by->id,
                'skipped' => $skipped,
                'note' => $note,
            ]);
    }
}
