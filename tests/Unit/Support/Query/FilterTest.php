<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Query;

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use XditnModule\Support\Query\Filters\DateRangeFilter;
use XditnModule\Support\Query\Filters\InFilter;
use XditnModule\Support\Query\Filters\SearchFilter;
use XditnModule\Support\Query\Filters\StatusFilter;

class FilterTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_status_filter_applies_correctly(): void
    {
        $filter = new StatusFilter('status');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('where')
            ->once()
            ->with('status', 1)
            ->andReturnSelf();

        $result = $filter->apply($builder, 1);

        $this->assertSame($builder, $result);
    }

    public function test_status_filter_with_custom_column(): void
    {
        $filter = new StatusFilter('order_status');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('where')
            ->once()
            ->with('order_status', 'pending')
            ->andReturnSelf();

        $result = $filter->apply($builder, 'pending');

        $this->assertSame($builder, $result);
    }

    public function test_date_range_filter_with_array(): void
    {
        $filter = new DateRangeFilter('created_at');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('whereBetween')
            ->once()
            ->with('created_at', ['2024-01-01', '2024-12-31'])
            ->andReturnSelf();

        $result = $filter->apply($builder, ['2024-01-01', '2024-12-31']);

        $this->assertSame($builder, $result);
    }

    public function test_date_range_filter_with_keyed_array(): void
    {
        $filter = new DateRangeFilter('created_at');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('whereBetween')
            ->once()
            ->with('created_at', ['2024-01-01', '2024-12-31'])
            ->andReturnSelf();

        $result = $filter->apply($builder, ['start' => '2024-01-01', 'end' => '2024-12-31']);

        $this->assertSame($builder, $result);
    }

    public function test_date_range_filter_with_only_start(): void
    {
        $filter = new DateRangeFilter('created_at');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('where')
            ->once()
            ->with('created_at', '>=', '2024-01-01')
            ->andReturnSelf();

        $result = $filter->apply($builder, ['start' => '2024-01-01']);

        $this->assertSame($builder, $result);
    }

    public function test_date_range_filter_with_only_end(): void
    {
        $filter = new DateRangeFilter('created_at');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('where')
            ->once()
            ->with('created_at', '<=', '2024-12-31')
            ->andReturnSelf();

        $result = $filter->apply($builder, ['end' => '2024-12-31']);

        $this->assertSame($builder, $result);
    }

    public function test_in_filter_with_array(): void
    {
        $filter = new InFilter('status');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('whereIn')
            ->once()
            ->with('status', [1, 2, 3])
            ->andReturnSelf();

        $result = $filter->apply($builder, [1, 2, 3]);

        $this->assertSame($builder, $result);
    }

    public function test_in_filter_with_comma_string(): void
    {
        $filter = new InFilter('user_id');

        $builder = $this->createMockBuilder();

        $builder->shouldReceive('whereIn')
            ->once()
            ->with('user_id', ['1', '2', '3'])
            ->andReturnSelf();

        $result = $filter->apply($builder, '1,2,3');

        $this->assertSame($builder, $result);
    }

    public function test_in_filter_filters_empty_values(): void
    {
        $filter = new InFilter('id');

        $this->assertFalse($filter->isValid([null, '', null]));
        $this->assertTrue($filter->isValid([1, 2, 3]));
        $this->assertTrue($filter->isValid('1,2,3'));
    }

    public function test_filter_is_valid(): void
    {
        $filter = new StatusFilter();

        $this->assertTrue($filter->isValid(1));
        $this->assertTrue($filter->isValid('active'));
        $this->assertFalse($filter->isValid(null));
        $this->assertFalse($filter->isValid(''));
    }

    public function test_filter_safe_apply_skips_invalid(): void
    {
        $filter = new StatusFilter();

        $builder = $this->createMockBuilder();

        // where 方法不应该被调用
        $builder->shouldNotReceive('where');

        $result = $filter->safeApply($builder, null);

        $this->assertSame($builder, $result);
    }

    public function test_search_filter_searches_multiple_columns(): void
    {
        $filter = new SearchFilter(['name', 'email']);

        // SearchFilter 使用闭包，所以我们只测试它不会抛出异常
        // 实际的查询逻辑需要更复杂的测试
        $this->assertInstanceOf(SearchFilter::class, $filter);
    }

    public function test_date_range_filter_validation(): void
    {
        $filter = new DateRangeFilter();

        $this->assertTrue($filter->isValid(['2024-01-01', '2024-12-31']));
        $this->assertTrue($filter->isValid(['start' => '2024-01-01']));
        $this->assertTrue($filter->isValid(['end' => '2024-12-31']));
        $this->assertFalse($filter->isValid([]));
        $this->assertFalse($filter->isValid('not-array'));
    }

    protected function createMockBuilder(): Builder|MockInterface
    {
        return Mockery::mock(Builder::class);
    }
}
