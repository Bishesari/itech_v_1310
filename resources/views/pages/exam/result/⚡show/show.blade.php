
<div class="space-y-6">

    @php
        $summary = $result['summary'];
        $questions = $result['questions'];
        $canReview = $result['can_review_answers'];

        $correctCount = (int) $summary['correct_count'];

        $answeredCount = collect($questions)
            ->where('is_answered', true)
            ->count();

        $unansweredCount = count($questions) - $answeredCount;

        $wrongCount = max(0, $answeredCount - $correctCount);
    @endphp

    {{-- Result Summary --}}
    <div class="rounded-xl border p-6 space-y-6">

        <div class="text-center space-y-3">

            <flux:heading size="xl">
                نتیجه آزمون
            </flux:heading>

            <div class="text-4xl font-bold font-mono">
                {{ number_format($summary['score'], 2) }}
                <span class="text-lg font-normal text-zinc-500">از ۱۰۰</span>
            </div>

            @if ($summary['passed'] === true)

                <flux:badge color="success" size="lg">
                    قبول شده
                </flux:badge>

            @elseif ($summary['passed'] === false)

                <flux:badge color="danger" size="lg">
                    مردود
                </flux:badge>

            @else

                <flux:badge color="zinc" size="lg">
                    بدون حدنصاب قبولی
                </flux:badge>

            @endif

            @if ($summary['passing_score'] !== null)
                <flux:text>
                    حدنصاب قبولی:
                    {{ $summary['passing_score'] }}
                </flux:text>
            @endif

        </div>

        <flux:separator />

        {{-- Statistics --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">

            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold">
                    {{ $summary['question_count'] }}
                </div>
                <flux:text>تعداد سؤال‌ها</flux:text>
            </div>

            <div class="rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">
                    {{ $correctCount }}
                </div>
                <flux:text>پاسخ صحیح</flux:text>
            </div>

            <div class="rounded-lg bg-red-50 dark:bg-red-950/30 p-4 text-center">
                <div class="text-2xl font-bold text-red-600">
                    {{ $wrongCount }}
                </div>
                <flux:text>پاسخ غلط</flux:text>
            </div>

            <div class="rounded-lg bg-amber-50 dark:bg-amber-950/30 p-4 text-center">
                <div class="text-2xl font-bold text-amber-600">
                    {{ $unansweredCount }}
                </div>
                <flux:text>بدون پاسخ</flux:text>
            </div>

        </div>

    </div>

    {{-- Review Availability --}}
    @if (! $canReview)

        <div class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-900 p-4 space-y-2">

            <flux:heading size="md">
                بررسی پاسخ‌ها فعلاً امکان‌پذیر نیست
            </flux:heading>

            <flux:text>
                پس از پایان مهلت تعیین‌شده برای نمایش پاسخ‌ها،
                گزینه‌های صحیح و توضیحات سؤال‌ها در دسترس قرار خواهند گرفت.
            </flux:text>

        </div>

    @endif

    {{-- Questions Review --}}
    @if ($canReview)

        <div class="space-y-5">

            <flux:heading size="xl">
                بررسی پاسخ‌ها
            </flux:heading>

            @foreach ($questions as $question)

                @php
                    $questionData = $question['question'];
                @endphp

                <div
                    wire:key="result-question-{{ $question['id'] }}"
                    class="rounded-xl border p-5 space-y-5"
                >

                    {{-- Question Header --}}
                    <div class="flex items-center justify-between gap-3">

                        <flux:heading size="lg">
                            سؤال {{ $question['position'] }}
                        </flux:heading>

                        @if (! $question['is_answered'])

                            <flux:badge color="zinc">
                                بدون پاسخ
                            </flux:badge>

                        @elseif ($question['is_correct'])

                            <flux:badge color="success">
                                صحیح
                            </flux:badge>

                        @else

                            <flux:badge color="danger">
                                غلط
                            </flux:badge>

                        @endif

                    </div>

                    {{-- Question Text --}}
                    <div class="whitespace-pre-line leading-8">
                        {{ $questionData['text'] ?? '' }}
                    </div>

                    {{-- Question Image --}}
                    @if (!empty($questionData['image_path']))

                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::url($questionData['image_path']) }}"
                            alt="تصویر سؤال"
                            class="max-w-full rounded-lg"
                        >

                    @endif

                    {{-- Question Code --}}
                    @if (!empty($questionData['code']))

                        <pre class="overflow-x-auto rounded-lg bg-zinc-100 dark:bg-zinc-800 p-4"><code>{{ $questionData['code'] }}</code></pre>

                    @endif

                    <flux:separator />

                    {{-- Options --}}
                    <div class="space-y-3">

                        @foreach ($question['options'] as $option)

                            @php
                                $optionSnapshot = $option['snapshot'] ?? [];

                                $isSelected = $option['is_selected'];
                                $isCorrect = $option['is_correct'] ?? false;
                            @endphp

                            <div
                                wire:key="result-option-{{ $option['id'] }}"
                                @class([
                                    'rounded-lg border p-4 space-y-2',
                                    'border-green-500 bg-green-50 dark:bg-green-950/20' => $isCorrect,
                                    'border-red-500 bg-red-50 dark:bg-red-950/20' => $isSelected && ! $isCorrect,
                                    'border-zinc-200 dark:border-zinc-700' => ! $isCorrect && ! $isSelected,
                                ])
                            >

                                <div class="flex items-start gap-3">

                                    <div class="min-w-0 flex-1 space-y-2">

                                        <div class="whitespace-pre-line">
                                            {{ $optionSnapshot['text'] ?? '' }}
                                        </div>

                                        @if (!empty($optionSnapshot['image_path']))

                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::url($optionSnapshot['image_path']) }}"
                                                alt="تصویر گزینه"
                                                class="max-h-64 max-w-full rounded-lg"
                                            >

                                        @endif

                                    </div>

                                    <div class="flex flex-col gap-1 shrink-0">

                                        @if ($isCorrect)

                                            <flux:badge color="success">
                                                پاسخ صحیح
                                            </flux:badge>

                                        @endif

                                        @if ($isSelected)

                                            <flux:badge color="{{ $isCorrect ? 'success' : 'danger' }}">
                                                پاسخ شما
                                            </flux:badge>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                    {{-- Explanation --}}
                    @if (!empty($questionData['explanation']))

                        <div class="rounded-lg bg-blue-50 dark:bg-blue-950/20 p-4 space-y-2">

                            <flux:heading size="sm">
                                توضیحات پاسخ
                            </flux:heading>

                            <div class="whitespace-pre-line leading-7">
                                {{ $questionData['explanation'] }}
                            </div>

                        </div>

                    @endif

                </div>

            @endforeach

        </div>

    @endif

</div>
