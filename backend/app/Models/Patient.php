<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    protected $table = 'patient';

    const UPDATED_AT = null;

    // patient_number and created_by are set by the server, never by API input
    protected $fillable = [
        'first_name',
        'last_name',
        'sex',
        'date_of_birth',
        'dob_estimated',
        'national_id',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'dob_estimated' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Placeholder until the real schema decides how patient numbers are issued
        // (a sequence or trigger in the DB would replace this).
        static::creating(function (Patient $patient) {
            $patient->patient_number ??= 'TMC-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'created_by');
    }
}
