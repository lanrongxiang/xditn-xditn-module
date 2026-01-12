<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use XditnModule\Base\XditnModuleModel;
use XditnModule\Traits\DB\AmountTrait;

class AmountTraitTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 创建测试表
        Schema::create('test_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('amount')->default(0)->comment('金额（分）');
            $table->unsignedInteger('discount_amount')->default(0)->comment('折扣金额（分）');
            $table->unsignedInteger('total_amount')->default(0)->comment('总金额（分）');
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });
    }

    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        Schema::dropIfExists('test_orders');
        parent::tearDown();
    }

    /**
     * Helper method to set amountFields using reflection.
     */
    protected function setAmountFields(TestOrder $order, array $fields): void
    {
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('amountFields');
        $property->setAccessible(true);
        $property->setValue($order, $fields);
    }

    /**
     * Helper method to set amountConversionRate using reflection.
     */
    protected function setAmountConversionRate(TestOrder $order, int $rate): void
    {
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('amountConversionRate');
        $property->setAccessible(true);
        $property->setValue($order, $rate);
    }

    /**
     * Test amount field conversion (single field).
     */
    public function test_single_amount_field_conversion(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount']);

        // 测试入库：系统单位转分
        $order->amount = 100.50;
        $this->assertEquals(10050, $order->getAttributes()['amount']);

        // 测试出库：分转系统单位
        $order->setRawAttributes(['amount' => 10050]);
        $this->assertEquals(100.50, $order->amount);
    }

    /**
     * Test multiple amount fields conversion.
     */
    public function test_multiple_amount_fields_conversion(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount', 'discount_amount', 'total_amount']);

        // 测试入库：系统单位转分
        $order->amount = 100.50;
        $order->discount_amount = 10.25;
        $order->total_amount = 90.25;

        $this->assertEquals(10050, $order->getAttributes()['amount']);
        $this->assertEquals(1025, $order->getAttributes()['discount_amount']);
        $this->assertEquals(9025, $order->getAttributes()['total_amount']);

        // 测试出库：分转系统单位
        $order->setRawAttributes([
            'amount' => 10050,
            'discount_amount' => 1025,
            'total_amount' => 9025,
        ]);

        $this->assertEquals(100.50, $order->amount);
        $this->assertEquals(10.25, $order->discount_amount);
        $this->assertEquals(90.25, $order->total_amount);
    }

    /**
     * Test null value handling.
     */
    public function test_null_value_handling(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount']);

        // 测试 null 值入库
        $order->amount = null;
        $this->assertNull($order->getAttributes()['amount'] ?? null);

        // 测试 null 值出库
        $order->setRawAttributes(['amount' => null]);
        $this->assertNull($order->amount);
    }

    /**
     * Test custom conversion rate.
     */
    public function test_custom_conversion_rate(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount']);
        $this->setAmountConversionRate($order, 1000); // 1 USD = 1000 分

        // 测试入库：系统单位转分
        $order->amount = 100.50;
        $this->assertEquals(100500, $order->getAttributes()['amount']);

        // 测试出库：分转系统单位
        $order->setRawAttributes(['amount' => 100500]);
        $this->assertEquals(100.50, $order->amount);
    }

    /**
     * Test centsToAmount method.
     */
    public function test_cents_to_amount_method(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount']);

        $this->assertEquals(100.50, $order->centsToAmount(10050));
        $this->assertEquals(10.25, $order->centsToAmount(1025));
        $this->assertNull($order->centsToAmount(null));
    }

    /**
     * Test amountToCents method.
     */
    public function test_amount_to_cents_method(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount']);

        $this->assertEquals(10050, $order->amountToCents(100.50));
        $this->assertEquals(1025, $order->amountToCents(10.25));
        $this->assertNull($order->amountToCents(null));
    }

    /**
     * Test database save and retrieve.
     */
    public function test_database_save_and_retrieve(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount']);

        // 保存到数据库（系统单位）
        $order->amount = 100.50;
        $order->save();

        // 从数据库读取（自动转换为系统单位）
        $retrieved = TestOrder::find($order->id);
        $this->setAmountFields($retrieved, ['amount']);
        $this->assertEquals(100.50, $retrieved->amount);

        // 验证数据库存储的是分
        $rawData = \Illuminate\Support\Facades\DB::table('test_orders')->where('id', $order->id)->first();
        $this->assertEquals(10050, $rawData->amount);
    }

    /**
     * Test multiple amount fields with database.
     */
    public function test_multiple_amount_fields_with_database(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount', 'discount_amount', 'total_amount']);

        // 保存到数据库（系统单位）
        $order->amount = 100.50;
        $order->discount_amount = 10.25;
        $order->total_amount = 90.25;
        $order->save();

        // 从数据库读取（自动转换为系统单位）
        $retrieved = TestOrder::find($order->id);
        $this->setAmountFields($retrieved, ['amount', 'discount_amount', 'total_amount']);
        $this->assertEquals(100.50, $retrieved->amount);
        $this->assertEquals(10.25, $retrieved->discount_amount);
        $this->assertEquals(90.25, $retrieved->total_amount);

        // 验证数据库存储的是分
        $rawData = \Illuminate\Support\Facades\DB::table('test_orders')->where('id', $order->id)->first();
        $this->assertEquals(10050, $rawData->amount);
        $this->assertEquals(1025, $rawData->discount_amount);
        $this->assertEquals(9025, $rawData->total_amount);
    }

    /**
     * Test rounding precision.
     */
    public function test_rounding_precision(): void
    {
        $order = new TestOrder();
        $this->setAmountFields($order, ['amount']);

        // 测试小数精度
        $order->amount = 100.555;
        $this->assertEquals(10056, $order->getAttributes()['amount']); // 四舍五入

        $order->amount = 100.554;
        $this->assertEquals(10055, $order->getAttributes()['amount']); // 四舍五入
    }
}

/**
 * Test model for AmountTrait.
 */
class TestOrder extends XditnModuleModel
{
    use AmountTrait;

    protected $table = 'test_orders';

    protected $fillable = ['amount', 'discount_amount', 'total_amount'];

    /**
     * 金额字段列表.
     */
    protected array $amountFields = [];
}
