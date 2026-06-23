<div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-zinc-50 to-zinc-100 px-4 py-10 dark:from-zinc-900 dark:to-zinc-800">

    {{-- Brand --}}
    <div class="mb-8 flex flex-col items-center gap-3 text-center">
        <div class="flex size-16 items-center justify-center rounded-2xl bg-zinc-900 text-white shadow-lg dark:bg-white dark:text-zinc-900">
            <flux:icon name="wifi" class="size-8" />
        </div>

        <flux:heading size="xl" class="text-2xl font-bold tracking-tight">
            {{ config('app.name') }}
        </flux:heading>

        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
            Sign in to continue
        </flux:text>
    </div>

    {{-- Login card --}}
    <flux:card class="w-full max-w-sm px-6 py-8 shadow-sm">
        <form wire:submit="login" class="flex flex-col gap-5">

            {{-- Email --}}
            <flux:field>
                <flux:label>Email address</flux:label>
                <flux:input
                    type="email"
                    wire:model="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    autofocus
                />
                <flux:error name="email" />
            </flux:field>

            {{-- Password --}}
            <flux:field>
                <div class="flex items-center justify-between">
                    <flux:label>Password</flux:label>
                    <a
                        href="#"
                        class="text-xs text-zinc-500 underline-offset-4 hover:text-zinc-800 hover:underline dark:text-zinc-400 dark:hover:text-zinc-200"
                    >
                        Forgot password?
                    </a>
                </div>
                <flux:input
                    type="password"
                    wire:model="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            {{-- Remember me --}}
            <div class="flex items-center gap-3">
                <flux:checkbox wire:model="remember" id="remember" />
                <label for="remember" class="cursor-pointer select-none text-sm text-zinc-600 dark:text-zinc-300">
                    Remember me for 30 days
                </label>
            </div>

            {{-- Submit button (min 44 px touch target) --}}
            <flux:button
                type="submit"
                variant="primary"
                class="mt-1 min-h-[44px] w-full text-sm font-semibold"
                wire:loading.attr="disabled"
                wire:target="login"
            >
                <span wire:loading.remove wire:target="login">Sign in</span>
                <span wire:loading wire:target="login">Signing in…</span>
            </flux:button>

        </form>
    </flux:card>

    <p class="mt-8 text-center text-xs text-zinc-400 dark:text-zinc-600">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </p>

</div>
