export type User = {
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_verified: boolean;
    has_meal_plan?: boolean;
    is_onboarded?: boolean;
    created_at: string;
    updated_at: string;
};

export interface Auth {
    user: User;
    subscribed: boolean;
}

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
