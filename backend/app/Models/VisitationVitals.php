<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A set of vital signs taken during a visit. A visit can have several
 * (for example a repeat blood pressure), so rows are added, never edited.
 */
class VisitationVitals extends Model
{
    protected $table = 'visitation_vitals';

    public $timestamps = false;

    public const MEASUREMENTS = [
        'temperature_c',
        'bp_systolic',
        'bp_diastolic',
        'pulse_bpm',
        'resp_rate',
        'spo2_pct',
        'weight_kg',
        'height_cm',
        'muac_cm',
    ];

    protected $fillable = [...self::MEASUREMENTS, 'notes'];

    protected function casts(): array
    {
        return [
            'temperature_c' => 'float',
            'bp_systolic' => 'integer',
            'bp_diastolic' => 'integer',
            'pulse_bpm' => 'integer',
            'resp_rate' => 'integer',
            'spo2_pct' => 'integer',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'muac_cm' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function visitation(): BelongsTo
    {
        return $this->belongsTo(Visitation::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'recorded_by');
    }
}
