<?php

// +----------------------------------------------------------------------
// | XditnModule [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://XditnModule.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/XditnModule-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

namespace Modules\Develop\Support\Generate;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Develop\Models\SchemaFiles;
use Modules\Develop\Support\Generate\Create\Controller;
use Modules\Develop\Support\Generate\Create\Dynamic;
use Modules\Develop\Support\Generate\Create\FrontForm;
use Modules\Develop\Support\Generate\Create\FrontTable;
use Modules\Develop\Support\Generate\Create\Menu;
use Modules\Develop\Support\Generate\Create\Model;
use Modules\Develop\Support\Generate\Create\Request;
use Modules\Develop\Support\Generate\Create\Route;
use Modules\Develop\Support\Generate\Exception\MenuCreateFailException;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use XditnModule\Exceptions\FailedException;

/**
 * @class Generator
 */
class Generator
{
    /**
     * @var array{module:string,controller:string,model:string,paginate: bool,schema: string}
     */
    protected array $gen;

    /**
     * @var array{name: string,charset: string, collection: string,
     *      comment:string,created_at: bool, updated_at: bool, deleted_at: bool,
     *      creator_id: bool, updated_at: bool, engine: string}
     */
    protected array $schema;

    protected array $structures;

    protected mixed $schemaId;

    protected array $files = [];

    /**
     * this model name from controller.
     */
    protected string $modelName;

    /**
     * this request name for controller.
     */
    protected ?string $requestName = null;

    protected ?string $originRouteContent = null;

    /**
     * generate.
     *
     * @throws Exception
     */
    public function generate(): bool
    {
        // 是否生成文件（默认 true）
        $generateFiles = $this->gen['generateFiles'] ?? true;

        try {
            if ($generateFiles) {
                // 生成文件模式
                // 前端文件生成
                $this->files['dynamic_path'] = $this->createDynamic();
                $this->files['table_path'] = $this->createFrontTable();
                $this->files['form_path'] = $this->createFrontForm();

                // 后端文件生成（根据 generateBackend 标志决定是否生成）
                $generateBackend = $this->gen['generateBackend'] ?? false;
                if ($generateBackend) {
                    $this->files['model_path'] = $this->createModel();
                    $this->files['request_path'] = $this->createRequest();
                    $this->files['controller_path'] = $this->createController();
                    $this->createRoute();
                } else {
                    // 不生成后端文件时，设置为空
                    $this->files['model_path'] = false;
                    $this->files['request_path'] = false;
                    $this->files['controller_path'] = false;
                }

                // 生成菜单
                (new Menu($this->gen))->useDialogForm($this->gen['dialogForm'])->generate();
            } else {
                // 不生成文件，只生成内容并保存到数据库
                $this->files['dynamic_path'] = $this->getDynamicContent();
                $this->files['table_path'] = $this->getFrontTableContent();
                $this->files['form_path'] = $this->getFrontFormContent();

                // 后端文件内容生成（根据 generateBackend 标志决定是否生成）
                $generateBackend = $this->gen['generateBackend'] ?? false;
                if ($generateBackend) {
                    $this->files['model_path'] = $this->getModelContent();
                    $this->files['request_path'] = $this->getRequestContent();
                    $this->files['controller_path'] = $this->getControllerContent();
                } else {
                    // 不生成后端文件时，设置为空
                    $this->files['model_path'] = false;
                    $this->files['request_path'] = false;
                    $this->files['controller_path'] = false;
                }
            }

            // 保存文件内容到数据库
            $this->saveFiles($this->files, $generateFiles);
        } catch (MenuCreateFailException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('代码生成错误'.$e->getMessage());
            if ($generateFiles) {
                $this->rollback();
            }
            throw $e;
        } finally {
            $this->files = [];
        }

        return true;
    }

    /**
     * create route.
     *
     * @throws FileNotFoundException
     */
    public function createRoute(): bool|string
    {
        // 保存之前的 route 文件
        $route = new Route($this->gen['controller']);

        $route = $route->setModule($this->gen['module']);

        // 保存原始的 route 文件内容
        $this->originRouteContent = $route->getOriginContent();

        return $route->create();
    }

