<div class="space-y-8">

    {{-- Page Header --}}
    <div class="flex items-center justify-between gap-4">

        <div class="space-y-1">
            <flux:heading size="xl">
                آزمون‌های من
            </flux:heading>

            <flux:text>
                آزمون‌های قابل شروع، در حال انجام و نتایج قبلی
            </flux:text>
        </div>

        <flux:button
            wire:click="loadExams"
            variant="outline"
            icon="arrow-path"
        >
            بروزرسانی
        </flux:button>

    </div>

    {{-- Available Exams --}}
    <section class="space-y-4">

        <div class="flex items-center gap-2">
            <flux:heading size="lg">
                آزمون‌های قابل شروع
            </flux:heading>

            <flux:badge color="info">
                {{ count($availableExams) }}
            </flux:badge>
        </div>

        @forelse ($availableExams as $exam)

            <div
                wire:key="available-exam-{{ $exam['id'] }}-{{ $exam['product_access_id'] ?? 'internal' }}"
                class="rounded-xl border p-5 space-y-4"
            >

                <div class="flex items-start justify-between gap-3">

                    <div class="space-y-1">
                        <flux:heading size="md">
                            {{ $exam['title'] }}
                        </flux:heading>

                        <flux:text>
                            @switch($exam['type'])
                                @case('quiz')
                                    آزمونک
                                    @break
                                @case('midterm')
                                    آزمون میان‌ترم
                                    @break
                                @case('final')
                                    آزمون پایان‌ترم
                                    @break
                                @case('mock')
                                    آزمون آزمایشی
                                    @break
                            @endswitch
                        </flux:text>
                    </div>

                    <flux:badge color="info">
                        آماده شروع
                    </flux:badge>

                </div>

                @if ($exam['description'])
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $exam['description'] }}
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">

                    <div>
                        <flux:text size="sm" class="text-zinc-500">
                            تعداد سؤال
                        </flux:text>
                        <div class="font-semibold">
                            {{ $exam['question_count'] }}
                        </div>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-zinc-500">
                            مدت آزمون
                        </flux:text>
                        <div class="font-semibold">
                            {{ $exam['duration_minutes'] }} دقیقه
                        </div>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-zinc-500">
                            آخرین مهلت شروع
                        </flux:text>
                        <div class="font-semibold">
                            {{ $exam['available_until'] }}
                        </div>
                    </div>

                </div>

                <div class="flex justify-end">
                    <flux:button
                        wire:click="startExam(
            {{ $exam['id'] }},
            {{ $exam['product_access_id'] ?? 'null' }}
        )"
                        variant="primary"
                        icon="play"
                    >
                        شروع آزمون
                    </flux:button>
                </div>

            </div>

        @empty

            <div class="rounded-xl border border-dashed p-8 text-center">
                <flux:icon
                    name="clipboard"
                    class="size-10 mx-auto text-zinc-400"
                />

                <flux:text class="mt-3">
                    در حال حاضر آزمون قابل شروعی وجود ندارد.
                </flux:text>
            </div>

        @endforelse

    </section>

    <flux:separator />

    {{-- In Progress Exams --}}
    <section class="space-y-4">

        <div class="flex items-center gap-2">
            <flux:heading size="lg">
                آزمون‌های در حال انجام
            </flux:heading>

            <flux:badge color="warning">
                {{ count($inProgressExams) }}
            </flux:badge>
        </div>

        @forelse ($inProgressExams as $exam)

            <div
                wire:key="progress-exam-{{ $exam['attempt_id'] }}"
                class="rounded-xl border p-5 space-y-4"
            >

                <div class="flex items-start justify-between gap-3">

                    <flux:heading size="md">
                        {{ $exam['title'] }}
                    </flux:heading>

                    <flux:badge color="warning">
                        در حال انجام
                    </flux:badge>

                </div>

                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <flux:text size="sm" class="text-zinc-500">
                            تعداد سؤال
                        </flux:text>
                        <div class="font-semibold">
                            {{ $exam['question_count'] }}
                        </div>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-zinc-500">
                            مدت آزمون
                        </flux:text>
                        <div class="font-semibold">
                            {{ $exam['duration_minutes'] }} دقیقه
                        </div>
                    </div>

                </div>

                <div class="flex justify-end">
                    <flux:button
                        wire:click="continueExam({{ $exam['attempt_id'] }})"
                        variant="primary"
                        icon="play"
                    >
                        ادامه آزمون
                    </flux:button>
                </div>

            </div>

        @empty

            <div class="rounded-xl border border-dashed p-8 text-center">
                <flux:text>
                    هیچ آزمون در حال انجامی ندارید.
                </flux:text>
            </div>

        @endforelse

    </section>

    <flux:separator />

    {{-- Completed Exams --}}
    <section class="space-y-4">

        <div class="flex items-center gap-2">
            <flux:heading size="lg">
                آزمون‌های پایان‌یافته
            </flux:heading>

            <flux:badge color="success">
                {{ count($completedExams) }}
            </flux:badge>
        </div>

        @forelse ($completedExams as $exam)

            <div
                wire:key="completed-exam-{{ $exam['attempt_id'] }}"
                class="rounded-xl border p-5 space-y-4"
            >

                <div class="flex items-start justify-between gap-3">

                    <flux:heading size="md">
                        {{ $exam['title'] }}
                    </flux:heading>

                    <flux:badge color="success">
                        پایان‌یافته
                    </flux:badge>

                </div>

                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <flux:text size="sm" class="text-zinc-500">
                            نمره
                        </flux:text>

                        <div class="text-xl font-bold font-mono">
                            {{ $exam['score'] !== null
                                ? number_format($exam['score'], 2)
                                : '—' }}
                            <span class="text-sm font-normal text-zinc-500">
                                از ۱۰۰
                            </span>
                        </div>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-zinc-500">
                            تعداد سؤال
                        </flux:text>

                        <div class="font-semibold">
                            {{ $exam['question_count'] }}
                        </div>
                    </div>

                </div>

                <div class="flex justify-end">
                    <flux:button
                        wire:click="viewResult({{ $exam['attempt_id'] }})"
                        variant="outline"
                        icon="check"
                    >
                        مشاهده نتیجه
                    </flux:button>
                </div>

            </div>

        @empty

            <div class="rounded-xl border border-dashed p-8 text-center">
                <flux:text>
                    هنوز آزمون پایان‌یافته‌ای ندارید.
                </flux:text>
            </div>

        @endforelse

    </section>

</div>
