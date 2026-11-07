export interface Entitlement {
    tier: SubscriptionTier;
    tier_label: string;
    payment_pending: boolean;
    payment_recovery_url: string | null;
    premium_enforcement_active: boolean;
    on_grace_period: boolean;
    grace_period_ends_at: string | null;
}

export type SubscriptionTier = 'free' | 'starter' | 'snap' | 'basic' | 'plus';
export interface AiAllowance {
    tier: SubscriptionTier;
    resets: 'daily' | 'monthly';
    resets_at: string;
    percentage: number;
    exhausted: boolean;
    can_upgrade: boolean;
}
