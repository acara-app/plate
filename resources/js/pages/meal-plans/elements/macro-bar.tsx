import { cn } from '@/lib/utils';
import { MacroPercentages } from '@/types/meal-plan';

interface MacroBarProps {
    macros: MacroPercentages;
    className?: string;
    showLegend?: boolean;
}

export function MacroBar({
    macros,
    className,
    showLegend = false,
}: MacroBarProps) {
    const { protein, carbs, fat } = macros;

    return (
        <div className={cn('space-y-2', className)}>
            <div className="flex h-2 w-full overflow-hidden rounded-full bg-muted">
                <div
                    className="bg-blue-500 transition-all"
                    style={{ width: `${protein}%` }}
                    title={`Protein: ${protein}%`}
                />
                <div
                    className="bg-green-500 transition-all"
                    style={{ width: `${carbs}%` }}
                    title={`Carbs: ${carbs}%`}
                />
                <div
                    className="bg-amber-500 transition-all"
                    style={{ width: `${fat}%` }}
                    title={`Fat: ${fat}%`}
                />
            </div>

            {showLegend && (
                <div className="flex items-center justify-center gap-4 text-xs text-muted-foreground">
                    <div className="flex items-center gap-1">
                        <div className="h-2 w-2 rounded-full bg-blue-500" />
                        <span>Protein {protein}%</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <div className="h-2 w-2 rounded-full bg-green-500" />
                        <span>Carbs {carbs}%</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <div className="h-2 w-2 rounded-full bg-amber-500" />
                        <span>Fat {fat}%</span>
                    </div>
                </div>
            )}
        </div>
    );
}
