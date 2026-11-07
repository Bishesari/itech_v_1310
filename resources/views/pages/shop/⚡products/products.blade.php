<div>
    <div class="container mx-auto px-4 py-10 lg:py-14">

        {{-- Breadcrumb --}}
        <div class="mb-8">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('home') }}">
                    خانه
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item href="{{ route('shop.index') }}">
                    فروشگاه
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item>
                    {{ $category['title'] }}
                </flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        {{-- Header --}}
        <div class="relative mb-10 overflow-hidden rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 sm:p-10">
            <div class="absolute -left-16 -top-16 h-48 w-48 rounded-full bg-indigo-500/10 blur-3xl"></div>

            <div class="relative">
                <flux:badge color="indigo" size="lg">
                    فروشگاه آی‌تک
                </flux:badge>

                <flux:heading size="2xl" class="mt-4">
                    {{ $category['title'] }}
                </flux:heading>

                <flux:text class="mt-3 max-w-2xl text-zinc-600 dark:text-zinc-400">
                    محصولات این دسته را مشاهده کنید و متناسب با نیاز خود، محصول موردنظرتان را انتخاب کنید.
                </flux:text>

                <div class="mt-5 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="squares-2x2" variant="mini" />
                    <span>{{ $products->count() }} محصول</span>
                </div>
            </div>
        </div>

        {{-- Products --}}
        @if ($products->isNotEmpty())
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">

                @foreach ($products as $product)
                    @php
                        $hasAccess = in_array(
                            $product->id,
                            $accessedProductIds
                        );

                        $price = $product->prices->sortByDesc('starts_at')->first();
                    @endphp

                    <div wire:key="product-{{ $product->id }}"
                         class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-500/5 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-indigo-500">

                        <div class="relative flex h-44 items-center justify-center overflow-hidden bg-gradient-to-br from-indigo-500/10 via-violet-500/5 to-transparent">
                            <div class="absolute inset-0 opacity-0 transition duration-300 group-hover:opacity-100">
                                <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-500/10 blur-2xl"></div>
                            </div>

                            <div class="relative flex h-20 w-20 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-800 dark:ring-zinc-700">
                                @if ($category['value'] === \App\Enums\ProductType::MOCK_EXAM->value)
                                    <flux:icon name="clipboard-document-check" class="size-10 text-indigo-500" />
                                @else
                                    <flux:icon name="document-text" class="size-10 text-indigo-500" />
                                @endif
                            </div>

                            @if ($hasAccess)
                                <div class="absolute right-4 top-4">
                                    <flux:badge color="green">
                                        <flux:icon name="check-circle" variant="mini" />
                                        خریداری شده
                                    </flux:badge>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col p-5 sm:p-6">

                            <div class="mb-3">
                                <flux:heading size="lg" class="line-clamp-2">
                                    {{ $product->title }}
                                </flux:heading>
                            </div>

                            @if ($product->short_description)
                                <flux:text class="line-clamp-3 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $product->short_description }}
                                </flux:text>
                            @else
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    برای مشاهده جزئیات این محصول وارد صفحه آن شوید.
                                </flux:text>
                            @endif

                            <div class="mt-auto pt-6">
                                <div class="mb-5 flex items-center justify-between gap-3">
                                    @if ($hasAccess)
                                        <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                                            دارای دسترسی
                                        </flux:text>
                                    @elseif ($price)
                                        <div>
                                            <flux:text class="text-xs text-zinc-500">
                                                قیمت محصول
                                            </flux:text>

                                            <div class="mt-1 flex items-baseline gap-1">
                                                <span class="text-xl font-bold text-zinc-900 dark:text-white">
                                                    {{ number_format($price->amount) }}
                                                </span>
                                                <span class="text-xs text-zinc-500">
                                                    تومان
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <flux:badge color="zinc">
                                            بدون قیمت فعال
                                        </flux:badge>
                                    @endif
                                </div>

                                <flux:button
                                    href="{{ route('shop.product', ['product' => $product->slug]) }}"
                                    variant="{{ $hasAccess ? 'filled' : 'outline' }}"
                                    color="{{ $hasAccess ? 'green' : 'indigo' }}"
                                    class="w-full"
                                    icon-trailing="arrow-left"
                                >
                                    {{ $hasAccess ? 'مشاهده محصول' : 'مشاهده جزئیات' }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        @else
            {{-- Empty State --}}
            <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="inbox" class="size-8 text-zinc-400" />
                </div>

                <flux:heading size="lg" class="mt-5">
                    محصولی در این دسته وجود ندارد
                </flux:heading>

                <flux:text class="mx-auto mt-2 max-w-md text-zinc-500 dark:text-zinc-400">
                    در حال حاضر محصول فعالی برای نمایش در این دسته‌بندی ثبت نشده است.
                </flux:text>

                <div class="mt-6">
                    <flux:button href="{{ route('shop.index') }}" variant="outline" icon="arrow-right">
                        بازگشت به فروشگاه
                    </flux:button>
                </div>
            </div>
        @endif

    </div>
</div>
