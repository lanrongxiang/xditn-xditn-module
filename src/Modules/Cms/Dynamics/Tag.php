<?php

namespace Modules\Cms\Dynamics;

use CatchForm\Builder;
use CatchForm\Form;
use CatchForm\Table\Table;

class Tag extends Builder
{
    protected function form(): mixed
    {
        // TODO: Implement form() method.
        return Form::make(function (Form $form) {
            $form->text('name', '名称')->required();
        });
    }

    protected function table(): mixed
    {
        // TODO: Implement table() method.
        return Table::make('/cms/tag')->columns(function (Table $table) {
            $table->selection();

            $table->id();

            $table->column('name', '名称');

            $table->column('created_at', '创建时间');

            $table->operate();
        })->search(function (Table $table) {
            $table->text('名称', 'name');
        });
    }
}
