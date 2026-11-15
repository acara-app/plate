import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { MEAL_TYPES } from '@/lib/meal-types';
import { cn } from '@/lib/utils';
import type { MealType } from '@/types/meal-plan';
import { useTranslation } from 'react-i18next';
import { EASE_OUT, PRESSABLE } from './pressable';

export default function MealTypeSwitch({
    value,
    onChange,
    labelledBy,
}: {
    value: MealType;
    onChange: (value: MealType) => void;
    labelledBy: string;
}) {
    const { t } = useTranslation('common');

    return (
        <ToggleGroup
            type="single"
            value={value}
            onValueChange={(next) => next && onChange(next as MealType)}
            aria-labelledby={labelledBy}
            className="relative grid grid-cols-4 gap-0 rounded-full bg-muted/70 p-1"
        >
            <span
                aria-hidden="true"
                data-index={MEAL_TYPES.findIndex(
                    (option) => option.key === value,
                )}
                className={cn(
                    'pointer-events-none absolute inset-y-1 left-1 w-[calc((100%-0.5rem)/4)] rounded-full bg-background shadow-sm ring-1 ring-black/5 transition-transform duration-200 data-[index=1]:translate-x-full data-[index=2]:translate-x-[200%] data-[index=3]:translate-x-[300%] motion-reduce:transition-none dark:bg-neutral-700 dark:ring-white/10',
                    EASE_OUT,
                )}
            />
            {MEAL_TYPES.map((option) => (
                <ToggleGroupItem
                    key={option.key}
                    value={option.key}
                    className={cn(
                        'group/option relative h-11 min-w-0 gap-1.5 rounded-full px-1.5 text-xs leading-tight whitespace-normal text-muted-foreground transition-[color,scale] duration-150 ease-out hover:bg-transparent hover:text-foreground active:scale-[0.97] data-[state=on]:bg-transparent data-[state=on]:font-semibold data-[state=on]:text-foreground data-[state=on]:shadow-none sm:text-sm',
                        PRESSABLE,
                    )}
                >
                    <option.Icon
                        className={cn(
                            'hidden size-4 group-data-[state=off]/option:text-muted-foreground/70 min-[26rem]:block',
                            option.iconColor,
                        )}
                        aria-hidden="true"
                    />
                    {t(option.labelKey)}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
