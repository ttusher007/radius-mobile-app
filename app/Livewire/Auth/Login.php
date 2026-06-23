<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Sign In')]
class Login extends Component
{
    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', __('auth.failed'));
            return;
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            $this->addError('email', 'Your account is inactive. Contact the administrator.');
            return;
        }

        session()->regenerate();

        $this->redirect('/', navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.auth.login');
    }
}
