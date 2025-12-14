export type GroceryListStatus =
    | 'generating'
    | 'active'
    | 'completed'
    | 'failed';

export const GroceryStatus = {
    Generating: 'generating',
    Active: 'active',
    Completed: 'completed',
    Failed: 'failed',
} as const satisfies Record<string, GroceryListStatus>;

export interface GroceryItem {
    id: number;
    name: string;
    quantity: string;
    category: string;
    is_checked: boolean;
    days: number[];
}

export interface GroceryListMetadata {
    generated_at?: string;
    completed_at?: string;
    total_items?: number;
    [key: string]: unknown;
}

export interface GroceryList {
    id: number;
    name: string;
    status: GroceryListStatus;
    metadata: GroceryListMetadata | null;
    items_by_category: Record<string, GroceryItem[]>;
    items_by_day: Record<number, GroceryItem[]>;
    total_items: number;
    checked_items: number;
    duration_days: number;
}

export interface MealPlanSummary {
    id: number;
    name: string | null;
    duration_days: number;
}

export const GroceryCategory = {
    Produce: 'Produce',
    Dairy: 'Dairy',
    MeatAndSeafood: 'Meat & Seafood',
    Pantry: 'Pantry',
    Frozen: 'Frozen',
    Bakery: 'Bakery',
    Beverages: 'Beverages',
    CondimentsAndSauces: 'Condiments & Sauces',
    HerbsAndSpices: 'Herbs & Spices',
    Other: 'Other',
} as const;

export type GroceryCategoryType =
    (typeof GroceryCategory)[keyof typeof GroceryCategory];

export const categoryEmojis: Record<string, string> = {
    [GroceryCategory.Produce]: '🥬',
    [GroceryCategory.Dairy]: '🥛',
    [GroceryCategory.MeatAndSeafood]: '🥩',
    [GroceryCategory.Pantry]: '🥫',
    [GroceryCategory.Frozen]: '🧊',
    [GroceryCategory.Bakery]: '🍞',
    [GroceryCategory.Beverages]: '🥤',
    [GroceryCategory.CondimentsAndSauces]: '🧴',
    [GroceryCategory.HerbsAndSpices]: '🌿',
    [GroceryCategory.Other]: '📦',
};
