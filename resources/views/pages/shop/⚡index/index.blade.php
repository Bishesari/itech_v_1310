
<div class="space-y-20 pb-12">

    {{-- Hero --}}
    <section class="relative isolate overflow-hidden">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -right-32 -top-32 size-120 rounded-full bg-indigo-500/10 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-32 size-120 rounded-full bg-cyan-500/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-zinc-300 dark:via-zinc-700 to-transparent"></div>
        </div>

        <div class="container mx-auto px-4">
            <div class="grid items-center gap-12 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:py-24">

                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-300">
                        <flux:icon name="shopping-bag" variant="micro" />
                        فروشگاه محصولات آی‌تک
                    </div>

                    <flux:heading
                        level="1"
                        class="mt-7 text-4xl font-bold leading-normal tracking-tight sm:text-5xl lg:text-6xl"
                    >
                        ابزار مناسب برای
                        <span class="text-indigo-600 dark:text-indigo-400">
                            پیشرفت مهارت‌های تو
                        </span>
                    </flux:heading>

                    <flux:text class="mt-6 max-w-xl text-base leading-9 text-zinc-600 dark:text-zinc-400 sm:text-lg">
                        مجموعه‌ای از منابع آموزشی و آزمون‌های آزمایشی برای یادگیری هدفمند،
                        تمرین بیشتر و آمادگی بهتر برای آزمون‌های مهارتی.
                    </flux:text>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <flux:button
                            href="#product-categories"
                            variant="primary"
                            icon="arrow-left"
                        >
                            مشاهده محصولات
                        </flux:button>

                        <flux:button
                            href="{{ route('home') }}"
                            variant="ghost"
                            icon="home"
                        >
                            صفحه اصلی
                        </flux:button>
                    </div>

                    <div class="mt-10 flex flex-wrap gap-x-7 gap-y-4 text-sm text-zinc-500 dark:text-zinc-400">
                        <div class="flex items-center gap-2">
                            <flux:icon name="check-circle" variant="micro" class="text-emerald-500" />
                            خرید آسان
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:icon name="shield-check" variant="micro" class="text-emerald-500" />
                            دسترسی به محصولات خریداری‌شده
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:icon name="clock" variant="micro" class="text-emerald-500" />
                            دسترسی آنلاین
                        </div>
                    </div>
                </div>

                {{-- Visual --}}
                <div class="relative mx-auto hidden w-full max-w-lg lg:block">
                    <div class="absolute inset-8 rounded-[3rem] bg-linear-to-br from-indigo-500/10 via-cyan-500/10 to-transparent blur-2xl"></div>

                    <div class="relative rounded-4xl border border-zinc-200 bg-white/80 p-7 shadow-2xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-900/80 dark:shadow-black/20">

                        <div class="flex items-center justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
                            <div class="flex items-center gap-3">
                                <div class="flex size-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700 dark:bg-indigo-400/10 dark:text-indigo-300">
                                    <flux:icon name="academic-cap" variant="outline" />
                                </div>

                                <div>
                                    <div class="font-semibold">مرکز یادگیری آی‌تک</div>
                                    <div class="mt-1 text-xs text-zinc-500">مسیر پیشرفت تو</div>
                                </div>
                            </div>

                            <flux:badge color="success" size="sm">
                                آنلاین
                            </flux:badge>
                        </div>

                        <div class="space-y-4 py-7">
                            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 dark:bg-cyan-400/10 dark:text-cyan-300">
                                        <flux:icon name="document-text" variant="outline" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold">نمونه سؤالات پرتکرار</div>
                                        <div class="mt-1 text-xs text-zinc-500">تمرین و مرور سؤالات مهم</div>
                                    </div>

                                    <flux:icon name="check-circle" class="text-emerald-500" />
                                </div>
                            </div>

                            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-400/10 dark:text-violet-300">
                                        <flux:icon name="clipboard-document-check" variant="outline" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold">آزمون‌های آزمایشی</div>
                                        <div class="mt-1 text-xs text-zinc-500">ارزیابی آمادگی و مهارت</div>
                                    </div>

                                    <flux:icon name="check-circle" class="text-emerald-500" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-zinc-950 p-5 text-white dark:bg-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm text-zinc-400">قدم بعدی تو</div>
                                    <div class="mt-2 text-lg font-bold">با آمادگی بیشتر شروع کن</div>
                                </div>

                                <div class="flex size-12 items-center justify-center rounded-2xl bg-white/10">
                                    <flux:icon name="rocket-launch" variant="outline" class="size-6" />
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="absolute -bottom-6 -right-5 flex size-16 items-center justify-center rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon name="sparkles" class="size-7 text-indigo-500" />
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Categories --}}
    <section id="product-categories" class="border-y border-zinc-200 bg-zinc-50/80 dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="container mx-auto px-4 py-16 sm:py-20">

            <div class="mx-auto max-w-2xl text-center">
                <flux:badge color="info">
                    دسته‌بندی محصولات
                </flux:badge>

                <flux:heading level="2" size="xl" class="mt-4">
                    چه چیزی نیاز داری؟
                </flux:heading>

                <flux:text class="mt-4 leading-8 text-zinc-600 dark:text-zinc-400">
                    از میان محصولات آی‌تک، گزینه مناسب خودت را انتخاب کن و
                    مسیر یادگیری و آمادگی را ادامه بده.
                </flux:text>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2">

                @foreach ($categories as $category)
                    <a
                        href="{{ route('shop.products', ['type' => $category['value']]) }}"
                        wire:key="shop-category-{{ $category['value'] }}"
                        class="group relative overflow-hidden rounded-3xl border border-zinc-200 bg-white p-7 transition duration-300 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-500/5 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-indigo-400/40"
                    >
                        <div aria-hidden="true" class="pointer-events-none absolute -left-16 -top-16 size-48 rounded-full bg-indigo-500/5 blur-3xl transition duration-300 group-hover:bg-indigo-500/10"></div>

                        <div class="relative">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-700 transition duration-300 group-hover:bg-indigo-600 group-hover:text-white dark:bg-zinc-800 dark:text-zinc-200 dark:group-hover:bg-indigo-500">
                                    <flux:icon :name="$category['icon']" variant="outline" class="size-7" />
                                </div>

                                <flux:badge color="zinc">
                                    {{ number_format($category['count']) }} محصول
                                </flux:badge>
                            </div>

                            <flux:heading size="lg" class="mt-7">
                                {{ $category['title'] }}
                            </flux:heading>

                            <flux:text class="mt-3 min-h-14 leading-8 text-zinc-500 dark:text-zinc-400">
                                {{ $category['description'] }}
                            </flux:text>

                            <div class="mt-7 flex items-center justify-between border-t border-zinc-100 pt-5 dark:border-zinc-800">
                                <span class="text-sm font-semibold text-indigo-600 transition group-hover:text-indigo-700 dark:text-indigo-400 dark:group-hover:text-indigo-300">
                                    مشاهده محصولات
                                </span>

                                <span class="flex size-9 items-center justify-center rounded-full bg-zinc-100 transition duration-300 group-hover:bg-indigo-600 group-hover:text-white dark:bg-zinc-800 dark:group-hover:bg-indigo-500">
                                    <flux:icon name="arrow-left" variant="micro" />
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach

            </div>

        </div>
    </section>

    {{-- Benefits --}}
    <section>
        <div class="container mx-auto px-4">
            <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">

                <div>
                    <flux:text class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        تجربه خرید ساده
                    </flux:text>

                    <flux:heading level="2" size="xl" class="mt-3 leading-relaxed">
                        از انتخاب محصول تا شروع یادگیری، بدون پیچیدگی
                    </flux:heading>

                    <flux:text class="mt-5 leading-8 text-zinc-600 dark:text-zinc-400">
                        محصول موردنظرت را انتخاب کن، جزئیات آن را بررسی کن و پس از خرید،
                        به محتوای آن دسترسی داشته باش.
                    </flux:text>

                    <div class="mt-7">
                        <flux:button href="#product-categories" variant="primary" icon="arrow-left">
                            انتخاب محصول
                        </flux:button>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800">
                        <div class="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400">
                            <flux:icon name="magnifying-glass" variant="outline" />
                        </div>

                        <flux:heading size="sm" class="mt-5">
                            انتخاب آسان
                        </flux:heading>

                        <flux:text class="mt-3 text-sm leading-7 text-zinc-500 dark:text-zinc-400">
                            مشاهده و مقایسه محصولات و انتخاب گزینه مناسب.
                        </flux:text>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800">
                        <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                            <flux:icon name="shield-check" variant="outline" />
                        </div>

                        <flux:heading size="sm" class="mt-5">
                            خرید مطمئن
                        </flux:heading>

                        <flux:text class="mt-3 text-sm leading-7 text-zinc-500 dark:text-zinc-400">
                            پرداخت آنلاین و ثبت دسترسی محصول خریداری‌شده.
                        </flux:text>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800">
                        <div class="flex size-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400">
                            <flux:icon name="academic-cap" variant="outline" />
                        </div>

                        <flux:heading size="sm" class="mt-5">
                            شروع استفاده
                        </flux:heading>

                        <flux:text class="mt-3 text-sm leading-7 text-zinc-500 dark:text-zinc-400">
                            دسترسی به محتوای محصول پس از تکمیل خرید.
                        </flux:text>
                    </div>
                </div>

            </div>
        </div>
    </section>

</div>
