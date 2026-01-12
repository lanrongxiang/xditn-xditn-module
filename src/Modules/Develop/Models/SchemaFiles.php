<?php

declare(strict_types=1);

namespace Modules\Develop\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use XditnModule\Base\XditnModuleModel as Model;

class SchemaFiles extends Model
{
    protected $table = 'schema_files';

    protected $fillable = [
        'id', 'schema_id', 'controller_file', 'model_file', 'request_file',
        'controller_path', 'model_path', 'request_path',
    ];

    protected bool $autoNull2EmptyString = false;

    protected function controllerPath(): Attribute
    {
        return new Attribute(
            set: fn ($value) => $this->removeBasePath($value)
        );
    }

    protected function modelPath(): Attribute
    {
        return new Attribute(
            set: fn ($value) => $this->removeBasePath($value)
        );
    }

    protected function requestPath(): Attribute
    {
        return new Attribute(
            set: fn ($value) => $this->removeBasePath($value)
        );
    }

    protected function removeBasePath($path)
    {
        return Str::of($path)->replace(base_path(), '')->replace('\\', '/');
    }
}
