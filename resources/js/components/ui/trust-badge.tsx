import { cn } from '@/lib/utils';
import { OpenFoodFactsVerification } from '@/types/meal-plan';
import { AlertTriangle, Shield, Sparkles } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from './tooltip';

interface TrustBadgeProps {
    verification: OpenFoodFactsVerification | null;
    className?: string;
}

export function TrustBadge({ verification, className }: TrustBadgeProps) {
    if (!verification) {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <div
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
                                className,
                            )}
                        >
                            <Sparkles className="h-3 w-3" />
                            <span>AI Estimate</span>
                        </div>
                    </TooltipTrigger>
                    <TooltipContent className="max-w-xs">
                        <p className="font-semibold">AI Generated Estimate</p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Nutritional values estimated by AI. Not verified
                            with food database.
                        </p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        );
    }

    // High discrepancy detected (corrections were applied)
    if (
        verification.verified &&
        verification.corrections_applied &&
        Object.keys(verification.corrections_applied).length > 0
    ) {
        const corrections = Object.entries(
            verification.corrections_applied,
        ).map(([nutrient, data]) => ({
            nutrient,
            ...data,
        }));

        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <div
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full bg-orange-100 px-2 py-1 text-xs font-medium text-orange-700 dark:bg-orange-950 dark:text-orange-300',
                                className,
                            )}
                        >
                            <AlertTriangle className="h-3 w-3" />
                            <span>Corrected</span>
                        </div>
                    </TooltipTrigger>
                    <TooltipContent className="max-w-sm">
                        <p className="font-semibold">
                            High Discrepancy Detected
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            AI estimates had significant differences (&gt;15%)
                            from OpenFoodFacts database. Values were adjusted
                            using a weighted average.
                        </p>
                        <div className="mt-2 space-y-2 border-t pt-2">
                            {corrections.map((correction) => (
                                <div
                                    key={correction.nutrient}
                                    className="text-xs"
                                >
                                    <p className="font-medium capitalize">
                                        {correction.nutrient}
                                    </p>
                                    <p className="text-muted-foreground">
                                        Discrepancy:{' '}
                                        {correction.discrepancy_percent.toFixed(
                                            1,
                                        )}
                                        %
                                    </p>
                                    <p className="text-muted-foreground">
                                        AI: {correction.original.toFixed(1)} →
                                        Verified:{' '}
                                        {correction.verified.toFixed(1)} →
                                        Corrected:{' '}
                                        {correction.corrected.toFixed(1)}
                                    </p>
                                </div>
                            ))}
                        </div>
                        <p className="mt-2 text-xs italic text-muted-foreground">
                            Source: OpenFoodFacts Database
                        </p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        );
    }

    // Verified by OpenFoodFacts (low discrepancy, no corrections)
    if (verification.verified && verification.confidence === 'high') {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <div
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300',
                                className,
                            )}
                        >
                            <Shield className="h-3 w-3" />
                            <span>Verified</span>
                        </div>
                    </TooltipTrigger>
                    <TooltipContent className="max-w-xs">
                        <p className="font-semibold">
                            Verified by OpenFoodFacts
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Nutritional values cross-checked with OpenFoodFacts
                            database. All values within acceptable range
                            (&lt;15% discrepancy).
                        </p>
                        <div className="mt-2 border-t pt-2 text-xs">
                            <p className="text-muted-foreground">
                                Verification Rate:{' '}
                                {(verification.verification_rate * 100).toFixed(
                                    0,
                                )}
                                %
                            </p>
                            <p className="text-muted-foreground">
                                Confidence: {verification.confidence}
                            </p>
                        </div>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        );
    }

    // Medium confidence (some ingredients matched but nutrition incomplete)
    if (!verification.verified && verification.confidence === 'medium') {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <div
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300',
                                className,
                            )}
                        >
                            <Sparkles className="h-3 w-3" />
                            <span>Partial Match</span>
                        </div>
                    </TooltipTrigger>
                    <TooltipContent className="max-w-xs">
                        <p className="font-semibold">Partially Verified</p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {verification.note ||
                                'Some ingredients matched in database but nutrition data was incomplete. Using AI estimates.'}
                        </p>
                        <div className="mt-2 border-t pt-2 text-xs">
                            <p className="text-muted-foreground">
                                Verification Rate:{' '}
                                {(verification.verification_rate * 100).toFixed(
                                    0,
                                )}
                                %
                            </p>
                        </div>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        );
    }

    // Low confidence / failed verification
    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <div
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-900 dark:text-gray-300',
                            className,
                        )}
                    >
                        <Sparkles className="h-3 w-3" />
                        <span>AI Estimate</span>
                    </div>
                </TooltipTrigger>
                <TooltipContent className="max-w-xs">
                    <p className="font-semibold">Low Confidence Estimate</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Unable to verify ingredients in food database.
                        Nutritional values are AI estimates only.
                    </p>
                    <div className="mt-2 border-t pt-2 text-xs">
                        <p className="text-muted-foreground">
                            Verification Rate:{' '}
                            {(verification.verification_rate * 100).toFixed(0)}%
                        </p>
                        <p className="text-muted-foreground">
                            Confidence: {verification.confidence}
                        </p>
                    </div>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
