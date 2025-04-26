<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    public const FAILED_CHECKSUM = 'failed:checksum';

    public const FAILED_CHUNKS_MERGE = 'failed:chunks-merge';
}
