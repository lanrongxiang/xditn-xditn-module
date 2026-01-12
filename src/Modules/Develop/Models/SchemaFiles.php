<?php

namespace Modules\Develop\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use XditnModule\Base\CatchModel as Model;

class SchemaFiles extends Model
{
    protected $table = 'schema_files';

    protected $fillable = [
        'id', 'schema_id', 'controller_file', 'model_file', 'request_file', 'created_at', 'updated_at', 'deleted_at',
        'controller_path', 'model_path', 'request_path', 'dynamic_path', 'dynamic_file',
    ];

    protected bool $autoNull2EmptyString = false;

    protected function controllerPath(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $this->removeBasePath($value)
        );
    }

    protected function modelPath(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $this->removeBasePath($value)
        );
    }

    protected function requestPath(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $this->removeBasePath($value)
        );
    }

    protected function dynamicPath(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $this->removeBasePath($value)
        );
    }

    protected function removeBasePath($path)
    {
        return Str::of($path)->replace(base_path(), '')->replace('\\', '/');
    }
}
