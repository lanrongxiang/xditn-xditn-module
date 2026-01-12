<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use XditnModule\Base\XditnModuleModel;
use XditnModule\Traits\DB\Trans;

class TransTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_transactions');
        parent::tearDown();
    }

    public function test_begin_transaction(): void
    {
        $model = new TestTransactionModel();

        $this->assertFalse(DB::transactionLevel() > 0);

        $model->beginTransaction();

        $this->assertTrue(DB::transactionLevel() > 0);

        $model->rollback();
    }

    public function test_commit_transaction(): void
    {
        $model = new TestTransactionModel();

        $model->beginTransaction();
        TestTransactionModel::create(['name' => 'test']);
        $model->commit();

        $this->assertDatabaseHas('test_transactions', ['name' => 'test']);
    }

    public function test_rollback_transaction(): void
    {
        $model = new TestTransactionModel();

        $model->beginTransaction();
        TestTransactionModel::create(['name' => 'test']);
        $model->rollback();

        $this->assertDatabaseMissing('test_transactions', ['name' => 'test']);
    }

    public function test_transaction_closure_success(): void
    {
        $model = new TestTransactionModel();

        $result = $model->transaction(function () {
            return TestTransactionModel::create(['name' => 'test']);
        });

        $this->assertNotNull($result);
        $this->assertDatabaseHas('test_transactions', ['name' => 'test']);
    }

    public function test_transaction_closure_rollback_on_exception(): void
    {
        $this->expectException(\Exception::class);

        $model = new TestTransactionModel();

        try {
            $model->transaction(function () {
                TestTransactionModel::create(['name' => 'test']);
                throw new \Exception('Test exception');
            });
        } finally {
            $this->assertDatabaseMissing('test_transactions', ['name' => 'test']);
        }
    }
}

class TestTransactionModel extends XditnModuleModel
{
    use Trans;

    protected $table = 'test_transactions';
    protected $fillable = ['name'];
}
