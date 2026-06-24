@props([
    'label' => null,
    'model',
    'options' => [],
    'placeholder' => 'Search…',
    'empty' => 'No matches',
])

<flux:field>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    <div
        wire:key="searchable-select-{{ $model }}-{{ md5(json_encode($options)) }}"
        x-data="{
            open: false,
            search: '',
            options: @js($options),
            selected: $wire.$entangle('{{ $model }}', true),
            get filtered() {
                if (! this.search.trim()) return this.options;
                const q = this.search.toLowerCase();
                return this.options.filter((o) => String(o.label).toLowerCase().includes(q));
            },
            labelFor(value) {
                const match = this.options.find((o) => String(o.value) === String(value));
                return match ? match.label : '—';
            },
            choose(value) {
                this.selected = value;
                this.open = false;
                this.search = '';
            },
        }"
        x-on:keydown.escape.window="open = false"
        class="relative"
    >
        <button
            type="button"
            x-on:click="open = ! open"
            class="flex h-10 w-full min-h-[44px] items-center justify-between gap-2 rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-start text-base shadow-xs sm:text-sm dark:border-white/10 dark:bg-white/10"
        >
            <span x-text="labelFor(selected)" class="truncate text-zinc-700 dark:text-zinc-300"></span>
            <flux:icon name="chevron-down" class="size-4 shrink-0 text-zinc-400" />
        </button>

        <div
            x-show="open"
            x-collapse
            x-on:click.outside="open = false"
            class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-800"
        >
            <div class="border-b border-zinc-200 p-2 dark:border-white/10">
                <input
                    type="text"
                    x-model="search"
                    placeholder="{{ $placeholder }}"
                    class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 shadow-xs focus:border-zinc-300 focus:outline-none dark:border-white/10 dark:bg-white/10 dark:text-zinc-300"
                    x-on:keydown.stop
                />
            </div>

            <ul class="max-h-60 overflow-y-auto py-1">
                <template x-for="option in filtered" :key="option.value">
                    <li>
                        <button
                            type="button"
                            x-on:click="choose(option.value)"
                            class="flex min-h-[44px] w-full items-center px-3 py-2 text-start text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700"
                            x-bind:class="{ 'bg-zinc-100 font-medium dark:bg-zinc-700': String(selected) === String(option.value) }"
                            x-text="option.label"
                        ></button>
                    </li>
                </template>
                <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-500">{{ $empty }}</li>
            </ul>
        </div>
    </div>
</flux:field>
