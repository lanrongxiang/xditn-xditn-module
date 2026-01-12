<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Modules\VideoSubscription\Models\HomeCategory;
use Modules\VideoSubscription\Models\Video;
use Modules\VideoSubscription\Models\VideoCategory;
use Modules\VideoSubscription\Models\VipPlan;
use Modules\VideoSubscription\Traits\HasMultilingualFields;
use XditnModule\Base\CatchModel;
use XditnModule\Traits\DB\BaseOperate;
use XditnModule\Traits\DB\DateformatTrait;
use XditnModule\Traits\DB\ScopeTrait;
use XditnModule\Traits\DB\WithAttributes;

/**
 * mysql 表数据，提供给前端的数据，格式 [{label:'label',value:'value'}].
 *
 * 如果有 parent_id 则为树形
 *
 * value 默认为 id，label 默认为 name
 *
 * 支持多语言字段：如果检测到模型使用了 HasMultilingualFields trait，
 * 会自动使用 {field}_text 访问器和动态搜索字段
 *
 * @class TableData
 */
class TableData implements OptionInterface
{
    /**
     * 表名到模型类的映射.
     */
    protected array $tableModelMap = [
        'video_categories' => VideoCategory::class,
        'vip_plans' => VipPlan::class,
        'videos' => Video::class,
        'home_categories' => HomeCategory::class,
    ];

    public function get(): array|Collection
    {
        $tableName = Request::get('table');
        if (!$tableName) {
            return [];
        }
        // value 字段
        $value = Request::get('value', 'id');
        // label 字段
        $label = Request::get('label', 'name');
        // 父级字段
        $parentIdColumn = Request::get('pid');
        // 过滤，只过滤 label 数据
        $filter = Request::get('filter');
        // 获取表字段
        $columns = Schema::getColumnListing($tableName);

        // 尝试获取对应的模型类
        $modelClass = $this->getModelClass($tableName);
        $isMultilingual = false;
        $labelTextField = null;
        $labelSearchField = null;

        if ($modelClass) {
            $modelInstance = new $modelClass();
            // 检查是否使用了 HasMultilingualFields trait
            $traits = class_uses_recursive($modelClass);
            if (in_array(HasMultilingualFields::class, $traits)) {
                $isMultilingual = true;
                // 检查 label 字段是否为多语言字段
                $multilingualFields = $modelInstance->getMultilingualFields() ?? [];
                if (in_array($label, $multilingualFields)) {
                    // 使用 {field}_text 访问器
                    $labelTextField = $label.'_text';
                    // 获取动态搜索字段
                    $defaultLocale = config('multilingual.default_search_locale', 'zh');
                    $labelSearchField = $label.'_'.$defaultLocale;
                }
            }
        }

        // 创建模型实例
        if ($modelClass && $isMultilingual) {
            $model = new $modelClass();
        } elseif (in_array('deleted_at', $columns)) {
            $model = new class() extends CatchModel {};
        } else {
            $model = new class() extends Model {
                use BaseOperate;
                use DateformatTrait;
                use ScopeTrait;
                use WithAttributes;
            };
            $model->setTable($tableName);
        }

        // 构建查询字段
        $query = $model->query();

        if ($isMultilingual && $modelClass) {
            // 对于多语言模型，需要选择：id、name（多语言JSON字段，用于访问器）、name_zh（搜索字段）、parent_id
            $selectFields = [$value, $label]; // id 和 name（多语言字段）
            if ($labelSearchField && in_array($labelSearchField, $columns)) {
                $selectFields[] = $labelSearchField; // name_zh（用于搜索）
            }
            if ($parentIdColumn && in_array($parentIdColumn, $columns)) {
                $selectFields[] = $parentIdColumn;
            }
            $query->select($selectFields);
        } else {
            // 对于非多语言模型
            $fields = [$value, $label];
            if ($parentIdColumn && in_array($parentIdColumn, $columns)) {
                $fields[] = $parentIdColumn;
            }
            $query->select($fields);
        }

        // 搜索过滤
        if ($filter) {
            if ($isMultilingual && $labelSearchField) {
                // 使用动态搜索字段
                $query->where($labelSearchField, 'like', "%{$filter}%");
            } else {
                // 使用普通字段搜索
                $query->whereLike($label, "{$filter}");
            }
        }

        // 树形结构
        if ($parentIdColumn && in_array($parentIdColumn, $columns)) {
            $results = $query->get();
            $tree = $results->toTree(pidField: $parentIdColumn)->values();

            // 对于多语言模型，只返回必要的字段
            if ($isMultilingual && $labelTextField) {
                return $tree->map(fn ($item) => $this->formatMultilingualItem($item, $value, $label, $labelTextField, $parentIdColumn, true));
            }

            return $tree;
        }

        // 分页
        if (Request::get('page')) {
            $paginate = $query->paginate(Request::get('limit', 10));

            $items = $paginate->items();
            // 对于多语言模型，只返回必要的字段
            if ($isMultilingual && $labelTextField) {
                $items = array_map(fn ($item) => $this->formatMultilingualItem($item, $value, $label, $labelTextField, $parentIdColumn), $items);
            }

            return [
                'data' => $items,
                'total' => $paginate->total(),
                'limit' => $paginate->perPage(),
                'page' => $paginate->currentPage(),
            ];
        }

        // 普通列表
        $results = $query->get();

        // 对于多语言模型，只返回必要的字段
        if ($isMultilingual && $labelTextField) {
            return $results->map(fn ($item) => $this->formatMultilingualItem($item, $value, $label, $labelTextField, $parentIdColumn));
        }

        // 对于非多语言模型，需要手动构建返回格式
        return $results->map(function ($item) use ($value, $label) {
            if (is_array($item)) {
                return [
                    'value' => $item[$value] ?? null,
                    'label' => $item[$label] ?? '',
                ];
            }

            return [
                'value' => $item->{$value} ?? null,
                'label' => $item->{$label} ?? '',
            ];
        });
    }

