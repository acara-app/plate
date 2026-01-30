import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import onboarding from '@/routes/onboarding';
import { Link } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';

export function OnboardingBanner() {
    return (
        <Alert className="border-primary/50 bg-primary/5">
            <Sparkles className="h-5 w-5 text-primary" />
            <AlertTitle className="text-lg">
                Discover Your Perfect Nutrition Plan
            </AlertTitle>
            <AlertDescription className="space-y-3">
                <p>
                    Answer a few quick questions to unlock AI-powered meal plans
                    tailored to your dietary needs, health goals, and daily
                    routine. Whether you’re managing allergies, diabetes, or
                    aiming for weight loss, CustomNutriAI crafts nutrition that
                    fits you.
                </p>
                <Button asChild size="sm">
                    <Link href={onboarding.biometrics.show.url()}>
                        Create My Plan Now
                    </Link>
                </Button>
            </AlertDescription>
        </Alert>
    );
}
