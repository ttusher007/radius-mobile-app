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

    /** MUSHAK-6.3 tax invoice header fields. */
    public string $registeredPersonName = '';

    public string $bin = '';

    public string $invoiceIssuingAddress = '';

    public ?string $savedMessage = null;

    public function mount(): void
    {
        $this->authorizeSettingsAccess();

        $this->mandatoryRechargeCustomer = AppSettings::mandatoryRechargeCustomer();
        $this->registeredPersonName = AppSettings::registeredPersonName();
        $this->bin = AppSettings::bin();
        $this->invoiceIssuingAddress = AppSettings::invoiceIssuingAddress();
    }

    public function save(): void
    {
        $this->authorizeSettingsAccess();

        $this->validate([
            'registeredPersonName' => 'nullable|string|max:191',
            'bin' => 'nullable|string|max:64',
            'invoiceIssuingAddress' => 'nullable|string|max:500',
        ], [], [
            'registeredPersonName' => 'registered person name',
            'bin' => 'BIN number',
            'invoiceIssuingAddress' => 'invoice issuing address',
        ]);

        AppSettings::setMandatoryRechargeCustomer($this->mandatoryRechargeCustomer);
        AppSettings::setString(AppSettings::REGISTERED_PERSON_NAME, $this->registeredPersonName);
        AppSettings::setString(AppSettings::BIN, $this->bin);
        AppSettings::setString(AppSettings::INVOICE_ISSUING_ADDRESS, $this->invoiceIssuingAddress);

        $this->savedMessage = 'Billing settings saved.';
    }

    public function render()
    {
        return view('livewire.settings.billing-settings', [
            'logoUrl' => AppSettings::logoUrl(),
            'logoPath' => 'public/'.AppSettings::LOGO_PATH,
        ]);
    }

    private function authorizeSettingsAccess(): void
    {
        abort_unless(
            AccessHelper::any(['settings_update', 'settings_edit', 'super-admin']),
            403
        );
    }
}
