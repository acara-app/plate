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
    subscribed: boolean;
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
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User extends Row {
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
}

export interface SexOption {
    value: string;
    label: string;
}

export interface Profile {
    age: number;
    height: number;
    weight: number;
    sex: string;
    goal_id?: number;
    target_weight?: number;
    additional_goals?: string;
    lifestyle_id?: number;
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

export interface JobTracking {
    status: 'pending' | 'processing' | 'completed' | 'failed';
    progress: number;
    message: string | null;
}
