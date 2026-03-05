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

export interface Goal {
    name: string;
    created_at: string;
    updated_at: string;
}

export interface LifeStyle {
    name: string;
    activity_level: string;
    description: string;
    activity_multiplier: number;
    created_at: string;
    updated_at: string;
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
