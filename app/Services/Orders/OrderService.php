<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ReceivableStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAccess;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OrderService
{
    public function createForProduct(User $user, int $productId): Order
    {
        return DB::transaction(function () use ($user, $productId) {
            $product = Product::query()
                ->whereKey($productId)
                ->where('is_active', true)
                ->where('is_publicly_listed', true)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'product' => 'این محصول در حال حاضر قابل خرید نیست.',
                ]);
            }

            $hasAccess = ProductAccess::query()
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();

            if ($hasAccess) {
                throw ValidationException::withMessages([
                    'product' => 'شما قبلاً به این محصول دسترسی دارید.',
                ]);
            }

            $now = now();

            $prices = $product->prices()
                ->where(function ($query) use ($now) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', $now);
                })
                ->orderByDesc('starts_at')
                ->lockForUpdate()
                ->get();

            if ($prices->count() !== 1) {
                throw ValidationException::withMessages([
                    'product' => $prices->isEmpty()
                        ? 'برای این محصول قیمت معتبر ثبت نشده است.'
                        : 'برای این محصول چند قیمت معتبر وجود دارد؛ تنظیم قیمت‌ها باید بررسی شود.',
                ]);
            }

            $amount = (int) $prices->first()->amount;

            if ($amount < 1) {
                throw ValidationException::withMessages([
                    'product' => 'قیمت محصول معتبر نیست.',
                ]);
            }

            $metadata = [];

            if ($product->type === ProductType::WRITTEN_FREQUENT_QUESTIONS) {
                $standard = $product->standard;

                if (! $standard || ! $standard->is_active) {
                    throw ValidationException::withMessages([
                        'product' => 'استاندارد متصل به این محصول فعال یا معتبر نیست.',
                    ]);
                }

                $metadata = [
                    'standard_id' => $standard->id,
                    'standard_code' => $standard->code,
                    'standard_name' => $standard->name_fa,
                ];
            }

            if ($product->type === ProductType::MOCK_EXAM) {
                if (! $product->exam()->exists()) {
                    throw ValidationException::withMessages([
                        'product' => 'آزمون مرتبط با این محصول تعریف نشده است.',
                    ]);
                }
            }

            $existingOrders = Order::query()
                ->where('user_id', $user->id)
                ->where('status', OrderStatus::PENDING)
                ->whereHas('items', fn ($query) =>
                $query->where('product_id', $product->id)
                )
                ->with(['items', 'receivable'])
                ->lockForUpdate()
                ->get()
                ->filter(fn (Order $order) =>
                    $order->items->count() === 1
                    && (int) $order->items->first()->product_id === (int) $product->id
                )
                ->values();

            if ($existingOrders->count() > 1) {
                throw ValidationException::withMessages([
                    'order' => 'چند سفارش پرداخت‌نشده برای این محصول وجود دارد؛ ابتدا باید بررسی شوند.',
                ]);
            }

            if ($existingOrders->isNotEmpty()) {
                $order = $existingOrders->first();
                $receivable = $order->receivable;

                if (
                    ! $receivable
                    || $receivable->status !== ReceivableStatus::UNPAID
                    || (int) $receivable->amount !== $amount
                    || (int) $order->total_amount !== $amount
                    || (int) $order->items->first()->unit_amount !== $amount
                ) {
                    throw ValidationException::withMessages([
                        'order' => 'اطلاعات سفارش یا مطالبه قبلی با قیمت فعلی محصول مطابقت ندارد؛ ابتدا باید بررسی شود.',
                    ]);
                }

                return $order;
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => 'ORD-'.Str::ulid(),
                'status' => OrderStatus::PENDING,
                'total_amount' => $amount,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_type' => $product->type->value,
                'unit_amount' => $amount,
                'metadata' => $metadata ?: null,
            ]);

            $order->receivable()->create([
                'user_id' => $user->id,
                'title' => 'خرید محصول: '.$product->title,
                'amount' => $amount,
                'status' => ReceivableStatus::UNPAID,
                'created_by' => $user->id,
            ]);

            return $order->load(['items', 'receivable']);
        }, attempts: 3);
    }
}
