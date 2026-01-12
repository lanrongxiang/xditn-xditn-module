<?php

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Modules\Permissions\Models\Roles;
use Modules\System\Models\RoleHasColumns;
use Modules\System\Models\TableColumn;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 数据库表管理
 *
 * @subgroupDescription  后台系统管理->数据库表管理
 */
class SchemaController extends Controller
{
    /**
     * 数据库表列表.
     *
     * @urlParam page int 页码
     * @urlParam limit int 每页数量
     * @urlParam name string 表名（搜索）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField limit int 每页数量
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField data object[] 数据
     * @responseField data[].table_name string 表名
     * @responseField data[].engine string 存储引擎
     * @responseField data[].table_rows int 行数
     * @responseField data[].data_length string 数据大小
     * @responseField data[].index_length string 索引大小
     * @responseField data[].table_comment string 表注释
     * @responseField data[].table_collation string 字符集
     * @responseField data[].created_at string 创建时间
     * @responseField data[].has_role_columns bool 是否有角色字段权限
     *
     * @param Request $request
     * @param TableColumn $tableColumn
     *
     * @return LengthAwarePaginator
     */
    public function index(Request $request, TableColumn $tableColumn)
    {
        $database = config('database.connections.mysql.database');

        $SQL = <<<SQL
SELECT table_name, engine, table_rows, data_length, index_length, table_comment, table_collation, create_time as created_at FROM information_schema.tables where TABLE_SCHEMA = "{$database}"
SQL;

        $tables = Collection::make(json_decode(json_encode(DB::select($SQL)), true));

        $tables = $tables->map(function ($table) {
            $table = array_change_key_case($table);
            $table['data_length'] = Number::fileSize($table['data_length']);
            $table['index_length'] = Number::fileSize($table['index_length']);

            return $table;
        });

        // 搜索
        if ($request->get('name')) {
            $tables = $tables->filter(function ($table) use ($request) {
                return str_contains($table['table_name'], $request->get('name'));
            })->values();
        }

        $page = $request->get('page');
        $limit = $request->get('limit');

        // 判断表的字段是否跟角色有关联，如果有，则展示相关字段管理
        $hasRoleTables = $tableColumn->getHasRoleTableNames();
        $filterTables = $tables->slice(($page - 1) * $limit, $limit)
            ->values()
            ->map(function ($table) use ($hasRoleTables) {
                $table['has_role_columns'] = in_array($table['table_name'], $hasRoleTables);

                return $table;
            });

        return new LengthAwarePaginator(
            $filterTables,
            count($tables),
            $limit,
            $page
        );
    }

