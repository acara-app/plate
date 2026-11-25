@props(['value', 'label', 'description'])

<label {{ $attributes->class('group relative flex min-h-16 min-w-0 cursor-pointer touch-manipulation items-center gap-2 rounded-[1.125rem] bg-muted px-3 py-2 text-left transition duration-150 ease-out select-none has-checked:bg-primary/10 has-checked:inset-ring-2 has-checked:inset-ring-primary has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50 active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100') }}>
    <input type="radio" name="snap-result-plan" value="{{ $value }}" x-model="plan" @checked($value === 'free') class="absolute inset-0 size-full cursor-pointer opacity-0" />
    <span aria-hidden="true" class="pointer-events-none flex size-4.5 shrink-0 items-center justify-center rounded-full inset-ring-2 inset-ring-muted-foreground/40 group-has-checked:bg-primary group-has-checked:text-primary-foreground group-has-checked:inset-ring-0 max-[359px]:hidden">
        <x-snap-result.icon name="check" class="hidden size-3 group-has-checked:block" stroke-width="3.5" />
    </span>
    <span class="pointer-events-none flex min-w-0 flex-col gap-0.5 break-words">
        <span class="text-sm leading-tight font-semibold text-balance">{{ $label }}</span>
        <span class="text-xs leading-snug text-muted-foreground tabular-nums">{{ $description }}</span>
    </span>
</label>
