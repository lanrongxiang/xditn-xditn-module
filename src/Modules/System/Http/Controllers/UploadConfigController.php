<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig;
use Modules\System\Support\Configure;
use XditnModule\Base\XditnModuleController as Controller;
use XditnModule\Exceptions\FailedException;

/**
 * @group 管理端
 *
 * @subgroup 上传配置
 *
 * @subgroupDescription  后台系统管理->上传配置
 */
class UploadConfigController extends Controller
{
    public function __construct(
        protected readonly SystemConfig $model
    ) {
    }

    /**
     * 保存上传配置.
     *
     * @bodyParam driver string required 上传驱动
     * @bodyParam file_exts array 允许的文件扩展名
     * @bodyParam image_exts array 允许的图片扩展名
     * @bodyParam limit_size int 文件大小限制（MB）
     * @bodyParam config object 驱动配置
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        $driver = $request->get('driver');
        if (!$driver) {
            throw new FailedException('请先选择上传驱动');
        }

        $config = Configure::parse('upload', $request->only(['file_exts', 'image_exts', 'limit_size']));
        $config = array_merge($config, Configure::parse("upload.$driver", $request->except(['file_exts', 'image_exts', 'limit_size', 'driver'])));
        $config['upload.driver'] = $driver;

        return $this->model->storeBy($config);
    }

    /**
     * 获取上传配置.
     *
     * @urlParam driver string 上传驱动
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 上传配置
     * @responseField data.driver string 上传驱动
     * @responseField data.limit_size int 文件大小限制
     * @responseField data.file_exts array 允许的文件扩展名
     * @responseField data.image_exts array 允许的图片扩展名
     * @responseField data.config object 驱动配置
     *
     * @param $driver
     *
     * @return array
     */
    public function show($driver = null)
    {
        if (!$driver) {
            $driver = config('upload.driver');
        }

        $fileExts = config()->get('upload.file_exts');
        $imageExts = config()->get('upload.image_exts');

        return [
            'driver' => $driver,
            'limit_size' => (int) config('upload.limit_size', 1),
            'file_exts' => $fileExts,
            'image_exts' => $imageExts,
            'config' => config("upload.$driver", []),
        ];
    }
}
