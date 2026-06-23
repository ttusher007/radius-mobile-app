<?php

use App\Livewire\Auth\Login;
use App\Livewire\Billing\BillView;
use App\Livewire\Billing\MoneyReceipt;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/billing/bill-view', BillView::class)->name('billing.bill-view');
    Route::get('/billing/money-receipt/{customer}', MoneyReceipt::class)
        ->whereNumber('customer')
        ->name('billing.money-receipt');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');
