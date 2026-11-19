<?php

namespace Database\Seeders;

use App\Models\Checkpoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The stations a visit moves through. Reference data, safe to run in every
 * environment; running it again brings names and order back in line.
 *
 * A new visit starts at the checkpoint with the lowest sequence.
 */
class CheckpointSeeder extends Seeder
{
    /** code => [name, sequence] */
    public const CHECKPOINTS = [
        'reception' => ['Reception', 1],
        'triage' => ['Triage / Vitals', 2],
        'consultation' => ['Consultation', 3],
        'lab' => ['Laboratory', 4],
        'pharmacy' => ['Pharmacy', 5],
        'billing' => ['Billing / Cashier', 6],
    ];

    public function run(): void
    {
        // Move existing rows out of the way first so swapping two sequences
        // does not trip the unique index.
        Checkpoint::query()->update(['sequence' => DB::raw('sequence + 1000')]);

        foreach (self::CHECKPOINTS as $code => [$name, $sequence]) {
            Checkpoint::updateOrCreate(['code' => $code], ['name' => $name, 'sequence' => $sequence]);
        }
    }
}
