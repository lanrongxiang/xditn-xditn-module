<?php

namespace Modules\Common\Http\Controllers;

use Exception;
use Modules\Common\Models\Area;

/**
 * @group 管理端
 *
 * @subgroup 地区管理
 *
 * @subgroupDescription  后台地区管理
 */
class AreaController
{
    /**
     * 地区列表.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object[] 地区数据（树形结构）
     * @responseField data[].id int 地区ID
     * @responseField data[].name string 地区名称
     * @responseField data[].parent_id int 父级ID
     * @responseField data[].children object[] 子级
     * @responseField data[].children[].id int 地区ID
     * @responseField data[].children[].name string 地区名称
     * @responseField data[].children[].parent_id int 父级ID
     *
     * @param Area $area
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function index(Area $area)
    {
        return $area->getAll();
    }
}
