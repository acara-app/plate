import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export function GroceryListSkeleton() {
    const { t } = useTranslation('common');
    return (
        <div className="space-y-6">
            <Alert className="border-primary/30 bg-primary/5">
                <Loader2 className="h-4 w-4 animate-spin text-primary" />
                <AlertTitle className="text-primary">
                    {t('grocery_list.generating.title')}
                </AlertTitle>
                <AlertDescription className="text-muted-foreground">
                    {t('grocery_list.generating.description')}
                </AlertDescription>
            </Alert>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {[1, 2, 3, 4, 5, 6].map((i) => (
                    <Card key={i} className="overflow-hidden">
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <div className="h-6 w-32 animate-pulse rounded bg-muted" />
                                <div className="h-5 w-12 animate-pulse rounded bg-muted" />
                            </div>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="space-y-3">
                                {[1, 2, 3, 4].map((j) => (
                                    <div
                                        key={j}
                                        className="flex items-center gap-3"
                                    >
                                        <div className="h-4 w-4 animate-pulse rounded bg-muted" />
                                        <div className="h-4 flex-1 animate-pulse rounded bg-muted" />
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
