<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\ConnectorLog;
use Modules\System\Models\SystemConfig;
use Modules\System\Support\Configure;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 连接器日志
 *
 * @subgroupDescription  后台系统管理->连接器日志
 */
class ConnectorLogController extends Controller
{
    public function __construct(
        protected readonly ConnectorLog $model
    ) {
    }

    /**
     * 连接器日志列表.
     *
     * @urlParam page int 页码
     * @urlParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField limit int 每页数量
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField data object[] 数据
     * @responseField data[].id int ID
     * @responseField data[].method string HTTP 方法
     * @responseField data[].uri string 请求 URI
     * @responseField data[].status_code int 状态码
     * @responseField data[].time_taken float 耗时（秒）
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 连接器配置.
     *
     * @urlParam prefix string 配置前缀
     *
     * @bodyParam prefix string 配置前缀
     * @bodyParam config object 配置数据
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object|array 配置数据
     *
     * @param Request $request
     * @param SystemConfig $config
     *
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application|mixed
     */
    public function config(Request $request, SystemConfig $config)
    {
        if ($request->isMethod('POST')) {
            return $config->storeBy(
                Configure::parse('connector', $request->all())
            );
        } else {
            return config('connector.'.$request->get('prefix'));
        }
    }

    /**
     * 聚合数据统计
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 统计数据
     * @responseField data.total_requests int 总请求数
     * @responseField data.success_requests int 成功请求数
     * @responseField data.failed_requests int 失败请求数
     * @responseField data.avg_time_taken float 平均耗时
     *
     * @return array
     */
    public function summary()
    {
        return $this->model->summary();
    }

    /**
     * 状态码统计
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] 状态码统计
     * @responseField data[].status_code int 状态码
     * @responseField data[].count int 数量
     *
     * @return array
     */
    public function statusCode(): array
    {
        return $this->model->statusCodes();
    }

    /**
     * 接口耗时统计
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 耗时统计
     * @responseField data.avg float 平均耗时
     * @responseField data.min float 最小耗时
     * @responseField data.max float 最大耗时
     *
     * @return array
     */
    public function timeTaken(): array
    {
        return $this->model->timeTaken();
    }

    /**
     * 请求量 Top10.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] Top10 请求
     * @responseField data[].uri string 请求 URI
     * @responseField data[].method string HTTP 方法
     * @responseField data[].count int 请求次数
     *
     * @return array
     */
    public function requestsTop10(): array
    {
        $result = $this->model->requestsTop10();
        // 确保返回数组类型
        if (is_array($result)) {
            return $result;
        }
        // 如果是 Collection，转换为数组
        if (method_exists($result, 'toArray')) {
            return $result->toArray();
        }

        return (array) $result;
    }

    /**
     * 错误请求 Top10.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] Top10 错误请求
     * @responseField data[].uri string 请求 URI
     * @responseField data[].method string HTTP 方法
     * @responseField data[].status_code int 状态码
     * @responseField data[].count int 错误次数
     *
     * @return array
     */
    public function requestErrorsTop10(): array
    {
        return $this->model->requestErrorsTop10();
    }

    /**
     * 最快请求 Top10.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] Top10 最快请求
     * @responseField data[].uri string 请求 URI
     * @responseField data[].method string HTTP 方法
     * @responseField data[].time_taken float 耗时
     *
     * @return array
     */
    public function requestFastTop10(): array
    {
        return $this->model->requestFastTop10();
    }

    /**
     * 最慢请求 Top10.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] Top10 最慢请求
     * @responseField data[].uri string 请求 URI
     * @responseField data[].method string HTTP 方法
     * @responseField data[].time_taken float 耗时
     *
     * @return array
     */
    public function requestSlowTop10(): array
    {
        return $this->model->requestSlowTop10();
    }

    /**
     * 每小时请求统计
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] 每小时请求统计
     * @responseField data[].hour string 小时
     * @responseField data[].count int 请求次数
     *
     * @return array
     */
    public function everyHourRequests(): array
    {
        return $this->model->everyHourRequests();
    }

    /**
     * 每分钟请求统计
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] 每分钟请求统计
     * @responseField data[].minute string 分钟
     * @responseField data[].count int 请求次数
     *
     * @return array
     */
    public function everyMinuteRequests(): array
    {
        return $this->model->everyMinuteRequests();
    }
}