    /**
     * create font.
     *
     * @throws FileNotFoundException
     */
    public function createFrontTable(): bool|string|null
    {
        $apiString = (new Route($this->gen['controller']))->setModule($this->gen['module'])->getApiRoute();
        $table = new FrontTable(
            $this->gen['controller'],
            $this->gen['paginate'],
            $apiString,
            $this->gen['form'],
            $this->gen['dymaic'],
            $this->gen['dialogForm'],
            $this->gen['operations']
        );

        return $table->setModule($this->gen['module'])->setStructures($this->structures)->create();
    }

    public function createDynamic()
    {
        if ($this->gen['dymaic']) {
            $apiString = (new Route($this->gen['controller']))->setModule($this->gen['module'])->getApiRoute();

            $dynamic = new Dynamic(
                $this->gen['controller'],
                $this->structures,
                $this->gen['form'],
                $apiString,
                $this->gen['dialogForm']
            );

            return $dynamic->setModule($this->gen['module'])->create();
        }
    }

    /**
     * create font.
     *
     * @throws FileNotFoundException
     */
    public function createFrontForm(): bool|string|null
    {
        // 无需创建 form
        if (!$this->gen['form']) {
            return false;
        }

        $apiString = (new Route($this->gen['controller']))->setModule($this->gen['module'])->getApiRoute();

        $form = new FrontForm(
            $this->gen['controller'],
            $this->gen['dymaic'],
            $this->gen['dialogForm'],
            $apiString
        );

        return $form->setModule($this->gen['module'])->setStructures($this->structures)->setTableName($this->gen['schema'])->create();
    }

    /**
     * create model.
     *
     * @throws FileNotFoundException
     */
    protected function createModel(): bool|string
    {
        if (!$this->gen['model']) {
            throw new FailedException('模型名称不能为空');
        }

        $model = new Model($this->gen['model'], $this->gen['schema'], $this->gen['module'], $this->gen['relations']);

        $this->modelName = $model->getModelName();

        return $model->setModule($this->gen['module'])->setStructures($this->structures)->create();
    }

    /**
     * create request.
     *
     * @throws FileNotFoundException
     */
    protected function createRequest(): bool|string
    {
        $request = new Request($this->gen['controller']);

        $file = $request->setStructures($this->structures)->setModule($this->gen['module'])->create();

        $this->requestName = $request->getRequestName();

        return $file;
    }

    /**
     * create controller.
     *
     * @throws FileNotFoundException
     */
    protected function createController(): bool|string
    {
        $controller = new Controller($this->gen['controller'], $this->modelName, $this->requestName, $this->gen['form'], $this->gen['dymaic'], $this->structures, $this->gen['operations']);

        return $controller->setModule($this->gen['module'])->create();
    }

    /**
     * 获取动态文件内容（不写入文件）.
     */
    protected function getDynamicContent(): array|false
    {
        if (!$this->gen['dymaic']) {
            return false;
        }

        $apiString = (new Route($this->gen['controller']))->setModule($this->gen['module'])->getApiRoute();

        $dynamic = new Dynamic(
            $this->gen['controller'],
            $this->structures,
            $this->gen['form'],
            $apiString,
            $this->gen['dialogForm']
        );

        $dynamic->setModule($this->gen['module']);
        $content = $dynamic->getContent();

        if ($content instanceof PhpFile) {
            $printer = new PsrPrinter();
            $printer->setTypeResolving(false);
            $content = $printer->printFile($content);
        }

        return [
            'path' => $dynamic->getFile(),
            'content' => $content ?: '',
        ];
    }

    /**
     * 获取前端表格内容（不写入文件）.
     */
    protected function getFrontTableContent(): array|false
    {
        $apiString = (new Route($this->gen['controller']))->setModule($this->gen['module'])->getApiRoute();
        $table = new FrontTable(
            $this->gen['controller'],
            $this->gen['paginate'],
            $apiString,
            $this->gen['form'],
            $this->gen['dymaic'],
            $this->gen['dialogForm'],
            $this->gen['operations']
        );

        $table->setModule($this->gen['module'])->setStructures($this->structures);
        $content = $table->getContent();

        return [
            'path' => $table->getFile(),
            'content' => $content ?: '',
        ];
    }

    /**
     * 获取前端表单内容（不写入文件）.
     */
    protected function getFrontFormContent(): array|false
    {
        if (!$this->gen['form']) {
            return false;
        }

        $apiString = (new Route($this->gen['controller']))->setModule($this->gen['module'])->getApiRoute();

        $form = new FrontForm(
            $this->gen['controller'],
            $this->gen['dymaic'],
            $this->gen['dialogForm'],
            $apiString
        );

        $form->setModule($this->gen['module'])->setStructures($this->structures)->setTableName($this->gen['schema']);
        $content = $form->getContent();

        return [
            'path' => $form->getFile(),
            'content' => $content ?: '',
        ];
    }

