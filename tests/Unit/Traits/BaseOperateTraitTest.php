<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use XditnModule\Traits\DB\BaseOperate;
use XditnModule\Traits\DB\WithAttributes;
use XditnModule\Traits\DB\WithEvents;
use XditnModule\Traits\DB\WithSearch;

class BaseOperateTraitTest extends TestCase
{
    protected function getTestModel(): Model
    {
        return new class() extends Model {
            use BaseOperate;
            use WithAttributes;
            use WithEvents;
            use WithSearch;

            protected $table = 'test_table';

            protected $fillable = ['id', 'name', 'status', 'created_at', 'updated_at'];

            public $timestamps = false;
        };
    }

    protected function getTestModelWithoutTimestamps(): Model
    {
        return new class() extends Model {
            use BaseOperate;
            use WithAttributes;
            use WithEvents;
            use WithSearch;

            protected $table = 'test_table';

            protected $fillable = ['id', 'name', 'status'];

            public $timestamps = false;
        };
    }

    public function test_get_created_at_column_with_fillable(): void
    {
        $model = $this->getTestModel();

        // 当 created_at 在 fillable 中时，应该返回 'created_at'
        $this->assertEquals('created_at', $model->getCreatedAtColumn());
    }

    public function test_get_created_at_column_without_fillable(): void
    {
        $model = $this->getTestModelWithoutTimestamps();

        // 当 created_at 不在 fillable 中时，应该返回 null
        $this->assertNull($model->getCreatedAtColumn());
    }

    public function test_get_updated_at_column_with_fillable(): void
    {
        $model = $this->getTestModel();

        // 当 updated_at 在 fillable 中时，应该返回 'updated_at'
        $this->assertEquals('updated_at', $model->getUpdatedAtColumn());
    }

    public function test_get_updated_at_column_without_fillable(): void
    {
        $model = $this->getTestModelWithoutTimestamps();

        // 当 updated_at 不在 fillable 中时，应该返回 null
        $this->assertNull($model->getUpdatedAtColumn());
    }

    public function test_get_creator_id_column(): void
    {
        $model = $this->getTestModel();

        $this->assertEquals('creator_id', $model->getCreatorIdColumn());
    }

    public function test_get_parent_id_column(): void
    {
        $model = $this->getTestModel();

        $this->assertEquals('parent_id', $model->getParentIdColumn());
    }

    public function test_set_parent_id_column(): void
    {
        $model = $this->getTestModel();
        $model->setParentIdColumn('pid');

        $this->assertEquals('pid', $model->getParentIdColumn());
    }

    public function test_set_paginate(): void
    {
        $model = $this->getTestModel();
        $model->setPaginate(false);

        $reflection = new \ReflectionClass($model);
        $property = $reflection->getProperty('isPaginate');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($model));
    }

    public function test_disable_paginate(): void
    {
        $model = $this->getTestModel();
        $model->disablePaginate();

        $reflection = new \ReflectionClass($model);
        $property = $reflection->getProperty('isPaginate');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($model));
    }

    public function test_as_tree(): void
    {
        $model = $this->getTestModel();
        $model->asTree();

        $reflection = new \ReflectionClass($model);
        $property = $reflection->getProperty('asTree');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($model));
    }

    public function test_set_data_range(): void
    {
        $model = $this->getTestModel();
        $model->setDataRange(true);

        $reflection = new \ReflectionClass($model);
        $property = $reflection->getProperty('dataRange');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($model));
    }

    public function test_set_auto_null_to_empty_string(): void
    {
        $model = $this->getTestModel();
        $model->setAutoNull2EmptyString(false);

        $reflection = new \ReflectionClass($model);
        $property = $reflection->getProperty('autoNull2EmptyString');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($model));
    }

    public function test_fill_creator_id(): void
    {
        $model = $this->getTestModel();
        $model->fillCreatorId(false);

        $reflection = new \ReflectionClass($model);
        $property = $reflection->getProperty('isFillCreatorId');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($model));
    }

    public function test_alias_field_string(): void
    {
        $model = $this->getTestModel();

        $aliased = $model->aliasField('name');

        $this->assertEquals('test_table.name', $aliased);
    }

    public function test_alias_field_array(): void
    {
        $model = $this->getTestModel();

        $aliased = $model->aliasField(['name', 'status']);

        $this->assertEquals(['test_table.name', 'test_table.status'], $aliased);
    }

    public function test_without_form(): void
    {
        $model = new class() extends Model {
            use BaseOperate;
            use WithAttributes;
            use WithEvents;
            use WithSearch;

            protected $table = 'test_table';

            protected $fillable = ['id', 'name'];

            protected array $form = ['name', 'status'];
        };

        $model->withoutForm();

        $this->assertEquals([], $model->getForm());
    }

    public function test_get_form(): void
    {
        $model = new class() extends Model {
            use BaseOperate;
            use WithAttributes;
            use WithEvents;
            use WithSearch;

            protected $table = 'test_table';

            protected $fillable = ['id', 'name'];

            protected array $form = ['name', 'status'];
        };

        $this->assertEquals(['name', 'status'], $model->getForm());
    }

    public function test_set_searchable(): void
    {
        $model = $this->getTestModel();
        $model->setSearchable(['name' => 'like', 'status' => '=']);

        $this->assertEquals(['name' => 'like', 'status' => '='], $model->searchable);
    }
}
