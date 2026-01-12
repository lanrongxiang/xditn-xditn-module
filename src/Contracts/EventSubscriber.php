<?php

declare(strict_types=1);

namespace XditnModule\Contracts;

use Illuminate\Events\Dispatcher;

/**
 * 事件订阅者接口.
 *
 * 事件订阅者可以在一个类中订阅多个事件，
 * 这样可以将相关的事件监听逻辑组织在一起。
 *
 * 使用示例：
 * ```php
 * class OrderEventSubscriber implements EventSubscriber
 * {
 *     public function subscribe(Dispatcher $events): array
 *     {
 *         return [
 *             OrderCreatedEvent::class => 'handleOrderCreated',
 *             OrderPaidEvent::class => 'handleOrderPaid',
 *             OrderCancelledEvent::class => 'handleOrderCancelled',
 *         ];
 *     }
 *
 *     public function handleOrderCreated(OrderCreatedEvent $event): void
 *     {
 *         // 处理订单创建事件
 *     }
 *
 *     public function handleOrderPaid(OrderPaidEvent $event): void
 *     {
 *         // 处理订单支付事件
 *     }
 *
 *     public function handleOrderCancelled(OrderCancelledEvent $event): void
 *     {
 *         // 处理订单取消事件
 *     }
 * }
 * ```
 *
 * 注册订阅者（在 ServiceProvider 中）：
 * ```php
 * Event::subscribe(OrderEventSubscriber::class);
 * ```
 */
interface EventSubscriber
{
    /**
     * 注册事件订阅.
     *
     * 返回一个关联数组，键为事件类名，值为处理方法名。
     *
     * @return array<class-string, string|array<string>>
     */
    public function subscribe(Dispatcher $events): array;
}
