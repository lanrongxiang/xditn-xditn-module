<?php

namespace Modules\Cms\Dynamics;

use CatchForm\Builder;
use CatchForm\Components\Rules\Control;
use CatchForm\Form;
use CatchForm\Table\Table;
use Modules\Cms\Enums\CategoryType;
use Modules\Cms\Models\Category as CategoryModel;

class Category extends Builder
{
    protected function form(): mixed
    {
        // TODO: Implement form() method.
        return Form::make(function (Form $form) {
            $form->cascader('parent_id', '父级分类')
                ->options(CategoryModel::query()->get(['id as value', 'name as label', 'parent_id'])->toTree(id: 'value'))
                ->checkStrictly()
                ->class('w-full');

            $form->text('name', '分类名称')->required();

            $form->text('slug', '别名')->required()
                ->help('别名可以自定义分类名称, 通常只包含字母、数字和"_,-"连字符。别名可以自定义作 url 短链接使用，所以分类是可以自定义链接的');

            $form->select('type', '类型')->options(CategoryType::class)
                ->whenEqual(CategoryType::HREF->value(), function (Control $control) {
                    $control->required('link');

                    $control->show('link');
                });

            $form->text('link', '链接');

            $form->number('order', '排序')->defaultValue(1);
        });
    }

    protected function table(): mixed
    {
        // TODO: Implement table() method.
        return Table::make('/cms/category')->columns(function (Table $table) {
            $table->column('id', 'ID')->width(150);

            $table->column('name', '名称');

            $table->column('slug', '别名');

            $table->column('url', '链接')->link(true, '链接');

            $table->column('post_count', '数量');

            $table->column('created_at', '创建时间');

            $table->operate();
        })->asTree()->search(function (Table $table) {
            $table->text('分类名称', 'name');
            $table->select('类型', 'type')->options(CategoryType::class);
        });
    }
}
