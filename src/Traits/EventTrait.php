<?php

declare(strict_types=1);

namespace XditnModule\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 事件 Trait.
 *
 * 为事件类提供通用的实现，包含 Laravel 事件的标准 Trait
 * 以及 Event 接口的默认实现。
 *
 * 使用示例：
 * ```php
 * class OrderCreatedEvent implements Event
 * {
 *     use EventTrait;
 *
 *     public function __construct(
 *         public readonly Order $order
 *     ) {
 *         $this->initializeEventTrait();
 *     }
 *
 *     public function getData(): array
 *     {
 *         return ['order_id' => $this->order->id];
 *     }
 * }
 * ```
 */
trait EventTrait
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * 事件发生时间.
     */
    protected DateTimeInterface $occurredAt;

    /**
     * 是否应该广播.
     */
    protected bool $shouldBroadcast = false;

    /**
     * 是否应该异步处理.
     */
    protected bool $shouldQueue = false;

    /**
     * 初始化事件 Trait.
     *
     * 在构造函数中调用此方法。
     */
    protected function initializeEventTrait(): void
    {
        $this->occurredAt = Carbon::now();
    }

    /**
     * 是否应该广播此事件.
     */
    public function shouldBroadcast(): bool
    {
        return $this->shouldBroadcast;
    }

    /**
     * 设置是否广播.
     *
     * @return $this
     */
    public function setBroadcast(bool $broadcast): static
    {
        $this->shouldBroadcast = $broadcast;

        return $this;
    }

    /**
     * 是否应该异步处理此事件.
     */
    public function shouldQueue(): bool
    {
        return $this->shouldQueue;
    }

    /**
     * 设置是否异步处理.
     *
     * @return $this
     */
    public function setQueue(bool $queue): static
    {
        $this->shouldQueue = $queue;

        return $this;
    }

    /**
     * 获取事件名称.
     */
    public function getEventName(): string
    {
        return class_basename(static::class);
    }

    /**
     * 获取事件发生时间.
     */
    public function getOccurredAt(): DateTimeInterface
    {
        return $this->occurredAt ?? Carbon::now();
    }

    /**
     * 获取事件数据（默认实现，子类应该重写）.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [];
    }

    /**
     * 转换为数组（用于日志记录）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'occurred_at' => $this->getOccurredAt()->format('Y-m-d H:i:s'),
            'should_broadcast' => $this->shouldBroadcast(),
            'should_queue' => $this->shouldQueue(),
            'data' => $this->getData(),
        ];
    }
}
