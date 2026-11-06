
<div>
    <flux:heading size="lg" level="1">
        {{ __('آزمون‌های کتبی') }}
    </flux:heading>

    <div class="inline-flex mt-2 mb-4">
        <flux:breadcrumbs>
{{--            <flux:breadcrumbs.item--}}
{{--                href="{{ route('exam.index') }}"--}}
{{--                wire:navigate--}}
{{--            >--}}
                {{ __('آزمون‌ها') }}
{{--            </flux:breadcrumbs.item>--}}

            <flux:breadcrumbs.item>
                {{ __('کوئیز جدید') }}
            </flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:separator variant="subtle" />

    <form
        wire:submit="create"
        class="grid mt-5 sm:w-[400px]"
        autocomplete="off"
    >
        {{-- Standard --}}
        <flux:select
            wire:model.live="standard_id"
            variant="listbox"
            placeholder="استانداردی انتخاب کنید ..."
            label="استاندارد"
            searchable
            class="mb-3"
        >
            @foreach ($standards as $standard)
                <flux:select.option value="{{ $standard->id }}">
                    {{ $standard->name_fa }}
                </flux:select.option>
            @endforeach
        </flux:select>

        @error('standard_id')
        <flux:text color="danger" size="sm">
            {{ $message }}
        </flux:text>
        @enderror

        {{-- Chapters --}}
        @if ($standard_id)
            <flux:checkbox.group
                wire:model.live="chapter_ids"
                variant="cards"
                class="flex-col mb-4"
                wire:key="chapters-{{ $standard_id }}"
            >
                <div
                    wire:loading
                    wire:target="standard_id"
                    class="text-amber-500 dark:text-amber-300"
                >
                    <flux:icon.loading />
                </div>

                @if ($chapters->isEmpty())
                    <flux:text>
                        {{ __('فصلی برای این استاندارد تعریف نشده است.') }}
                    </flux:text>
                @endif

                @foreach ($chapters as $chapter)
                    @php
                        $questionCount = $chapter->questions->count();
                    @endphp

                    @if ($questionCount > 0)
                        <flux:checkbox
                            value="{{ $chapter->id }}"
                            label="{{ $chapter->number . ' - ' . $chapter->title }}"
                            description="{{ __('تعداد سؤالات: ') . $questionCount }}"
                            class="cursor-pointer"
                        />
                    @else
                        <flux:checkbox
                            label="{{ $chapter->number . ' - ' . $chapter->title }}"
                            description="{{ __('بدون سؤال') }}"
                            disabled
                        />
                    @endif
                @endforeach
            </flux:checkbox.group>

            @error('chapter_ids')
            <flux:text color="danger" size="sm">
                {{ $message }}
            </flux:text>
            @enderror

            @error('chapter_ids.*')
            <flux:text color="danger" size="sm">
                {{ $message }}
            </flux:text>
            @enderror
        @endif

        {{-- Exam information --}}
        @if ($standard_id && $chapters->isNotEmpty())
            <flux:field class="mb-3">
                <flux:label>
                    {{ __('عنوان آزمون') }}
                    <span class="text-red-500">*</span>
                </flux:label>

                <flux:input wire:model="title" />
                <flux:error name="title" />
            </flux:field>

            <flux:field class="mb-3">
                <flux:label>
                    {{ __('توضیحات') }}
                </flux:label>

                <flux:textarea wire:model="description" rows="2" />
                <flux:error name="description" />
            </flux:field>

            @php
                $selectedQuestionCount = $chapters->whereIn('id', $chapter_ids)->sum('questions_count');
            @endphp

            <flux:badge color="orange" class="mt-2">
                {{ __('مجموع سؤالات فصل‌های انتخاب‌شده: ') . $selectedQuestionCount }}
            </flux:badge>


            <flux:badge color="orange" class="mt-2">
                {{ __('تعداد فصل‌های انتخابی: ') . count($chapter_ids) }}
            </flux:badge>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <flux:field>
                    <flux:label>
                        {{ __('تعداد سؤالات') }}
                        <span class="text-red-500">*</span>
                    </flux:label>

                    <flux:input
                        type="number"
                        min="1"
                        wire:model="question_count"
                    />
                    <flux:error name="question_count" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        {{ __('مدت آزمون (دقیقه)') }}
                        <span class="text-red-500">*</span>
                    </flux:label>

                    <flux:input
                        type="number"
                        min="1"
                        wire:model="duration_minutes"
                    />
                    <flux:error name="duration_minutes" />
                </flux:field>
            </div>

            <flux:field class="mt-4">
                <flux:label>
                    {{ __('نمره قبولی (از ۱۰۰)') }}
                </flux:label>

                <flux:input
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    wire:model="passing_score"
                />
                <flux:error name="passing_score" />
            </flux:field>

            {{-- Availability --}}
            <flux:field class="mt-4">
                <flux:label>
                    {{ __('تاریخ و ساعت شروع') }}
                </flux:label>

                <flux:input
                    type="datetime-local"
                    wire:model="available_from"
                />
                <flux:error name="available_from" />
            </flux:field>

            <flux:field class="mt-4 mb-4">
                <flux:label>
                    {{ __('تاریخ و ساعت پایان') }}
                    <span class="text-red-500">*</span>
                </flux:label>

                <flux:input
                    type="datetime-local"
                    wire:model="available_until"
                />
                <flux:error name="available_until" />
            </flux:field>

            <div class="flex justify-between flex-row-reverse">
                <flux:button
                    type="submit"
                    variant="primary"
                    color="sky"
                    size="sm"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="create">
                        {{ __('ذخیره') }}
                    </span>

                    <span wire:loading wire:target="create">
                        {{ __('در حال ذخیره...') }}
                    </span>
                </flux:button>

{{--                <flux:button--}}
{{--                    href="{{ route('exam.index') }}"--}}
{{--                    variant="primary"--}}
{{--                    color="zinc"--}}
{{--                    wire:navigate--}}
{{--                    size="sm"--}}
{{--                >--}}
{{--                    {{ __('انصراف') }}--}}
{{--                </flux:button>--}}
            </div>
        @endif
    </form>
</div>
