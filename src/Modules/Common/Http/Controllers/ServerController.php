<?php

namespace Modules\Common\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use XditnModule\Support\Decomposer;

/**
 * @group 管理端
 *
 * @subgroup 服务器信息
 *
 * @subgroupDescription  后台服务器信息
 */
class ServerController
{
    /**
     * 服务器信息.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 服务器信息
     * @responseField data.os string 操作系统
     * @responseField data.php string PHP版本
     * @responseField data.laravel string Laravel版本
     * @responseField data.XditnModule string XditnModule版本
     * @responseField data.host string 域名
     * @responseField data.mysql string MySQL版本
     * @responseField data.memory_limit string 内存限制
     * @responseField data.max_execution_time string 最大执行时间
     * @responseField data.upload_max_filesize string 上传文件大小
     *
     * @return array
     */
    public function info()
    {
        return Cache::adminRemember('server_info', 7 * 3600 * 24, function () {
            return Decomposer::getReportJson();
        });
    }
}
