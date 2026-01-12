<?php

namespace Modules\Cms\Dynamics;

use CatchForm\Builder;
use CatchForm\Components\Rules\Control;
use CatchForm\Form;
use CatchForm\Table\Action;
use CatchForm\Table\Table;
use Modules\Cms\Enums\Visible;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Tag;
use Modules\User\Models\User;
use XditnModule\Enums\Status;

class Post extends Builder
{
    protected function form(): mixed
    {
        // TODO: Implement form() method.
        return Form::make(function (Form $form) {
            $form->tab('post', 1)
                ->panes(function (Form $form) {
                    $form->tabPane('文章内容', 1)
                        ->class('gap-y-5 flex flex-col w-full md:w-1/2')
                        ->body(function (Form $form) {
                            $form->text('title', '标题')->required();
                            $form->richText('content', '内容')->required();
                            $form->upload('cover', '封面')->attach()->multiple();
                            $form->textarea('excerpt', '描述')->rows(3);
                        });

                    $form->tabPane('SEO 信息', 2)
                        ->class('gap-y-5 flex flex-col w-full md:w-1/2')
                        ->body(function (Form $form) {
                            $form->text('seo_title', '标题')->required();
                            $form->text('seo_keywords', '关键词')->required();
                            $form->text('seo_description', '描述')->required();
                        });

                    $form->tabPane('其他信息', 3)
                        ->class('gap-y-5 flex flex-col w-full md:w-1/2')
                        ->body(function (Form $form) {
                            $form->cascader('category_id', '选择分类')
                                ->required()
                                ->checkStrictly()
                                ->class('w-full')
                                ->options(Category::query()->get(['id as value', 'name as label', 'parent_id'])->toTree(id: 'value'));

                            $form->select('author', '作者')->required()
                                ->options(User::query()->get(['id as value', 'username as label']));

                            $form->select('visible', '可见状态')->options(Visible::class)
                                ->whenEqual(3, function (Control $control) {
                                    $control->required('password');
                                })->whenEqual(3, function (Control $control) {
                                    $control->show('password');
                                });

                            $form->text('password', '查看密码');

                            $form->select('tags', '标签')
                                ->required()
                                ->options(Tag::query()->get(['name as value', 'name as label']))
                                ->allowCreate(true)->multiple(true)->filterable(true);

                            $form->radio('is_can_comment', '是否允许评论')
                                ->options([[
                                    'label' => '是',
                                    'value' => 1,
                                ], [
                                    'label' => '否',
                                    'value' => 2,
                                ]])
                                ->defaultValue(1)
                                ->asButton();

                            $form->radio('type', '类型')->options([
                                [
                                    'label' => '文章',
                                    'value' => 1,
                                ], [
                                    'label' => '页面',
                                    'value' => 2, ],
                            ])->asButton()->defaultValue(1);

                            $form->radio('status', '状态')->options(Status::class)->asButton()
                                ->defaultValue(1);

                            $form->radio('top', '置顶')->asButton()->options([
                                [
                                    'label' => '分类置顶',
                                    'value' => 1,
                                ], [
                                    'label' => '首页置顶',
                                    'value' => 2,
                                ], [
                                    'label' => '全局置顶',
                                    'value' => 3,
                                ],
                            ])->defaultValue(1);

                            $form->number('sort', '排序')->defaultValue(1);
                        });
                })->class('w-full pt-5 pr-4 bg-white dark:bg-regal-dark');
        });
    }

    protected function table(): mixed
    {
        // TODO: Implement table() method.
        return Table::make('cms/post')->columns(function (Table $table) {
            $table->id();

            $table->column('title', '标题')->width(500);

            $table->column('category', '分类');

            $table->column('creator', '作者')->tags(true);

            $table->column('status', '发布状态')->switch();

            $table->column('sort', '排序')->sortable();

            $table->column('updated_at', '更新时间');

            $table->operate()->update(false);
        })->showOperation(false)->search(function (Table $table) {
            $table->text('标题', 'title');

            $table->tree('分类', 'category_id')->options(Category::query()->get(['id', 'name', 'parent_id'])->toTree())->value('id')->label('name');
        })->headLeftAction([
            Action::create('创建文章')->route('/cms/articles/create'),
        ])->prependAction(
            Action::update('编辑')->primary()->route('/cms/articles/create/:id')->small()->asText(),
        );
    }
}
