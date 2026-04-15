import { Button } from '@/components/ui/button';
import { Loader2, ShoppingCart } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface EmptyGroceryListStateProps {
    onGenerate: () => void;
    isGenerating: boolean;
}

export function EmptyGroceryListState({
    onGenerate,
    isGenerating,
}: EmptyGroceryListStateProps) {
    const { t } = useTranslation('common');
    return (
        <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed bg-card p-8 text-center shadow-sm md:p-12">
            <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <ShoppingCart className="h-8 w-8" />
            </div>
            <h3 className="text-xl font-semibold">
                {t('grocery_list.empty.title')}
            </h3>
            <p className="mt-2 max-w-md text-muted-foreground">
                {t('grocery_list.empty.description')}
            </p>
            <Button
                className="mt-6 w-full sm:w-auto"
                onClick={onGenerate}
                disabled={isGenerating}
            >
                {isGenerating ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <ShoppingCart className="h-4 w-4" />
                )}
                {t('grocery_list.empty.button')}
            </Button>
        </div>
    );
}
