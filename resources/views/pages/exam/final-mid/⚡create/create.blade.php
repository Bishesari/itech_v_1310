<div>
    <flux:heading size="lg" level="1">
        {{ __('ایجاد آزمون کتبی') }}
    </flux:heading>

    <div class="inline-flex mt-2 mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item>
                {{ __('آزمون‌ها') }}
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>
                {{ __('آزمون جدید') }}
            </flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:separator variant="subtle" />

    <form
        wire:submit="create"
        class="grid mt-5 sm:w-[400px]"
        autocomplete="off"
    >
        <flux:field class="mb-3">
            <flux:label>{{ __('نوع آزمون') }}</flux:label>

            <flux:select wire:model="type">
                <flux:select.option value="midterm">
                    {{ __('میان‌ترم') }}
                </flux:select.option>

                <flux:select.option value="final">
                    {{ __('پایان‌ترم') }}
                </flux:select.option>
            </flux:select>

            <flux:error name="type" />
        </flux:field>

        <flux:field class="mb-3">
            <flux:label>{{ __('استاندارد') }}</flux:label>

            <flux:select
                wire:model="standard_id"
                variant="listbox"
                searchable
                placeholder="استانداردی انتخاب کنید ..."
            >
                @foreach ($standards as $standard)
                    <flux:select.option value="{{ $standard->id }}">
                        {{ $standard->name_fa }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:error name="standard_id" />
        </flux:field>

        <flux:field class="mb-3">
            <flux:label>{{ __('عنوان آزمون') }}</flux:label>
            <flux:input wire:model="title" />
            <flux:error name="title" />
        </flux:field>

        <flux:field class="mb-3">
            <flux:label>{{ __('توضیحات') }}</flux:label>
            <flux:textarea wire:model="description" rows="2" />
            <flux:error name="description" />
        </flux:field>

        <div class="grid grid-cols-2 gap-3 mt-3">
            <flux:field>
                <flux:label>{{ __('تعداد سؤالات') }}</flux:label>
                <flux:input
                    type="number"
                    min="1"
                    wire:model="question_count"
                />
                <flux:error name="question_count" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('مدت آزمون (دقیقه)') }}</flux:label>
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

        <flux:field class="mt-4">
            <flux:label>{{ __('تاریخ و ساعت شروع') }}</flux:label>
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

        <div class="flex justify-end">
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
        </div>
    </form>
</div>
