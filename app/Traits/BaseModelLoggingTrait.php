<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait BaseModelLoggingTrait
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
