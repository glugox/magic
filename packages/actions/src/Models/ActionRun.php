<?php

namespace Glugox\Actions\Models;

use Illuminate\Database\Eloquent\Model;

class ActionRun extends Model
{
    protected $table = 'action_runs';

    protected $fillable = [
        'action', 'status', 'progress', 'message', 'params', 'targets', 'user_id'
    ];

    protected $casts = [
        'params' => 'array',
        'targets' => 'array',
    ];
}
