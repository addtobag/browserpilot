<?php

namespace BrowserPilot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PilotProfile extends Model
{
    protected $table = 'pilot_profiles';

    protected $guarded = [];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
