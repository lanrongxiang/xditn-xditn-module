<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\VideoSubscription\Models\VideoCategory;

/**
 * 视频分类筛选选项（支持树形结构）.
 */
class VideoCategories implements OptionInterface
{
    public function get(): array
    {
        $request = request();
        $filter = $request->get('filter', '');
        $status = $request->get('status', 1); // 默认只返回启用的
        $tree = $request->get('tree', false); // 是否返回树形结构

        // 获取动态搜索字段名（根据配置的默认语言）
        $defaultLocale = config('multilingual.default_search_locale', 'zh');
        $nameSearchField = 'name_'.$defaultLocale;

        $query = VideoCategory::query()
            ->select(['id', 'name', $nameSearchField, 'parent_id', 'level', 'status'])
            ->when($filter, function ($query) use ($filter, $nameSearchField) {
                // 使用动态搜索字段进行搜索
                $query->where($nameSearchField, 'like', "%{$filter}%");
            })
            ->when($status !== null, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('sort')
            ->orderBy('id');

        $categories = $query->get();

        if ($tree) {
            // 返回树形结构
            return $this->buildTree($categories);
        }

        // 返回扁平结构，带层级前缀
        return $categories->map(function ($category) {
            $prefix = str_repeat('├─ ', $category->level - 1);

            return [
                'value' => $category->id,
                'label' => $prefix.$category->name_text, // 使用 name_text 访问器获取多语言文本
            ];
        })->toArray();
    }

    /**
     * 构建树形结构.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, VideoCategory> $categories
     * @param int $parentId
     *
     * @return array<int, array{value: int, label: string, children?: array}>
     */
    protected function buildTree($categories, int $parentId = 0): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $item = [
                    'value' => $category->id,
                    'label' => $category->name_text, // 使用 name_text 访问器获取多语言文本
                ];
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }

        return $tree;
    }
}