    /**
     * 根据表名获取模型类.
     */
    protected function getModelClass(string $tableName): ?string
    {
        return $this->tableModelMap[$tableName] ?? null;
    }

    /**
     * 格式化多语言项，只返回必要的字段
     * 返回的字段名使用原始字段名（如 name），但值来自访问器（如 name_text）.
     *
     * @param mixed $item 数据项（数组或模型实例）
     * @param string $valueField 值字段名
     * @param string $labelField 标签字段名（原始字段名，如 name）
     * @param string $labelTextField 标签文本访问器名（如 name_text）
     * @param string|null $parentIdColumn 父级ID字段名
     * @param bool $includeChildren 是否包含 children 字段（树形结构）
     *
     * @return array
     */
    protected function formatMultilingualItem($item, string $valueField, string $labelField, string $labelTextField, ?string $parentIdColumn, bool $includeChildren = false): array
    {
        // 如果是数组
        if (is_array($item)) {
            $result = [
                $valueField => $item[$valueField] ?? null,
                $labelField => $item[$labelTextField] ?? '', // 字段名用 labelField，值用 labelTextField
            ];
            if ($parentIdColumn) {
                $result[$parentIdColumn] = $item[$parentIdColumn] ?? null;
            }
            if ($includeChildren) {
                $result['children'] = $item['children'] ?? null;
            }

            return $result;
        }

        // 如果是模型实例，转换为数组并获取访问器值
        $array = is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : [];
        $result = [
            $valueField => $array[$valueField] ?? $item->{$valueField} ?? null,
            $labelField => $array[$labelTextField] ?? $item->{$labelTextField} ?? '', // 字段名用 labelField，值用 labelTextField
        ];
        if ($parentIdColumn) {
            $result[$parentIdColumn] = $array[$parentIdColumn] ?? $item->{$parentIdColumn} ?? null;
        }
        if ($includeChildren) {
            $result['children'] = $array['children'] ?? ($item->children ?? null);
        }

        return $result;
    }
}