    /**
     * 获取模型内容（不写入文件）.
     */
    protected function getModelContent(): array|false
    {
        if (!$this->gen['model']) {
            return false;
        }

        $model = new Model($this->gen['model'], $this->gen['schema'], $this->gen['module'], $this->gen['relations']);

        $this->modelName = $model->getModelName();
        $model->setModule($this->gen['module'])->setStructures($this->structures);
        $content = $model->getContent();

        if ($content instanceof PhpFile) {
            $printer = new PsrPrinter();
            $printer->setTypeResolving(false);
            $content = $printer->printFile($content);
        }

        return [
            'path' => $model->getFile(),
            'content' => $content ?: '',
        ];
    }

    /**
     * 获取请求验证内容（不写入文件）.
     */
    protected function getRequestContent(): array|false
    {
        $request = new Request($this->gen['controller']);

        $request->setStructures($this->structures)->setModule($this->gen['module']);
        $this->requestName = $request->getRequestName();
        $content = $request->getContent();

        if ($content instanceof PhpFile) {
            $printer = new PsrPrinter();
            $printer->setTypeResolving(false);
            $content = $printer->printFile($content);
        }

        return [
            'path' => $request->getFile(),
            'content' => $content ?: '',
        ];
    }

    /**
     * 获取控制器内容（不写入文件）.
     */
    protected function getControllerContent(): array|false
    {
        $controller = new Controller($this->gen['controller'], $this->modelName, $this->requestName, $this->gen['form'], $this->gen['dymaic'], $this->structures, $this->gen['operations']);

        $controller->setModule($this->gen['module']);
        $content = $controller->getContent();

        if ($content instanceof PhpFile) {
            $printer = new PsrPrinter();
            $printer->setTypeResolving(false);
            $content = $printer->printFile($content);
        }

        return [
            'path' => $controller->getFile(),
            'content' => $content ?: '',
        ];
    }

    /**
     * rollback.
     */
    protected function rollback(): void
    {
        // delete controller & model & migration file
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        // 回滚 route 文件
        if ($this->originRouteContent) {
            $route = new Route($this->gen['controller']);
            $route->setModule($this->gen['module'])->putOriginContent($this->originRouteContent);
        }
    }

    /**
     * 保存文件.
     *
     * @param array $params 文件路径或内容数组
     * @param bool $generateFiles 是否生成文件
     */
    protected function saveFiles($params, bool $generateFiles = false): mixed
    {
        $schemaFiles = SchemaFiles::where('schema_id', $this->schemaId)->first();

        $schemaFilesModel = new SchemaFiles();
        $data = [];
        foreach ($params as $key => $value) {
            $fileKey = Str::of($key)->replace('path', 'file')->toString();

            if ($generateFiles) {
                // 生成文件模式：从文件路径读取内容
                $filepath = $value;
                if ($filepath && file_exists($filepath)) {
                    $data[$key] = $filepath;
                    $data[$fileKey] = file_get_contents($filepath);
                } else {
                    $data[$key] = '';
                    $data[$fileKey] = '';
                }
            } else {
                // 不生成文件模式：直接使用内容数组
                if (is_array($value) && isset($value['path'], $value['content'])) {
                    $data[$key] = $value['path'];
                    $data[$fileKey] = $value['content'];
                } elseif ($value === false) {
                    $data[$key] = '';
                    $data[$fileKey] = '';
                } else {
                    $data[$key] = '';
                    $data[$fileKey] = '';
                }
            }
        }

        if ($schemaFiles) {
            return $schemaFilesModel->updateBy($schemaFiles->id, $data);
        } else {
            $data['schema_id'] = $this->schemaId;

            return $schemaFilesModel->storeBy($data);
        }
    }

    /**
     * set params.
     *
     * @return $this
     */
    public function setParams(array $params): Generator
    {
        $this->gen = $params['codeGen'];

        foreach ($params['structures'] as &$structure) {
            $structure['search'] = strlen($structure['search_op']) > 0;
        }

        $this->structures = $params['structures'];

        $this->schemaId = $params['schemaId'];

        return $this;
    }
}
