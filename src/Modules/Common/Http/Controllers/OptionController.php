<?php

namespace Modules\Common\Http\Controllers;

use Exception;
use Illuminate\Support\Collection;
use Modules\Common\Repository\Options\Factory;

/**
 * @group 管理端
 *
 * @subgroup 选项管理
 *
 * @subgroupDescription  后台选项管理
 */
class OptionController
{
    /**
     * 选项列表.
     *
     * 获取各种筛选选项数据，支持通过 filter 参数进行搜索过滤
     *
     * @urlParam name string required 选项名称，可选值：
     *   - users: 用户筛选（支持 filter 参数搜索用户名/邮箱/手机号）
     *   - vipPlans: VIP套餐计划筛选（支持 filter 参数搜索套餐名称，支持 status 参数筛选状态）
     *   - videoCategories: 视频分类筛选（支持 filter 参数搜索分类名称，支持 status 参数筛选状态，支持 tree=true 返回树形结构）
     *   - payPlatforms: 支付平台筛选
     *   - payStatuses: 支付状态筛选
     *   - refundStatuses: 退款状态筛选
     *   - accessTypes: 视频访问类型筛选
     *   - planTypes: 套餐类型筛选
     *   - status: 通用状态筛选（启用/禁用）
     *   - dictionaries: 字典筛选
     *   - 其他 Common 模块支持的选项
     *
     * @queryParam filter string 搜索关键词，用于筛选选项（部分选项支持）
     * @queryParam status int 状态筛选（部分选项支持，如 vipPlans、videoCategories）
     * @queryParam tree boolean 是否返回树形结构（仅 videoCategories 支持）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] 选项数据
     * @responseField data[].value string|int 选项值
     * @responseField data[].label string 选项标签/名称
     * @responseField data[].children object[] 子选项（树形结构时存在）
     *
     * @param $name
     * @param Factory $factory
     *
     * @return array|Collection
     *
     * @throws Exception
     */
    public function index($name, Factory $factory): array|Collection
    {
        return $factory->make($name)->get();
    }
}
