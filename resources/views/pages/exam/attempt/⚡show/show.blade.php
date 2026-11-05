
<div class="space-y-6">

    @php
        $questions = $attempt->questions;
        $currentQuestion = $questions->get($currentQuestionIndex);
        $currentAnswer = $currentQuestion?->answer;
        $questionSnapshot = $currentQuestion?->question_snapshot ?? [];
        $options = $currentQuestion?->options ?? collect();

        $remainingSeconds = max(
            0,
            (int) now()->diffInSeconds($attempt->deadline_at, false)
        );
    @endphp

    @if ($attempt->status === \App\Enums\ExamAttemptStatus::SUBMITTED)

        <div class="rounded-xl border p-6 text-center space-y-3">
            <flux:icon name="check-circle" class="size-12 mx-auto text-green-600" />

            <flux:heading size="xl">
                آزمون به پایان رسیده است
            </flux:heading>

            <flux:text>
                پاسخ‌های شما ثبت و آزمون تصحیح شده است.
            </flux:text>

{{--            <flux:button--}}
{{--                href="{{ route('exam.results.show', $attempt) }}"--}}
{{--                variant="primary"--}}
{{--            >--}}
                مشاهده نتیجه آزمون
{{--            </flux:button>--}}
        </div>

    @else

        {{-- Exam Header --}}
        <div class="rounded-xl border p-4 space-y-4">

            <div class="flex items-center justify-between gap-4">

                <div>
                    <flux:heading size="lg">
                        {{ $attempt->exam->title }}
                    </flux:heading>

                    <flux:text>
                        سؤال {{ $currentQuestionIndex + 1 }}
                        از {{ $attempt->question_count }}
                    </flux:text>
                </div>

                <div
                    x-data="{ remaining: {{ $remainingSeconds }} }"
                    x-init="
        const timer = setInterval(() => {
            if (remaining > 0) {
                remaining--;
            }

            if (remaining <= 0) {
                clearInterval(timer);
                $wire.refreshAttempt();
            }
        }, 1000);
    "
                    class="text-center"
                >
                    <flux:badge color="warning">
                        زمان باقی‌مانده
                    </flux:badge>

                    <div
                        class="font-mono text-xl font-bold tabular-nums"
                        dir="ltr"
                        x-text="
            String(Math.floor(remaining / 3600)).padStart(2, '0')
            + ':' +
            String(Math.floor((remaining % 3600) / 60)).padStart(2, '0')
            + ':' +
            String(remaining % 60).padStart(2, '0')
        "
                    ></div>
                </div>

            </div>

            <flux:separator />

            {{-- Question Navigation --}}
            <div class="flex flex-wrap gap-2">

                @foreach ($questions as $index => $question)

                    <flux:button
                        wire:click="goToQuestion({{ $index }})"
                        size="sm"
                        variant="{{ $currentQuestionIndex === $index ? 'primary' : 'outline' }}"
                        color="{{ $question->answer->is_marked_for_review ? 'warning' : ($question->answer->selected_option_id ? 'success' : 'zinc') }}"
                    >
                        {{ $index + 1 }}
                    </flux:button>

                @endforeach

            </div>

        </div>

        {{-- Current Question --}}
        @if ($currentQuestion)

            <div class="rounded-xl border p-5 space-y-6">

                <div class="flex items-center justify-between gap-3">

                    <flux:heading size="lg">
                        سؤال {{ $currentQuestion->position }}
                    </flux:heading>

                    <flux:button
                        wire:click="toggleReview({{ $currentQuestion->id }})"
                        size="sm"
                        variant="outline"
                        color="{{ $currentAnswer->is_marked_for_review ? 'warning' : 'zinc' }}"
                        icon="bookmark"
                    >
                        {{ $currentAnswer->is_marked_for_review
                            ? 'حذف علامت مرور'
                            : 'علامت‌گذاری برای مرور' }}
                    </flux:button>

                </div>

                {{-- Question Text --}}
                <div class="text-base leading-8 whitespace-pre-line">
                    {{ $questionSnapshot['text'] ?? '' }}
                </div>

                {{-- Question Image --}}
                @if (!empty($questionSnapshot['image_path']))
                    <img
                        src="{{ Storage::url($questionSnapshot['image_path']) }}"
                        alt="تصویر سؤال"
                        class="max-w-full rounded-lg"
                    >
                @endif

                {{-- Question Code --}}
                @if (!empty($questionSnapshot['code']))
                    <pre class="overflow-x-auto rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800"><code>{{ $questionSnapshot['code'] }}</code></pre>
                @endif

                <flux:separator />

                {{-- Options --}}
                <div class="space-y-3">

                    @foreach ($options as $option)

                        @php
                            $optionSnapshot = $option->option_snapshot ?? [];
                        @endphp

                        <label
                            wire:key="option-{{ $option->id }}"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-800"
                        >

                            <input
                                type="radio"
                                name="question-{{ $currentQuestion->id }}"
                                value="{{ $option->id }}"
                                wire:click="saveAnswer({{ $currentQuestion->id }}, {{ $option->id }})"
                                @checked((int) $currentAnswer->selected_option_id === (int) $option->id)
                                class="mt-1"
                            >

                            <div class="min-w-0 flex-1 space-y-2">

                                @if (!empty($optionSnapshot['text']))
                                    <div class="whitespace-pre-line">
                                        {{ $optionSnapshot['text'] }}
                                    </div>
                                @endif

                                @if (!empty($optionSnapshot['image_path']))
                                    <img
                                        src="{{ Storage::url($optionSnapshot['image_path']) }}"
                                        alt="تصویر گزینه"
                                        class="max-h-64 max-w-full rounded-lg"
                                    >
                                @endif

                            </div>

                        </label>

                    @endforeach

                </div>

            </div>

            {{-- Navigation Buttons --}}
            <div class="flex items-center justify-between gap-3">

                <flux:button
                    wire:click="previousQuestion"
                    variant="outline"
                    icon="arrow-right"
                    :disabled="$currentQuestionIndex === 0"
                >
                    سؤال قبلی
                </flux:button>

                @if ($currentQuestionIndex < $questions->count() - 1)

                    <flux:button
                        wire:click="nextQuestion"
                        variant="primary"
                        icon-trailing="arrow-left"
                    >
                        سؤال بعدی
                    </flux:button>

                @else

                    <flux:button
                        wire:click="submitAttempt"
                        wire:confirm="آیا از تحویل نهایی آزمون مطمئن هستید؟"
                        variant="danger"
                    >
                        تحویل آزمون
                    </flux:button>

                @endif

            </div>

        @endif

    @endif

</div>
