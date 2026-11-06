<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReceivableStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccess;
use App\Models\Receivable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class OrderFulfillmentService
{
    public function fulfill(Receivable $receivable): void
    {
        DB::transaction(function () use ($receivable): void {

            $receivable = Receivable::query()
                ->lockForUpdate()
                ->findOrFail($receivable->id);

            // فقط بدهی تسویه‌شده قابل تکمیل است.
            if ($receivable->status !== ReceivableStatus::PAID) {
                return;
            }

            // دریافت منبع بدهی.
            $source = $receivable->source;

            // این سرویس فقط مسئول تکمیل سفارش است.
            if (! $source instanceof Order) {
                return;
            }

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($source->id);

            // کنترل مالکیت سفارش و بدهی.
            if ((int) $order->user_id !== (int) $receivable->user_id) {
                throw new DomainException(
                    'مالک سفارش با مالک بدهی مطابقت ندارد.'
                );
            }

            // سفارش لغوشده یا منقضی‌شده نباید احیا شود.
            if (in_array($order->status, [
                OrderStatus::CANCELLED,
                OrderStatus::EXPIRED,
            ], true)) {
                throw new DomainException(
                    'امکان تکمیل سفارش لغوشده یا منقضی‌شده وجود ندارد.'
                );
            }

            // سفارش در انتظار پرداخت، پرداخت‌شده می‌شود.
            if ($order->status === OrderStatus::PENDING) {
                $order->update([
                    'status' => OrderStatus::PAID,
                ]);
            }

            // دریافت اقلام سفارش با قفل رکوردها.
            $items = OrderItem::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new DomainException(
                    'سفارش فاقد اقلام قابل تکمیل است.'
                );
            }

            foreach ($items as $item) {
                $access = ProductAccess::query()->createOrFirst(
                    [
                        'order_item_id' => $item->id,
                    ],
                    [
                        'user_id' => $order->user_id,
                        'product_id' => $item->product_id,
                        'granted_at' => now(),
                    ],
                );

                // اطمینان از سازگاری دسترسی موجود با قلم سفارش.
                if (
                    (int) $access->user_id !== (int) $order->user_id ||
                    (int) $access->product_id !== (int) $item->product_id
                ) {
                    throw new DomainException(
                        'دسترسی موجود با اطلاعات قلم سفارش مطابقت ندارد.'
                    );
                }
            }
        });
    }
}
