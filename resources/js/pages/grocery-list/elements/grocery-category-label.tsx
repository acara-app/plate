import { cn } from '@/lib/utils';
import { GroceryCategory } from '@/types/grocery-list';
import {
    Beef,
    BottleWine,
    Carrot,
    Croissant,
    CupSoda,
    Leaf,
    Milk,
    Package,
    Package2,
    Snowflake,
    type LucideIcon,
} from 'lucide-react';

export const groceryCategoryIcons: Record<
    string,
    { Icon: LucideIcon; className: string }
> = {
    [GroceryCategory.Produce]: {
        Icon: Carrot,
        className:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    },
    [GroceryCategory.Dairy]: {
        Icon: Milk,
        className:
            'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
    },
    [GroceryCategory.MeatAndSeafood]: {
        Icon: Beef,
        className:
            'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
    },
    [GroceryCategory.Pantry]: {
        Icon: Package2,
        className:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    },
    [GroceryCategory.Frozen]: {
        Icon: Snowflake,
        className:
            'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
    },
    [GroceryCategory.Bakery]: {
        Icon: Croissant,
        className:
            'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
    },
    [GroceryCategory.Beverages]: {
        Icon: CupSoda,
        className:
            'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
    },
    [GroceryCategory.CondimentsAndSauces]: {
        Icon: BottleWine,
        className:
            'bg-lime-100 text-lime-700 dark:bg-lime-500/15 dark:text-lime-300',
    },
    [GroceryCategory.HerbsAndSpices]: {
        Icon: Leaf,
        className:
            'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300',
    },
    [GroceryCategory.Other]: {
        Icon: Package,
        className:
            'bg-muted text-muted-foreground dark:bg-muted dark:text-muted-foreground',
    },
};

interface GroceryCategoryLabelProps {
    category: string;
}

export function GroceryCategoryLabel({ category }: GroceryCategoryLabelProps) {
    const config =
        groceryCategoryIcons[category] ??
        groceryCategoryIcons[GroceryCategory.Other];
    const Icon = config.Icon;

    return (
        <span className="flex min-w-0 items-center gap-2">
            <span
                className={cn(
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                    config.className,
                )}
                aria-hidden="true"
            >
                <Icon className="h-4 w-4" />
            </span>
            <span className="truncate">{category}</span>
        </span>
    );
}
