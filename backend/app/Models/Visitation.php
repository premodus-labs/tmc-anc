<?php

namespace App\Models;

use Database\Factories\VisitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * One patient visit, from arrival to closure. It moves through checkpoints;
 * each entry into a checkpoint is a VisitationCheckpointLog row.
 */
class Visitation extends Model
{
    /** @use HasFactory<VisitationFactory> */
    use HasFactory;

    protected $table = 'visitation';

    public $timestamps = false;

    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_COMPLETED, self::STATUS_CANCELLED];

    public const VISIT_TYPES = ['general', 'anc', 'follow_up', 'emergency'];

    // Listed most urgent first; the queue sorts by this order
    public const PRIORITIES = ['emergency', 'urgent', 'routine'];

    // Everything else (number, status, checkpoint, who and when) is set by the server
    protected $fillable = [
        'patient_id',
        'visit_type',
        'priority',
        'patient_consent',
    ];

    protected function casts(): array
    {
        return [
            'patient_consent' => 'boolean',
            'arrived_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Placeholder, same as patient_number, until the real schema issues visit numbers
        static::creating(function (Visitation $visit) {
            $visit->visit_number ??= 'V-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        });
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Most urgent first, then first come first served.
     */
    public function scopeQueueOrder(Builder $query): void
    {
        $cases = collect(self::PRIORITIES)
            ->map(fn ($priority, $rank) => "when '{$priority}' then {$rank}")
            ->implode(' ');

        $query->orderByRaw("case priority {$cases} else 99 end")->orderBy('arrived_at')->orderBy('id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function currentCheckpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class, 'current_checkpoint_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'registered_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'closed_by');
    }

    public function checkpointLog(): HasMany
    {
        return $this->hasMany(VisitationCheckpointLog::class)->orderBy('entered_at')->orderBy('id');
    }

    /**
     * The log row for the checkpoint the visit is at now (not yet cleared).
     */
    public function currentLog(): HasOne
    {
        return $this->hasOne(VisitationCheckpointLog::class)->whereNull('cleared_at')->latestOfMany();
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(VisitationVitals::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }
}
