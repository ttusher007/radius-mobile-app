<x-layouts.app>
    <x-slot:heading>Access denied</x-slot:heading>

    <div class="mx-auto flex w-full max-w-lg flex-col gap-6">
        <flux:callout variant="danger" icon="shield-exclamation">
            <flux:callout.heading>You cannot open {{ $pageLabel }}</flux:callout.heading>
            <flux:callout.text>
                This page exists, but your account does not meet the access requirements below.
                Ask an administrator to assign the needed permission or manager / POP scope.
            </flux:callout.text>
        </flux:callout>

        @if ($missingPermissions !== [])
            <flux:card class="space-y-3 p-4 sm:p-5">
                <flux:heading size="sm">Required permission (any one)</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Your role must include at least one of these permission names:
                </flux:text>
                <ul class="space-y-2">
                    @foreach ($missingPermissions as $permission)
                        <li class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/60">
                            <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $permission['name'] }}
                            </p>
                            @if ($permission['description'])
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $permission['description'] }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </flux:card>
        @endif

        @if ($missingScope)
            <flux:card class="space-y-3 p-4 sm:p-5">
                <flux:heading size="sm">Manager / POP scope required</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    You also need access to at least one manager or POP. That usually comes from
                    one of these permissions plus an assignment in the admin panel:
                </flux:text>
                <ul class="space-y-2">
                    @foreach ($scopePermissions as $permission)
                        <li class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/60">
                            <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $permission['name'] }}
                            </p>
                            @if ($permission['description'])
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $permission['description'] }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </flux:card>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row">
            <flux:button href="{{ route('dashboard') }}" variant="primary" class="min-h-[44px] w-full sm:w-auto" wire:navigate>
                Back to dashboard
            </flux:button>
        </div>
    </div>
</x-layouts.app>
