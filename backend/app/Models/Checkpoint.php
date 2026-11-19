<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkpoint extends Model
{
    protected $table = 'checkpoint';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'sequence'];

    protected function casts(): array
    {
        return ['sequence' => 'integer'];
    }

    /**
     * Where a new visit starts: the checkpoint with the lowest sequence.
     */
    public static function entry(): self
    {
        return static::query()->orderBy('sequence')->firstOrFail();
    }

    public function openVisits(): HasMany
    {
        return $this->hasMany(Visitation::class, 'current_checkpoint_id')->where('status', Visitation::STATUS_OPEN);
    }
}
