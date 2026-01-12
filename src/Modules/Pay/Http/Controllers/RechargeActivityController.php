<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Modules\Pay\Models\RechargeActivity;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 充值活动管理
 *
 * @subgroupDescription  后台支付管理->充值活动管理
 */
class RechargeActivityController extends Controller
{
    public function __construct(
        protected readonly RechargeActivity $model
    ) {
    }

    /**
     * 充值活动列表.
     *
     * @queryParam type int 活动类型:1=折扣活动,2=充值档位送金币
     * @queryParam status int 状态:1=启用,2=禁用
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 活动列表
     * @responseField data[].id int 活动ID
     * @responseField data[].title string 活动名称
     * @responseField data[].description string 活动描述
     * @responseField data[].type int 活动类型:1=折扣活动,2=充值档位送金币
     * @responseField data[].min_amount float 最低充值金额（元）
     * @responseField data[].max_amount float|null 最高充值金额（元）
     * @responseField data[].discount_rate float|null 折扣率（0-100，仅折扣活动）
     * @responseField data[].bonus_coins int|null 赠送金币数（仅充值档位活动）
     * @responseField data[].original_coins int|null 原价金币数（仅充值档位活动）
     * @responseField data[].start_at string|null 活动开始时间
     * @responseField data[].end_at string|null 活动结束时间
     * @responseField data[].status int 状态:1=启用,2=禁用
     * @responseField data[].sort int 排序
     * @responseField data[].created_at string 创建时间
     * @responseField data[].updated_at string 更新时间
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 新增充值活动.
     *
     * @bodyParam title string required 活动名称
     * @bodyParam description string 活动描述
     * @bodyParam type int required 活动类型:1=折扣活动,2=充值档位送金币
     * @bodyParam min_amount float required 最低充值金额（元）。例如：99.99元传入99.99
     * @bodyParam max_amount float 最高充值金额（元）。例如：100元传入100
     * @bodyParam discount_rate float 折扣率(0-100)，仅折扣活动使用
     * @bodyParam bonus_coins int 赠送金币数，仅充值档位活动使用
     * @bodyParam original_coins int 原价金币数，仅充值档位活动使用
     * @bodyParam start_at string 活动开始时间
     * @bodyParam end_at string 活动结束时间
     * @bodyParam status int 状态:1=启用,2=禁用
     * @bodyParam sort int 排序
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 活动信息
     * @responseField data.id int 活动ID
     * @responseField data.title string 活动名称
     * @responseField data.description string 活动描述
     * @responseField data.type int 活动类型
     * @responseField data.min_amount float 最低充值金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.max_amount float|null 最高充值金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.discount_rate float|null 折扣率
     * @responseField data.bonus_coins int|null 赠送金币数
     * @responseField data.original_coins int|null 原价金币数
     * @responseField data.start_at string|null 活动开始时间
     * @responseField data.end_at string|null 活动结束时间
     * @responseField data.status int 状态
     * @responseField data.sort int 排序
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function store(): mixed
    {
        return $this->model->storeBy(request()->all());
    }

    /**
     * 充值活动详情.
     *
     * @urlParam id int required 活动ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 活动详情
     * @responseField data.id int 活动ID
     * @responseField data.title string 活动名称
     * @responseField data.description string 活动描述
     * @responseField data.type int 活动类型:1=折扣活动,2=充值档位送金币
     * @responseField data.min_amount float 最低充值金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.max_amount float|null 最高充值金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.discount_rate float|null 折扣率（0-100，仅折扣活动）
     * @responseField data.bonus_coins int|null 赠送金币数（仅充值档位活动）
     * @responseField data.original_coins int|null 原价金币数（仅充值档位活动）
     * @responseField data.start_at string|null 活动开始时间
     * @responseField data.end_at string|null 活动结束时间
     * @responseField data.status int 状态:1=启用,2=禁用
     * @responseField data.sort int 排序
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    /**
     * 更新充值活动.
     *
     * @urlParam id int required 活动ID
     *
     * @bodyParam title string 活动名称
     * @bodyParam description string 活动描述
     * @bodyParam type int 活动类型:1=折扣活动,2=充值档位送金币
     * @bodyParam min_amount float 最低充值金额（元），会自动转换为分存储。例如：99.99元传入99.99，存储为9999分
     * @bodyParam max_amount float 最高充值金额（元），会自动转换为分存储。例如：100元传入100，存储为10000分
     * @bodyParam discount_rate float 折扣率(0-100)，仅折扣活动使用
     * @bodyParam bonus_coins int 赠送金币数，仅充值档位活动使用
     * @bodyParam original_coins int 原价金币数，仅充值档位活动使用
     * @bodyParam start_at string 活动开始时间
     * @bodyParam end_at string 活动结束时间
     * @bodyParam status int 状态:1=启用,2=禁用
     * @bodyParam sort int 排序
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 活动信息
     * @responseField data.id int 活动ID
     * @responseField data.title string 活动名称
     * @responseField data.description string 活动描述
     * @responseField data.type int 活动类型
     * @responseField data.min_amount float 最低充值金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.max_amount float|null 最高充值金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.discount_rate float|null 折扣率
     * @responseField data.bonus_coins int|null 赠送金币数
     * @responseField data.original_coins int|null 原价金币数
     * @responseField data.start_at string|null 活动开始时间
     * @responseField data.end_at string|null 活动结束时间
     * @responseField data.status int 状态
     * @responseField data.sort int 排序
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function update($id): mixed
    {
        return $this->model->updateBy($id, request()->all());
    }

    /**
     * 删除充值活动.
     *
     * @urlParam id int required 活动ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     */
    public function destroy($id): mixed
    {
        return $this->model->deleteBy($id);
    }

    /**
     * 启用/禁用充值活动.
     *
     * @urlParam id int required 活动ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 活动信息
     * @responseField data.id int 活动ID
     * @responseField data.status int 状态:1=启用,2=禁用
     */
    public function enable($id): mixed
    {
        return $this->model->toggleBy($id);
    }
}
