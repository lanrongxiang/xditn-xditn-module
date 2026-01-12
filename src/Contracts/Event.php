<?php

declare(strict_types=1);

namespace XditnModule\Contracts;

/**
 * 统一事件接口.
 *
 * 所有事件类都应该实现此接口，以确保事件的一致性和可预测性。
 *
 * 使用示例：
 * ```php
 * class OrderCreatedEvent implements Event
 * {
 *     use EventTrait;
 *
 *     public function __construct(
 *         public readonly Order $order
 *     ) {}
 *
 *     public function getData(): array
 *     {
 *         return [
 *             'order_id' => $this->order->id,
 *             'amount' => $this->order->amount,
 *         ];
 *     }
 *
 *     public function shouldBroadcast(): bool
 *     {
 *         return true;
 *     }
 *
 *     public function broadcastChannel(): string
 *     {
 *         return 'orders.' . $this->order->user_id;
 *     }
 * }
 * ```
 */
interface Event
{
    /**
     * 获取事件数据.
     *
     * 返回事件携带的数据，用于日志记录、序列化等场景。
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * 是否应该广播此事件.
     *
     * 返回 true 时，事件会通过 WebSocket 广播给前端。
     */
    public function shouldBroadcast(): bool;

    /**
     * 是否应该异步处理此事件.
     *
     * 返回 true 时，事件监听器会被放入队列异步处理。
     */
    public function shouldQueue(): bool;

    /**
     * 获取事件名称.
     *
     * 用于日志记录和调试。
     */
    public function getEventName(): string;

    /**
     * 获取事件发生时间.
     */
    public function getOccurredAt(): \DateTimeInterface;
}
