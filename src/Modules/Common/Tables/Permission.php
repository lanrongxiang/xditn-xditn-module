<?php

namespace Modules\Common\Tables;

use CatchForm\Components\Rules\Control;
use CatchForm\Form;
use CatchForm\Table\Table;
use Modules\Common\Repository\Options\Modules;
use Modules\Permissions\Enums\MenuType;
use XditnModule\Enums\Status;

class Permission extends Dynamic
{
    public function table()
    {
        return Table::make('permissions/permissions')->columns(function (Table $table) {
            $table->column('permission_name', '权限名称');

            $table->column('route', '菜单路由');

            $table->operate();
        })->dialog(900)->rowKey();
    }

    protected function form()
    {
        return Form::make(function (Form $form) {
            $form->col(function (Form $form) {
                $form->radio('type', '菜单类型')->required()->asButton()->options(MenuType::class)
                    ->defaultValue(1)
                    // 目录
                    ->whenEqual(MenuType::Top->value(), function (Control $control) {
                        $control->required(['permission_name', 'module', 'route']);
                    })
                    // 菜单操作
                    ->whenEqual(MenuType::Menu->value(), function (Control $control) {
                        $control->required(['permission_name', 'module', 'route', 'select_permission_mark', 'parent_id']);
                    })
                    // 按钮操作
                    ->whenEqual(MenuType::Action->value(), function (Control $control) {
                        $control->required(['permission_name', 'text_permission_mark', 'parent_id']);
                    })
                    ->emitChange();

                $form->text('permission_name', '菜单名称')->maxlength(30)->showWordLimit();

                $form->select('module', '所属模块')->options((new Modules())->get())->emitChange();

                $form->text('route', '路由Path')->maxlength(30)->showWordLimit()->required();

                $form->text('redirect', 'Redirect')->maxlength(50)->showWordLimit();

                $form->number('sort', '排序')->min(0)->max(999999)->defaultValue(1);
            })->span12();

            $form->col(function (Form $form) {
                $form->cascader('parent_id', '上级菜单')->options(
                    \Modules\Permissions\Models\Permissions::query()
                        ->whereIn('type', [
                            MenuType::Menu->value,
                            MenuType::Top->value,
                        ])->get(['id as value', 'permission_name as label', 'parent_id'])->toTree(id: 'value')
                )->checkStrictly()->class('w-full');

                $form->selectOptions('permission_mark', '权限标识')
                    ->alias('select_permission_mark')
                    ->api('controllers');

                $form->text('permission_mark', '权限标识')->alias('text_permission_mark');

                $form->iconSelect('icon', '选择icon')->class('w-full');

                $form->selectOptions('component', '所属组件')->api('components');

                $form->radio('hidden', 'Hidden')->options(Status::class)->defaultValue(Status::Enable->value());

                $form->radio('keepalive', 'Keepalive')->options(Status::class)->defaultValue(Status::Enable->value());

            })->span12();

            $form->text('active_menu', '激活菜单')
                ->info('如果是访问内页的菜单路由，例如创建文章 create/post, 虽然它隶属于文章列表，但实际上并不会嵌套在文章列表路由里
而是单独的一个路由，并且是不显示在左侧菜单的。所以在访问它的时候，需要左侧菜单高亮，则需要设置该参数');
        });
    }
}
