@blaze(memo: true)

@props([
    'date' => null,
    'label' => null,
])

@php
    use App\Support\ExpiryDateHelper;

    $display = $label ?? ExpiryDateHelper::format($date);
@endphp

<span {{ $attributes->class(ExpiryDateHelper::textClass($date)) }}>{{ $display }}</span>
