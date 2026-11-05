<?php

namespace BrowserPilot\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $driver
 * @property string $remote_id
 * @property string|null $connect_url
 * @property string|null $live_view_url
 * @property string $status
 */
class PilotSession extends Model
{
    use HasUlids;

    protected $table = 'pilot_sessions';

    protected $guarded = [];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'connect_url' => 'encrypted',
            'metadata' => 'array',
            'handoff_requested_at' => 'datetime',
            'handed_back_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
