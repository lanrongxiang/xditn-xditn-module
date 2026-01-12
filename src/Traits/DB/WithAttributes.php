<?php

declare(strict_types=1);

namespace XditnModule\Traits\DB;

/**
 * base operate.
 */
trait WithAttributes
{
    /**
     * @var string
     */
    protected string $parentIdColumn = 'parent_id';

    /**
     * @var string
     */
    protected string $sortField = '';

    /**
     * @var bool
     */
    protected bool $sortDesc = true;

    /**
     * as tress which is show in list as tree data.
     */
    protected bool $asTree = false;

    /**
     * @var bool
     */
    protected bool $isPaginate = true;

    /**
     * @var array
     */
    protected array $formRelations = [];

    /**
     * @var bool
     */
    protected bool $dataRange = false;

    /**
     * 字段访问.
     *
     * @var bool
     */
    protected bool $columnAccess = false;

    /**
     * null to empty string.
     */
    protected bool $autoNull2EmptyString = true;

    /**
     * 排序字段 query.
     *
     * @var string
     */
    protected string $dynamicQuerySortField = 'sortField';

    /**
     * query 排序字段顺序.
     *
     * @var string
     */
    protected string $dynamicQuerySortOrder = 'order';

    /**
     * 填充创建人.
     *
     * @var bool
     */
    protected bool $isFillCreatorId = true;

    /**
     * 与父级数据同步更新的字段.
     *
     * @var array|string[]
     */
    protected array $syncParentFields = ['status'];
}
