export interface Entitlement {
    tier: SubscriptionTier;
    tier_label: string;
    payment_pending: boolean;
    payment_recovery_url: string | null;
    premium_enforcement_active: boolean;
    on_grace_period: boolean;
    grace_period_ends_at: string | null;
}

export type SubscriptionTier = 'free' | 'basic' | 'plus';
export type PaidSubscriptionTier = Exclude<SubscriptionTier, 'free'>;
export type LimitType = 'rolling' | 'weekly' | 'monthly';
export type GatedFeature =
    | 'meal_planner'
    | 'image_analysis'
    | 'memory'
    | 'health_sync';

export interface CreditWarning {
    limit_type: LimitType;
    tier: SubscriptionTier;
    tier_label: string;
    current_credits: number;
    limit_credits: number;
    percentage: number;
    resets_at: string;
    resets_in: string;
}

export interface PaywallCapTrigger {
    kind: 'cap';
    limitType: LimitType;
    currentTier: SubscriptionTier;
    currentCredits: number;
    limitCredits: number;
    resetsIn: string;
}

export interface PaywallFeatureTrigger {
    kind: 'feature';
    feature: GatedFeature;
    requiredTier: PaidSubscriptionTier;
    currentTier: SubscriptionTier;
}

export type PaywallTrigger = PaywallCapTrigger | PaywallFeatureTrigger;
