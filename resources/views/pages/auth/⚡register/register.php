<?php

use App\Support\Digits;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('ثبت نام')]
class extends Component
{
    #[Validate('bail|required|digits:11|starts_with:09')]
    public string $number = '';


    public function submit(): void
    {
        $this->number = Digits::toEnglish($this->number);

        $this->validate();
    }
};
