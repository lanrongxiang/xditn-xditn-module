<?php

namespace Modules\Common\Tables;

use CatchForm\Components\Rules\Control;
use CatchForm\Form;
use CatchForm\Table\Table;
use Modules\Permissions\Enums\DataRange;
use Modules\Permissions\Models\Departments;
use Modules\Permissions\Models\Roles;

class Role extends Dynamic
{
    public function table()
    {
        return Table::make('permissions/roles')->columns(function (Table $table) {
            $table->column('role_name', '角色名称');

            $table->column('identify', '角色标识');

            // 角色描述
            $table->column('description', '角色描述');

            // 创建时间
            $table->column('created_at', '创建时间');

            $table->operate();
        })->dialog(800)->rowKey()
            ->search(function (Table $table) {
                $table->text('角色名称', 'roles.role_name');

                $table->text('角色标识', 'identify');
            });

    }

    protected function form()
    {
        return Form::make(function (Form $form) {
            $form->treeSelect('parent_id', '父级角色')->data(
                Roles::query()->get(['id as value', 'role_name as label', 'parent_id'])->toTree(id: 'value')->toArray()
            )->class('w-full')->emitChange()->checkStrictly(true);

            $form->text('role_name', '角色名称ssss')->maxlength(30)->showWordLimit()->required();

            $form->text('identify', '角色标识')->maxlength(30)->showWordLimit()->required();

            $form->textarea('description', '角色描述')->maxlength(200)->showWordLimit();

            $form->select('data_range', '数据权限')->options(DataRange::class)
                ->whenEqual(DataRange::Personal_Choose->value(), function (Control $control) {
                    $control->hide('departments');
                });

            $form->treeSelect('departments', '自定义权限')->data(
                Departments::query()->get()->toTree()->toArray()
            )->toProps([
                'label' => 'department_name',
                'value' => 'id',
            ])->showCheckbox()->required()->multiple()->valueKey('id');

            $form->custom('permissions', '权限')->type('div')
                ->subs(function (Form $form) {
                    $form->tree('permissions', '')->data([])->toProps([
                        'label' => 'permission_name',
                        'value' => 'id',
                    ])->showCheckbox()->loadData('permissionsOption', 'props.data')->class('w-full');
                })->class(['w-full h-40 pt-2 pl-2 overflow-auto border border-gray-300 rounded']);
            /**
             * $form->tree('permissions', '权限')->data(
             * \Modules\Permissions\Models\Permissions::query()->get(['id', 'permission_name', 'parent_id'])->toTree()
             * )->toProps([
             * 'label' => 'permission_name',
             * 'value' => 'id'
             * ])->showCheckbox(true);*/
        });
    }
}
