<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use XditnModule\Base\XditnModuleModel;
use XditnModule\Traits\DB\WithAttributes;

class WithAttributesTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_attributes');
        parent::tearDown();
    }

    public function test_get_parent_id_column(): void
    {
        $model = new TestAttributesModel();

        $this->assertSame('parent_id', $model->getParentIdColumn());
    }

    public function test_set_paginate(): void
    {
        $model = new TestAttributesModel();

        $result = $model->setPaginate(false);

        $this->assertSame($model, $result);
    }

    public function test_disable_paginate(): void
    {
        $model = new TestAttributesModel();

        $result = $model->disablePaginate();

        $this->assertSame($model, $result);
    }

    public function test_as_tree(): void
    {
        $model = new TestAttributesModel();

        $result = $model->asTree();

        $this->assertSame($model, $result);
    }

    public function test_set_data_range(): void
    {
        $model = new TestAttributesModel();

        $result = $model->setDataRange(true);

        $this->assertSame($model, $result);
    }

    public function test_set_column_access(): void
    {
        $model = new TestAttributesModel();

        $result = $model->setColumnAccess(true);

        $this->assertSame($model, $result);
    }

    public function test_set_auto_null_to_empty_string(): void
    {
        $model = new TestAttributesModel();

        $result = $model->setAutoNull2EmptyString(false);

        $this->assertSame($model, $result);
    }

    public function test_fill_creator_id(): void
    {
        $model = new TestAttributesModel();

        $result = $model->fillCreatorId(false);

        $this->assertSame($model, $result);
    }
}

class TestAttributesModel extends XditnModuleModel
{
    use WithAttributes;

    protected $table = 'test_attributes';
    protected $fillable = ['name', 'parent_id'];
}
