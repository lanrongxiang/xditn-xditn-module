<?php

declare(strict_types=1);

namespace XditnModule\Commands\Create;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

/**
 * 创建 API 资源命令.
 *
 * 使用示例：
 * ```bash
 * php artisan xditn:module:make:resource Pay RechargeActivity
 * php artisan xditn:module:make:resource Pay RechargeActivity --collection
 * ```
 */
class Resource extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:resource {module} {name} {--collection : Create a resource collection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API resource for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->getResourceName();
        $isCollection = $this->option('collection');

        $resourcePath = $this->getResourcePath($module);

        // 确保目录存在
        if (!File::isDirectory($resourcePath)) {
            File::makeDirectory($resourcePath, 0755, true);
        }

        // 生成 Resource
        $resourceFile = $resourcePath.$name.'Resource.php';
        if ($this->createFile($resourceFile, $this->buildResourceContent($module, $name))) {
            $this->info($resourceFile.' has been created');
        }

        // 如果指定了 --collection 或者总是生成 Collection
        if ($isCollection) {
            $collectionFile = $resourcePath.$name.'Collection.php';
            if ($this->createFile($collectionFile, $this->buildCollectionContent($module, $name))) {
                $this->info($collectionFile.' has been created');
            }
        }

        return self::SUCCESS;
    }

    /**
     * 创建文件.
     */
    protected function createFile(string $file, string $content): bool
    {
        if (File::exists($file)) {
            $answer = $this->ask($file.' already exists, Did you want replace it?', 'Y');

            if (!Str::of($answer)->lower()->exactly('y')) {
                return false;
            }
        }

        File::put($file, $content);

        return File::exists($file);
    }

    /**
     * 获取资源目录路径.
     */
    protected function getResourcePath(string $module): string
    {
        return XditnModule::getModulePath($module).'Http'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR;
    }

    /**
     * 获取资源名称.
     */
    protected function getResourceName(): string
    {
        return Str::of($this->argument('name'))
            ->studly()
            ->replace('Resource', '')
            ->replace('Collection', '')
            ->toString();
    }

    /**
     * 构建 Resource 文件内容.
     */
    protected function buildResourceContent(string $module, string $name): string
    {
        $namespace = XditnModule::getModuleNamespace($module).'\\Http\\Resources';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * {$name} API 资源.
 *
 * @property int \$id
 */
class {$name}Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            // TODO: 添加更多字段映射
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request \$request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
            ],
        ];
    }
}

PHP;
    }

    /**
     * 构建 Collection 文件内容.
     */
    protected function buildCollectionContent(string $module, string $name): string
    {
        $namespace = XditnModule::getModuleNamespace($module).'\\Http\\Resources';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * {$name} 资源集合.
 */
class {$name}Collection extends ResourceCollection
{
    /**
     * 指定资源类.
     *
     * @var string
     */
    public \$collects = {$name}Resource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
            'data' => \$this->collection,
            'links' => [
                'self' => url()->current(),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request \$request): array
    {
        return [
            'meta' => [
                'total' => \$this->collection->count(),
            ],
        ];
    }
}

PHP;
    }
}
