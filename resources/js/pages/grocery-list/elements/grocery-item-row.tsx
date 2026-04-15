import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { type GroceryItem } from '@/types/grocery-list';
import { useTranslation } from 'react-i18next';

interface GroceryItemRowProps {
    item: GroceryItem;
    onToggle: (item: GroceryItem) => void;
    showDays?: boolean;
    currentDay?: number;
}

export function GroceryItemRow({
    item,
    onToggle,
    showDays,
    currentDay,
}: GroceryItemRowProps) {
    const days = item.days ?? [];
    const otherDays = currentDay ? days.filter((d) => d !== currentDay) : [];
    const hasOtherDays = otherDays.length > 0;
    const { t } = useTranslation('common');

    const itemId = `item-${item.id}${currentDay ? `-day-${currentDay}` : ''}`;

    return (
        <li className="flex min-h-12 items-start gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-muted/50">
            <Checkbox
                id={itemId}
                checked={item.is_checked}
                className="mt-0.5 size-5 rounded-md"
                onCheckedChange={() => onToggle(item)}
            />
            <label
                htmlFor={itemId}
                className="flex min-h-8 flex-1 cursor-pointer flex-col justify-center gap-1 text-sm"
            >
                <span
                    className={cn(
                        'flex flex-wrap items-baseline gap-x-2 gap-y-1',
                        item.is_checked && 'text-muted-foreground line-through',
                    )}
                >
                    <span className="font-medium">{item.name}</span>
                    <span className="text-muted-foreground">
                        {item.quantity}
                    </span>
                </span>
                {(showDays && days.length > 1) || hasOtherDays ? (
                    <span className="flex flex-wrap gap-1.5">
                        {showDays && days.length > 1 && (
                            <Badge
                                variant="outline"
                                className="h-6 rounded-md px-2 text-xs font-normal"
                            >
                                {t('grocery_list.day', {
                                    number: days.join(', '),
                                })}
                            </Badge>
                        )}
                        {hasOtherDays && (
                            <Badge
                                variant="outline"
                                className="h-6 rounded-md px-2 text-xs font-normal"
                            >
                                {t('grocery_list.also_day', {
                                    days: otherDays.join(', '),
                                })}
                            </Badge>
                        )}
                    </span>
                ) : null}
            </label>
        </li>
    );
}
