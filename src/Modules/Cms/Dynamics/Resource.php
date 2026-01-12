<?php

namespace Modules\Cms\Dynamics;

use CatchForm\Builder;
use CatchForm\Components\Rules\Control;
use CatchForm\Form;
use CatchForm\Table\Table;

class Resource extends Builder
{
    protected function form(): mixed
    {
        // TODO: Implement form() method.
        return Form::make(function (Form $form) {
            $form->text('name', '名称')->required();

            $form->radio('type', '类型')->options([
                ['value' => 1, 'label' => '轮播图'],
                ['value' => 2, 'label' => '友情链接'],
                ['value' => 3, 'label' => '广告'],
            ])->required()->defaultValue(1)
                ->asButton()
                ->whenEqual(2, function (Control $control) {
                    $control->required('url');
                })
                ->whenNotEqual(2, function (Control $control) {
                    $control->hide('content');
                })
                ->whenNotEqual(2, function (Control $control) {
                    $control->required('content');
                });

            $form->upload('content', '上传图片');

            $form->url('url', '链接');

            $form->textarea('description', '描述');

            $form->radio('is_visible', '可见性')->options([
                ['value' => 1, 'label' => '可见'],
                ['value' => 2, 'label' => '隐藏'],
            ])->required()->asButton()->defaultValue(1);

            $form->radio('is_visible', '打开方式')->options([
                ['value' => 1, 'label' => '本窗口'],
                ['value' => 2, 'label' => '新窗口'],
            ])->required()->asButton()->defaultValue(1);
        });
    }

    protected function table(): mixed
    {

        // TODO: Implement table() method.
        return Table::make('/cms/resource')->columns(function (Table $table) {

            $table->selection();

            $table->id();

            $table->column('name', '名称');

            $table->column('content', '图片')->image();

            $table->column('url', '链接')->link(true, '链接');

            $table->column('type', '类型')->tags(['danger', 'info', 'success'])
                ->filter(
                    <<<'FUNC'
    (value) => {
      return value === 1 ? '轮播图' : value === 2 ? '友情链接' : '广告'
    }
FUNC
                );

            $table->operate();
        })->search(function (Table $table) {
            $table->text('名称', 'name');
        });
    }
}