    /**
     * 获取表字段.
     *
     * @urlParam table string required 表名
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] 字段列表
     * @responseField data[].name string 字段名
     * @responseField data[].type string 字段类型
     * @responseField data[].nullable bool 是否可为空
     * @responseField data[].default mixed 默认值
     * @responseField data[].readable_roles integer[] 可读角色ID列表
     * @responseField data[].writeable_roles integer[] 可写角色ID列表
     *
     * @param $table
     * @param TableColumn $tableColumn
     *
     * @return array
     */
    public function fields($table, TableColumn $tableColumn)
    {
        $columns = Schema::getColumns(Str::of($table)->remove(config('database.connections.mysql.prefix'))->toString());
        $tableColumns = $tableColumn->getColumnsContainRoles($table);
        foreach ($columns as &$column) {
            if (isset($tableColumns[$column['name']])) {
                if (!empty($tableColumns[$column['name']]['readable_roles'])) {
                    $column['readable_roles'] = array_column($tableColumns[$column['name']]['readable_roles'], 'id');
                } else {
                    $column['readable_roles'] = [];
                }

                if (!empty($tableColumns[$column['name']]['writeable_roles'])) {
                    $column['writeable_roles'] = array_column($tableColumns[$column['name']]['writeable_roles'], 'id');
                } else {
                    $column['writeable_roles'] = [];
                }

            } else {
                $column['readable_roles'] = [];
                $column['writeable_roles'] = [];
            }
        }

        return $columns;
    }    /**
     * 设置字段角色权限.
     *
     * @bodyParam table string required 表名
     * @bodyParam columns array required 字段配置数组
     * @bodyParam columns[].name string required 字段名
     * @bodyParam columns[].readable_roles integer[] 可读角色ID列表
     * @bodyParam columns[].writeable_roles integer[] 可写角色ID列表
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @param Request $request
     * @param TableColumn $tableColumn
     *
     * @return mixed
     */
    public function fieldsRoleVisible(Request $request, TableColumn $tableColumn): mixed
    {
        if (DB::transaction(function () use ($request, $tableColumn) {
            // 处理
            $table = $request->get('table');
            $columns = $request->get('columns');

            $saveColumns = [];
            foreach ($columns as $column) {
                if (!empty($column['readable_roles'])) {
                    $saveColumns[] = $column['name'];
                }
                if (!empty($column['writeable_roles'])) {
                    $saveColumns[] = $column['name'];
                }
            }
            // 去重
            $saveColumns = array_unique($saveColumns);
            // 保存 ['column_name' => 'id']
            $savedColumnIds = $tableColumn->storeColumns($table, $saveColumns);

            // 处理可读角色的字段权限
            $dealReadableRoles = function ($columns) use ($savedColumnIds, $table, $tableColumn) {
                // 可读角色 [role_id => [column_id, column_id, ...]]
                $readableRoleHasColumns = [];
                foreach ($columns as $column) {
                    if (!empty($column['readable_roles'])) {
                        foreach ($column['readable_roles'] as $roleId) {
                            $readableRoleHasColumns[$roleId][] = $savedColumnIds[$column['name']];
                        }
                    }
                }

                foreach ($readableRoleHasColumns as $_roleId => $_columnIds) {
                    $role = new Roles();
                    $role->saveReadableColumns($_roleId, $_columnIds, array_values($savedColumnIds));
                }

                // 获取已关联的可读角色ID
                $releatedReadableRoleIds = RoleHasColumns::getReadableRolesByColumnIds($tableColumn->where('table_name', $table)->pluck('id'));
                $roleIds = array_keys($readableRoleHasColumns);

                // 如果没有关联的角色，则删除所有关联的可读角色
                if (!count($readableRoleHasColumns)) {
                    foreach ($releatedReadableRoleIds as $roleId) {
                        $role = new Roles();
                        $role->detachReadableColumns($roleId, $savedColumnIds);
                    }
                } else {
                    foreach ($releatedReadableRoleIds as $roleId) {
                        if (!in_array($roleId, $roleIds)) {
                            //移除可读column的数据
                            $role = new Roles();
                            $role->detachReadableColumns($roleId, $savedColumnIds);
                        }
                    }
                }
            };

            // 处理可写角色的字段权限
            $dealWriteableRoles = function ($columns) use ($savedColumnIds, $table, $tableColumn) {
                // 可写角色
                $writeableRoleHasColumns = [];
                foreach ($columns as $column) {
                    if (!empty($column['writeable_roles'])) {
                        foreach ($column['writeable_roles'] as $roleId) {
                            $writeableRoleHasColumns[$roleId][] = $savedColumnIds[$column['name']];
                        }
                    }
                }

                foreach ($writeableRoleHasColumns as $_roleId => $_columnIds) {
                    $role = new Roles();
                    $role->saveWriteableColumns($_roleId, $_columnIds, array_values($savedColumnIds));
                }

                // 获取已关联的可写角色ID
                $releatedWriteableRoleIds = RoleHasColumns::getWriteableRolesByColumnIds($tableColumn->where('table_name', $table)->pluck('id'));
                $roleIds = array_keys($writeableRoleHasColumns);

                // 如果没有关联的角色，则删除所有关联的可读角色
                if (!count($writeableRoleHasColumns)) {
                    foreach ($releatedWriteableRoleIds as $roleId) {
                        $role = new Roles();
                        $role->detachWriteableColumns($roleId, $savedColumnIds);
                    }
                } else {
                    foreach ($releatedWriteableRoleIds as $roleId) {
                        if (!in_array($roleId, $roleIds)) {
                            //移除可读column的数据
                            $role = new Roles();
                            $role->detachWriteableColumns($roleId, $savedColumnIds);
                        }
                    }
                }
            };

            $dealReadableRoles($columns);
            $dealWriteableRoles($columns);

            return true;
        })) {
            $tableColumns = $tableColumn->with(['readableRoles', 'writeableRoles'])->get();

            $columnHasRoles = [];

            foreach ($tableColumns as $column) {
                if (!$column->readableRoles->isEmpty()) {
                    $columnHasRoles[$column->table_name][$column->column_name]['readable_roles'] =
                            array_unique(
                                array_merge(
                                    $columnHasRoles[$column->table_name][$column->column_name]['readable_roles'] ?? [],
                                    $column->readableRoles->pluck('id')->toArray()
                                )
                            );
                }

                if (!$column->writeableRoles->isEmpty()) {
                    $columnHasRoles[$column->table_name][$column->column_name]['writeable_roles'] =
                        array_unique(array_merge(
                            $columnHasRoles[$column->table_name][$column->column_name]['writeable_roles'] ?? [],
                            $column->writeableRoles->pluck('id')->toArray()
                        ));
                }
            }

            // 缓存
            Cache::forever('column_has_roles', $columnHasRoles);
        }

        return true;
    }

    /**
     * 获取已有权限的字段.
     *
     * @param Request $request
     * @param TableColumn $tableColumn
     *
     * @return \Illuminate\Database\Eloquent\Collection|Collection|TableColumn[]
     */
    public function fieldsManage(Request $request, TableColumn $tableColumn)
    {
        return $tableColumn->getTableColumns($request->get('table'));
    }

    /**
     * 删除字段权限.
     *
     * @urlParam id int required 字段权限ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @param $id
     * @param TableColumn $tableColumn
     *
     * @return bool|null
     */
    public function destroyField($id, TableColumn $tableColumn)
    {
        return $tableColumn->deleteById($id);
    }
}
