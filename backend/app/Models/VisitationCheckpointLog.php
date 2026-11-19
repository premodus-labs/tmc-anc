<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a visit into a checkpoint. cleared_at stays null while the
 * patient is still there; skipped marks a checkpoint left without being served.
 */
class VisitationCheckpointLog extends Model
{
    protected $table = 'visitation_checkpoint_log';

    public $timestamps = false;

    protected $fillable = [
        'checkpoint_id',
        'entered_at',
        'cleared_at',
        'cleared_by',
        'skipped',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'entered_at' => 'datetime',
            'cleared_at' => 'datetime',
            'skipped' => 'boolean',
        ];
    }

    public function visitation(): BelongsTo
    {
        return $this->belongsTo(Visitation::class);
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'cleared_by');
    }
}
