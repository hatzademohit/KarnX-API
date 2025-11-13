<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BaseModel extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()            // Log all attributes
            ->logOnlyDirty()      // Only log changed fields
            ->useLogName(class_basename($this))
            ->dontSubmitEmptyLogs();
    }
}
