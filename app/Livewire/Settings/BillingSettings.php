<?php

namespace App\Livewire\Settings;

use App\Support\AccessHelper;
use App\Support\AppSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Billing Settings')]
class BillingSettings extends Component
{
    public bool $mandatoryRechargeCustomer = false;

    public ?string $savedMessage = null;

    public function mount(): void
    {
        $this->authorizeSettingsAccess();

        $this->mandatoryRechargeCustomer = AppSettings::mandatoryRechargeCustomer();
    }

    public function save(): void
    {
        $this->authorizeSettingsAccess();

        AppSettings::setMandatoryRechargeCustomer($this->mandatoryRechargeCustomer);

        $this->savedMessage = 'Billing settings saved.';
    }

    public function render()
    {
        return view('livewire.settings.billing-settings');
    }

    private function authorizeSettingsAccess(): void
    {
        abort_unless(
            AccessHelper::any(['settings_update', 'settings_edit', 'super-admin']),
            403
        );
    }
}
