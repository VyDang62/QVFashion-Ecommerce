<?php
namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Logger
{
    public static function log($action, $model = null, $description, $properties = null)
    {
        $modelType = is_object($model) ? get_class($model) : 'System';
        $modelId = is_object($model) ? $model->id : 0;

        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description'=> $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
        ]);
    }
}