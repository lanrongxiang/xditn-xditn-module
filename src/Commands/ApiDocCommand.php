<?php

declare(strict_types=1);

namespace XditnModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Knuckles\Camel\Camel;
use Knuckles\Scribe\Matching\RouteMatcherInterface;
use Knuckles\Scribe\Tools\ConsoleOutputUtils as c;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Knuckles\Scribe\Tools\Globals;
use Knuckles\Scribe\Tools\PathConfig;
use Knuckles\Scribe\Writing\Writer;
use XditnModule\Support\ApiEndPoints;

/**
 * API 文档生成命令.
 *
 * 需要安装 knuckleswtf/scribe 包才能使用
 * 安装命令: composer require --dev knuckleswtf/scribe
 *
 * 注意：Laravel 12 可能需要等待 knuckleswtf/scribe 更新支持
 * 如果安装失败，请检查包的 Laravel 版本兼容性
 *
 * @see https://scribe.knuckles.wtf/
 */
class ApiDocCommand extends Command
{
    protected $signature = 'xditn:module:api:doc
                            {--config=XDITN_api_doc : 选择使用哪个配置文件, 默认为 config/XDITN_api_doc.php }
    ';

    protected $description = '生成 XditnModule API 文档（Postman JSON）';

    protected DocumentationConfig $docConfig;

    protected PathConfig $paths;

    /**
     * 生成 Postman JSON 文档.
     */
    public function handle(RouteMatcherInterface $routeMatcher): void
    {
        $this->bootstrap();
        $docConfig = $this->docConfig;
        $configFileOrder = $docConfig->get('groups.order', []);
        $apiEndPoints = new ApiEndPoints($this, $routeMatcher, $this->paths);
        $extractedEndpoints = $apiEndPoints->get();
        $userDefinedEndpoints = Camel::loadUserDefinedEndpoints(Camel::camelDir($this->paths));
        $groupedEndpoints = $this->mergeUserDefinedEndpoints($extractedEndpoints, $userDefinedEndpoints);
        $groupedEndpoints = Camel::prepareGroupedEndpointsForOutput($groupedEndpoints, $configFileOrder);
        $this->generatePostmanJson($groupedEndpoints);

        $this->info('生成 XditnModule API 文档（Postman JSON）成功');
    }

    public function getDocConfig(): DocumentationConfig
    {
        return $this->docConfig;
    }

    public function bootstrap(): void
    {
        Globals::$shouldBeVerbose = $this->option('verbose');

        c::bootstrapOutput($this->output);

        $configName = $this->option('config');
        if (!config($configName)) {
            throw new \InvalidArgumentException("(config/{$configName}.php) 配置文件不存在");
        }

        $this->paths = new PathConfig($configName);

        $this->docConfig = new DocumentationConfig(config($this->paths->configName));

        // Force root URL so it works in Postman collection
        $baseUrl = $this->docConfig->get('base_url') ?? config('app.url');
        URL::forceRootUrl($baseUrl);
    }

    protected function mergeUserDefinedEndpoints(array $groupedEndpoints, array $userDefinedEndpoints): array
    {
        foreach ($userDefinedEndpoints as $endpoint) {
            $indexOfGroupWhereThisEndpointShouldBeAdded = Arr::first(array_keys($groupedEndpoints), function ($key) use ($groupedEndpoints, $endpoint) {
                $group = $groupedEndpoints[$key];

                return $group['name'] === ($endpoint['metadata']['groupName'] ?? $this->docConfig->get('groups.default', ''));
            });

            if ($indexOfGroupWhereThisEndpointShouldBeAdded !== null) {
                $groupedEndpoints[$indexOfGroupWhereThisEndpointShouldBeAdded]['endpoints'][] = $endpoint;
            } else {
                $newGroup = [
                    'name' => $endpoint['metadata']['groupName'] ?? $this->docConfig->get('groups.default', ''),
                    'description' => $endpoint['metadata']['groupDescription'] ?? null,
                    'endpoints' => [$endpoint],
                ];

                $groupedEndpoints[$newGroup['name']] = $newGroup;
            }
        }

        return $groupedEndpoints;
    }

    /**
     * 生成 Postman Json 文件.
     */
    protected function generatePostmanJson(array $groups): void
    {
        $writer = new Writer($this->docConfig, $this->paths);
        $collection = $writer->generatePostmanCollection($groups);

        file_put_contents($this->docConfig->get('base_path').DIRECTORY_SEPARATOR.'postman.json', $collection);
    }
}
