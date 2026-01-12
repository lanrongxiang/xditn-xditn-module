<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\VideoSubscription\Models\VipPlan;

/**
 * VIP套餐计划筛选选项.
 */
class VipPlans implements OptionInterface
{
    public function get(): array
    {
        $request = request();
        $filter = $request->get('filter', '');
        $status = $request->get('status');

        // 获取动态搜索字段名（根据配置的默认语言）
        $defaultLocale = config('multilingual.default_search_locale', 'zh');
        $nameSearchField = 'name_'.$defaultLocale;

        $query = VipPlan::query()
            ->select(['id', 'name', $nameSearchField, 'type', 'price', 'status'])
            ->when($filter, function ($query) use ($filter, $nameSearchField) {
                // 使用动态搜索字段进行搜索
                $query->where($nameSearchField, 'like', "%{$filter}%");
            })
            ->when($status !== null, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('sort')
            ->orderBy('id');

        $plans = $query->get();

        return $plans->map(function ($plan) {
            return [
                'value' => $plan->id,
                'label' => $plan->name_text.' ('.number_format($plan->price / 100, 2).' USD)', // 使用 name_text 访问器获取多语言文本
            ];
        })->toArray();
    }
}
