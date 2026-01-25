import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export type Nullable<T> = T | null;

export interface Row {
    id: number;
    created_at: string;
    updated_at: string;
}

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    locale: string;
    translations: Record<string, unknown>;
    [key: string]: unknown;
}

export interface User extends Row {
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_verified: boolean;
    has_meal_plan?: boolean;
    is_onboarded?: boolean;
}

export interface SexOption {
    value: string;
    label: string;
}

export interface Profile {
    age?: number;
    height?: number;
    weight?: number;
    sex?: string;
    target_weight?: number;
    additional_goals?: string;
    goal_choice?: string;
    animal_product_choice?: string;
    intensity_choice?: string;
}

export interface DietaryPreference extends Row {
    name: string;
    type: string;
    description: string;
}

export interface Goal extends Row {
    name: string;
}

export interface HealthCondition extends Row {
    name: string;
    description: string;
    nutritional_impact: string;
}

export interface LifeStyle extends Row {
    name: string;
    activity_level: string;
    description: string;
    activity_multiplier: number;
}

export interface UserMedication extends Row {
    user_profile_id: number;
    name: string;
    dosage: string | null;
    frequency: string | null;
    purpose: string | null;
    started_at: string | null;
}

export enum GoalChoice {
    Spikes = 'spikes',
    WeightLoss = 'weight_loss',
    HeartHealth = 'heart_health',
    BuildMuscle = 'build_muscle',
    HealthyEating = 'healthy_eating',
}

export enum AnimalProductChoice {
    Omnivore = 'omnivore',
    Pescatarian = 'pescatarian',
    Vegan = 'vegan',
}

export enum IntensityChoice {
    Balanced = 'balanced',
    Aggressive = 'aggressive',
}

export const GoalChoices = Object.values(GoalChoice) as readonly string[];
export const AnimalProductChoices = Object.values(
    AnimalProductChoice,
) as readonly string[];
export const IntensityChoices = Object.values(
    IntensityChoice,
) as readonly string[];
