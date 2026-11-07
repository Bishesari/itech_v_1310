<div>
    <div class="container mx-auto space-y-8 px-4 py-10 lg:py-14">

        {{-- Breadcrumb --}}
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('home') }}">
                خانه
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item href="{{ route('shop.index') }}">
                فروشگاه
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item href="{{ route('shop.products', ['type' => $product->type->value]) }}">
                {{ $product->type->label() }}
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>
                {{ $product->title }}
            </flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Product Header --}}
        <div class="relative overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

            <div class="absolute -left-24 -top-24 size-80 rounded-full bg-indigo-500/10 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-20 size-72 rounded-full bg-violet-500/10 blur-3xl"></div>

            <div class="relative grid gap-8 p-6 sm:p-10 lg:grid-cols-3 lg:gap-12">

                <div class="flex flex-col justify-center lg:col-span-2">

                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge color="indigo">
                            {{ $product->type->label() }}
                        </flux:badge>

                        @if ($hasAccess)
                            <flux:badge color="green">
                                <flux:icon name="check-circle" variant="mini" />
                                خریداری شده
                            </flux:badge>
                        @endif
                    </div>

                    <flux:heading size="2xl" class="mt-5">
                        {{ $product->title }}
                    </flux:heading>

                    @if ($product->short_description)
                        <flux:text class="mt-4 text-base leading-8 text-zinc-600 dark:text-zinc-400">
                            {{ $product->short_description }}
                        </flux:text>
                    @endif

                    @if ($product->standard)
                        <div class="mt-6 flex flex-wrap items-center gap-3 text-sm">
                            <div class="flex items-center gap-2 rounded-xl bg-zinc-100 px-3 py-2 dark:bg-zinc-800">
                                <flux:icon name="academic-cap" class="size-4 text-indigo-500" />
                                <span>{{ $product->standard->name_fa }}</span>
                            </div>

                            @if ($product->standard->field)
                                <div class="flex items-center gap-2 rounded-xl bg-zinc-100 px-3 py-2 dark:bg-zinc-800">
                                    <flux:icon name="book-open" class="size-4 text-indigo-500" />
                                    <span>{{ $product->standard->field->name }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>

                {{-- Purchase Card --}}
                <div class="relative rounded-2xl border border-zinc-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-zinc-700 dark:bg-zinc-800/90">

                    @if ($hasAccess)
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                            <flux:icon name="check-circle" variant="solid" class="size-6" />
                            <flux:heading size="lg">
                                شما به این محصول دسترسی دارید
                            </flux:heading>
                        </div>

                        <flux:text class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-400">
                            این محصول قبلاً خریداری شده است و می‌توانید از محتوای آن استفاده کنید.
                        </flux:text>

                    @else
                        <flux:heading size="lg">
                            خرید محصول
                        </flux:heading>

                        @if ($price)
                            <div class="mt-5">
                                <flux:text class="text-sm text-zinc-500">
                                    قیمت قابل پرداخت
                                </flux:text>

                                <div class="mt-2 flex items-baseline gap-2">
                                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                        {{ number_format($price->amount) }}
                                    </span>

                                    <span class="text-sm text-zinc-500">
                                        تومان
                                    </span>
                                </div>
                            </div>


                            <flux:button
                                class="mt-6 w-full"
                                variant="primary"
                                color="indigo"
                                icon="shopping-bag"
                                wire:click="purchase"
                                wire:loading.attr="disabled"
                                wire:target="purchase"
                            >

                                <span wire:loading.remove wire:target="purchase">
                                    خرید محصول
                                </span>

                                <span wire:loading wire:target="purchase">
                                    در حال انتقال به درگاه...
                                </span>
                            </flux:button>

                            <div
                                wire:loading
                                wire:target="purchase"
                                class="mt-3 text-center text-xs text-zinc-500"
                            >
                                لطفاً تا انتقال به درگاه پرداخت صبر کنید.
                            </div>

                        @else
                            <div class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                                در حال حاضر قیمت فعالی برای این محصول ثبت نشده است.
                            </div>
                        @endif
                    @endif

                    <flux:separator class="my-5" />

                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <div class="flex items-center gap-2">
                            <flux:icon name="shield-check" class="size-4 text-green-500" />
                            دسترسی اختصاصی پس از خرید
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:icon name="academic-cap" class="size-4 text-indigo-500" />
                            محتوای تخصصی و آموزشی
                        </div>
                    </div>

                </div>
            </div>
        </div>


        @if ($errors->has('product'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10">
                <div class="flex items-start gap-3">
                    <flux:icon
                        name="exclamation-circle"
                        class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400"
                    />

                    <div class="text-sm leading-7 text-red-700 dark:text-red-300">
                        {{ $errors->first('product') }}
                    </div>
                </div>
            </div>
        @endif


        {{-- Product Description --}}
        @if ($product->description)
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 sm:p-8">
                <flux:heading size="lg">
                    درباره محصول
                </flux:heading>

                <div class="prose prose-zinc mt-5 max-w-none leading-8 dark:prose-invert">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>
        @endif

        {{-- Product Details --}}
        @if ($details['type'] === 'written_questions')

            <div class="space-y-6">

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <flux:heading size="xl">
                            سؤالات پرتکرار
                        </flux:heading>

                        <flux:text class="mt-2">
                            مجموعه سؤالات پرتکرار آزمون‌های کتبی این استاندارد
                        </flux:text>
                    </div>

                    <flux:badge color="indigo" size="lg">
                        {{ number_format($details['question_count']) }} سؤال
                    </flux:badge>
                </div>

                @if ($hasAccess)

                    @forelse ($details['questions'] as $index => $question)
                        <div wire:key="question-{{ $question->id }}"
                             class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 sm:p-7">

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <flux:heading size="md">
                                    سؤال {{ number_format($index + 1) }}
                                </flux:heading>

                                <flux:badge color="zinc">
                                    {{ $question->difficulty?->label() ?? 'بدون سطح دشواری' }}
                                </flux:badge>
                            </div>

                            <div class="mt-5 whitespace-pre-line leading-8 text-zinc-800 dark:text-zinc-200">
                                {{ $question->text }}
                            </div>

                            @if ($question->image_path)
                                <div class="mt-5">
                                    <img
                                        src="{{ asset('storage/' . $question->image_path) }}"
                                        alt="تصویر سؤال {{ $index + 1 }}"
                                        class="max-h-96 max-w-full rounded-xl object-contain"
                                    >
                                </div>
                            @endif

                            @if ($question->code)
                                <pre dir="ltr" class="mt-5 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-sm text-zinc-100"><code>{{ $question->code }}</code></pre>
                            @endif

                            @if ($question->options->isNotEmpty())
                                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                    @foreach ($question->options as $optionIndex => $option)
                                        <div wire:key="option-{{ $option->id }}"
                                             class="flex items-start gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                                            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-xs font-semibold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                                {{ chr(65 + $optionIndex) }}
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                @if ($option->text)
                                                    <p dir="{{ $option->dir }}" class="leading-7">
                                                        {{ $option->text }}
                                                    </p>
                                                @endif

                                                @if ($option->image_path)
                                                    <img
                                                        src="{{ asset('storage/' . $option->image_path) }}"
                                                        alt="تصویر گزینه"
                                                        class="mt-2 max-h-40 max-w-full rounded-lg object-contain"
                                                    >
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                            <flux:icon name="inbox" class="mx-auto size-10 text-zinc-400" />

                            <flux:heading size="lg" class="mt-4">
                                سؤالی برای نمایش وجود ندارد
                            </flux:heading>
                        </div>
                    @endforelse

                @else

                    <div class="rounded-3xl border border-indigo-200 bg-indigo-50/70 p-8 text-center dark:border-indigo-500/20 dark:bg-indigo-500/5 sm:p-12">

                        <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-500/10">
                            <flux:icon name="lock-closed" class="size-8 text-indigo-600 dark:text-indigo-400" />
                        </div>

                        <flux:heading size="xl" class="mt-5">
                            محتوای سؤالات اختصاصی است
                        </flux:heading>

                        <flux:text class="mx-auto mt-3 max-w-xl leading-8">
                            برای مشاهده مجموعه سؤالات پرتکرار این استاندارد، ابتدا محصول را خریداری کنید.
                        </flux:text>

                        @if ($price)
                            <div class="mt-6">
                                <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                                    {{ number_format($price->amount) }}
                                </span>

                                <span class="text-sm text-zinc-500">
                                    تومان
                                </span>
                            </div>
                        @endif

                    </div>

                @endif

            </div>

        @elseif ($details['type'] === 'mock_exam')

            <div class="space-y-6">

                <div>
                    <flux:heading size="xl">
                        مشخصات آزمون آزمایشی
                    </flux:heading>

                    <flux:text class="mt-2">
                        اطلاعات و شرایط شرکت در آزمون
                    </flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:icon name="document-text" class="size-7 text-indigo-500" />

                        <flux:text class="mt-4 text-sm text-zinc-500">
                            تعداد سؤالات
                        </flux:text>

                        <div class="mt-2 text-2xl font-bold">
                            {{ number_format($details['exam']->question_count) }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:icon name="clock" class="size-7 text-indigo-500" />

                        <flux:text class="mt-4 text-sm text-zinc-500">
                            مدت زمان
                        </flux:text>

                        <div class="mt-2 text-2xl font-bold">
                            {{ number_format($details['exam']->duration_minutes) }}
                            <span class="text-sm font-normal text-zinc-500">دقیقه</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:icon name="chart-bar" class="size-7 text-indigo-500" />

                        <flux:text class="mt-4 text-sm text-zinc-500">
                            نمره قبولی
                        </flux:text>

                        <div class="mt-2 text-2xl font-bold">
                            {{ number_format((float) $details['exam']->passing_score, 2) }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:icon name="clipboard-check" class="size-7 text-indigo-500" />

                        <flux:text class="mt-4 text-sm text-zinc-500">
                            وضعیت آزمون
                        </flux:text>

                        <div class="mt-2 text-base font-semibold">
                            @if ($details['exam']->available_from && now()->lt($details['exam']->available_from))
                                به‌زودی
                            @elseif ($details['exam']->available_until && now()->gt($details['exam']->available_until))
                                پایان‌یافته
                            @else
                                قابل برگزاری
                            @endif
                        </div>
                    </div>

                </div>

                @if ($details['exam']->description)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading size="lg">
                            توضیحات آزمون
                        </flux:heading>

                        <div class="mt-4 whitespace-pre-line leading-8 text-zinc-700 dark:text-zinc-300">
                            {{ $details['exam']->description }}
                        </div>
                    </div>
                @endif

                @php
                    $attempt = $details['attempt'];

                    $examAvailable =
                        (! $details['exam']->available_from || now()->gte($details['exam']->available_from))
                        && (! $details['exam']->available_until || now()->lte($details['exam']->available_until));
                @endphp

                @if (! $hasAccess)

                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-6 dark:border-indigo-500/20 dark:bg-indigo-500/5">
                        <div class="flex items-center gap-3">
                            <flux:icon name="lock-closed" class="size-6 text-indigo-600 dark:text-indigo-400" />

                            <flux:heading size="lg">
                                برای شرکت در آزمون، ابتدا محصول را خریداری کنید.
                            </flux:heading>
                        </div>
                    </div>

                @elseif (! $attempt)

                    <div class="rounded-2xl border border-green-200 bg-green-50 p-6 dark:border-green-500/20 dark:bg-green-500/5">
                        <div class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-1 size-7 text-green-600 dark:text-green-400" />

                            <div class="min-w-0 flex-1">
                                <flux:heading size="lg">
                                    آزمون برای شما فعال است
                                </flux:heading>

                                <flux:text class="mt-2 leading-7">
                                    دسترسی این آزمون برای شما ثبت شده و هنوز در آن شرکت نکرده‌اید.
                                </flux:text>

                                @if ($examAvailable)
                                    <flux:button
                                        href="{{ route('exam.index') }}"
                                        class="mt-5"
                                        color="green"
                                        icon="play"
                                    >
                                        شروع آزمون
                                    </flux:button>
                                @else
                                    <div class="mt-4 rounded-xl bg-white/70 p-3 text-sm text-zinc-600 dark:bg-zinc-900/50 dark:text-zinc-400">
                                        در حال حاضر امکان شروع آزمون وجود ندارد.
                                        @if ($details['exam']->available_from && now()->lt($details['exam']->available_from))
                                            آزمون از زمان تعیین‌شده در دسترس خواهد بود.
                                        @else
                                            مهلت برگزاری آزمون به پایان رسیده است.
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                @elseif ($attempt->status->value === 'in_progress')

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-500/20 dark:bg-amber-500/5">
                        <div class="flex items-start gap-3">
                            <flux:icon name="clock" class="mt-1 size-7 text-amber-600 dark:text-amber-400" />

                            <div class="min-w-0 flex-1">
                                <flux:heading size="lg">
                                    آزمون شما در حال انجام است
                                </flux:heading>

                                <flux:text class="mt-2 leading-7">
                                    شما قبلاً این آزمون را شروع کرده‌اید و می‌توانید ادامه آن را انجام دهید.
                                </flux:text>

                                <div class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    <div>
                                        زمان شروع:
                                        {{ $attempt->started_at?->format('Y/m/d H:i') }}
                                    </div>

                                    <div>
                                        مهلت پایان:
                                        {{ $attempt->deadline_at?->format('Y/m/d H:i') }}
                                    </div>
                                </div>

                                <flux:button
                                    href="{{ route('exam.attempts.show', ['attempt' => $attempt]) }}"
                                    class="mt-5"
                                    color="amber"
                                    icon="play"
                                >
                                    ادامه آزمون
                                </flux:button>
                            </div>
                        </div>
                    </div>

                @elseif ($attempt->status->value === 'submitted')

                    <div class="rounded-2xl border border-green-200 bg-green-50 p-6 dark:border-green-500/20 dark:bg-green-500/5">
                        <div class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-1 size-7 text-green-600 dark:text-green-400" />

                            <div class="min-w-0 flex-1">
                                <flux:heading size="lg">
                                    آزمون را به پایان رسانده‌اید
                                </flux:heading>

                                <flux:text class="mt-2 leading-7">
                                    پاسخ‌های شما ثبت شده است. برای مشاهده نتیجه و جزئیات عملکرد خود اقدام کنید.
                                </flux:text>

                                @if ($attempt->score !== null)
                                    <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                                        نمره شما:
                                        <span class="font-bold text-zinc-900 dark:text-white">
                            {{ number_format((float) $attempt->score, 2) }}
                        </span>
                                    </div>
                                @endif

                                <flux:button
                                    href="{{ route('exam.results.show', ['attempt' => $attempt]) }}"
                                    class="mt-5"
                                    color="green"
                                    icon="chart-bar"
                                >
                                    مشاهده نتیجه آزمون
                                </flux:button>
                            </div>
                        </div>
                    </div>

                @endif
            </div>


        {{-- Back to Shop --}}
        <div class="flex justify-center border-t border-zinc-200 pt-8 dark:border-zinc-700">
            <flux:button
                href="{{ route('shop.products', ['type' => $product->type->value]) }}"
                variant="ghost"
                icon="arrow-right"
            >
                بازگشت به محصولات این دسته
            </flux:button>
        </div>

    </div>
</div>
