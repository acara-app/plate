import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { useBoundProp } from '@json-render/react';
import { useId } from 'react';
import type { ViewProps } from './props';

export function Segmented({ props, bindings }: ViewProps<'Segmented'>) {
    const name = useId();
    const [value, setValue] = useBoundProp<unknown>(
        props.value,
        bindings?.value,
    );

    return (
        <div
            role="radiogroup"
            className="flex w-full gap-1 rounded-full bg-foreground/[0.06] p-1 dark:bg-white/[0.08]"
        >
            {props.options.map((option) => (
                <label
                    key={option.value}
                    className="flex min-h-9 flex-1 cursor-pointer touch-manipulation items-center justify-center rounded-full px-3 py-1.5 text-center text-sm leading-5 font-medium text-muted-foreground transition-colors duration-150 ease-out select-none [-webkit-tap-highlight-color:transparent] hover:text-foreground has-checked:bg-background has-checked:text-foreground has-checked:shadow-[0_1px_2px_rgb(0_0_0/0.08)] has-focus-visible:ring-2 has-focus-visible:ring-emerald-600 dark:has-checked:bg-white/15 dark:has-focus-visible:ring-emerald-400"
                >
                    <input
                        type="radio"
                        name={name}
                        value={option.value}
                        checked={value === option.value}
                        onChange={() => setValue(option.value)}
                        className="sr-only"
                    />
                    {option.label}
                </label>
            ))}
        </div>
    );
}

export function Checklist({ props, bindings }: ViewProps<'Checklist'>) {
    const baseId = useId();
    const [checked, setChecked] = useBoundProp<unknown>(
        props.checked,
        bindings?.checked,
    );
    const done = Array.isArray(checked)
        ? checked.filter((id): id is string => typeof id === 'string')
        : [];

    return (
        <ul className="-mx-2 flex flex-col">
            {props.items.map((item, index) => {
                const id = `${baseId}-${index}`;
                const isDone = done.includes(item.id);

                return (
                    <li key={item.id}>
                        <label
                            htmlFor={id}
                            className="flex min-h-11 cursor-pointer touch-manipulation items-center gap-3 rounded-xl px-2 py-2 select-none [-webkit-tap-highlight-color:transparent] hover:bg-foreground/[0.04]"
                        >
                            <Checkbox
                                id={id}
                                checked={isDone}
                                onCheckedChange={(state) =>
                                    setChecked(
                                        state === true
                                            ? [...done, item.id]
                                            : done.filter(
                                                  (doneId) =>
                                                      doneId !== item.id,
                                              ),
                                    )
                                }
                                className="size-5 rounded-md border-foreground/30 focus-visible:ring-emerald-600/40 data-[state=checked]:border-emerald-700 data-[state=checked]:bg-emerald-700 data-[state=checked]:text-white dark:data-[state=checked]:border-emerald-400 dark:data-[state=checked]:bg-emerald-400 dark:data-[state=checked]:text-emerald-950"
                            />
                            <span
                                className={cn(
                                    'text-[15px] leading-6 text-foreground/90 transition-colors duration-150',
                                    isDone &&
                                        'text-muted-foreground line-through decoration-foreground/30',
                                )}
                            >
                                {item.label}
                            </span>
                        </label>
                    </li>
                );
            })}
        </ul>
    );
}
