import type { ComponentType, SVGProps } from 'react';
import { useTranslation } from 'react-i18next';

type Drink = {
    nameKey: string;
    servingKey: string;
    ml: number;
    mg: number;
    Icon: ComponentType<SVGProps<SVGSVGElement>>;
};

const DEFAULT_LIMIT_MG = 400;

function EspressoIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.6}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M7 9h8v4a3 3 0 0 1-3 3h-2a3 3 0 0 1-3-3V9z" />
            <path d="M15 10h1.5a1.5 1.5 0 0 1 0 3H15" />
            <path d="M7 19h8" />
            <path d="M9 5c0 1-.5 1.5-.5 2.5" />
            <path d="M12 5c0 1-.5 1.5-.5 2.5" />
        </svg>
    );
}

function DripCoffeeIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.6}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M5 8h11v9a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V8z" />
            <path d="M16 10h2a2 2 0 0 1 0 4h-2" />
            <path d="M8 4c0 1-1 1-1 2.5" />
            <path d="M11 4c0 1-1 1-1 2.5" />
            <path d="M14 4c0 1-1 1-1 2.5" />
        </svg>
    );
}

function LatteIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.6}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M7 4h10l-1 16a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2L7 4z" />
            <path d="M8 11h8" />
            <path d="M11 8c0-1 1-1.5 1-3" />
        </svg>
    );
}

function EnergyDrinkIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.6}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M8 5h8v15a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V5z" />
            <path d="M8 8h8" />
            <path d="M8 18h8" />
            <path d="M13 11l-2 3h2l-1 3" />
        </svg>
    );
}

function BlackTeaIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.6}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M5 9h11v6a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4V9z" />
            <path d="M16 11h2a2 2 0 0 1 0 4h-2" />
            <path d="M8 6c1-1 0-2 1-3" />
            <path d="M12 6c1-1 0-2 1-3" />
        </svg>
    );
}

function ColaIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.6}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M8 4h8l-1 17a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2L8 4z" />
            <path d="M8 8h8" />
            <path d="M9 12h6" />
        </svg>
    );
}

const DRINKS: Drink[] = [
    {
        nameKey: 'drink_espresso',
        servingKey: 'drink_serving_espresso',
        ml: 30,
        mg: 63,
        Icon: EspressoIcon,
    },
    {
        nameKey: 'drink_drip_coffee',
        servingKey: 'drink_serving_drip_coffee',
        ml: 250,
        mg: 95,
        Icon: DripCoffeeIcon,
    },
    {
        nameKey: 'drink_latte',
        servingKey: 'drink_serving_latte',
        ml: 350,
        mg: 150,
        Icon: LatteIcon,
    },
    {
        nameKey: 'drink_energy_drink',
        servingKey: 'drink_serving_energy_drink',
        ml: 250,
        mg: 80,
        Icon: EnergyDrinkIcon,
    },
    {
        nameKey: 'drink_black_tea',
        servingKey: 'drink_serving_black_tea',
        ml: 250,
        mg: 47,
        Icon: BlackTeaIcon,
    },
    {
        nameKey: 'drink_cola',
        servingKey: 'drink_serving_cola',
        ml: 355,
        mg: 34,
        Icon: ColaIcon,
    },
];

export function DrinkSizeGrid({
    props,
}: {
    props: {
        limit_mg: number | null;
    };
}) {
    const { t } = useTranslation('caffeine');
    const limit = props.limit_mg ?? DEFAULT_LIMIT_MG;

    return (
        <section className="rounded-xl border border-gray-200 bg-white p-5 shadow-none dark:border-slate-700 dark:bg-slate-800">
            <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-50">
                {t('drink_grid_title', { limit })}
            </h3>
            <p className="mt-1 text-xs text-gray-500 dark:text-slate-400">
                {t('drink_grid_subtitle')}
            </p>
            <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                {DRINKS.map((drink) => {
                    const percentage = Math.min(
                        100,
                        Math.round((drink.mg / limit) * 100),
                    );

                    return (
                        <div
                            key={drink.nameKey}
                            className="flex flex-col items-center gap-2 rounded-lg border border-gray-100 p-3 text-center dark:border-slate-700"
                        >
                            <drink.Icon className="size-8 text-gray-700 dark:text-slate-300" />
                            <div>
                                <p className="text-xs font-semibold text-gray-900 dark:text-gray-50">
                                    {t(drink.nameKey)}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-slate-400">
                                    {t(drink.servingKey)} · {drink.ml} ml
                                </p>
                                <p className="text-xs font-medium text-gray-700 dark:text-slate-300">
                                    {t('drink_caffeine_amount', {
                                        mg: drink.mg,
                                    })}
                                </p>
                            </div>
                            <div className="w-full">
                                <div className="flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                                    <span>{percentage}%</span>
                                    <span>
                                        {t('drink_percentage_of_limit')}
                                    </span>
                                </div>
                                <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
                                    <div
                                        className="h-full rounded-full bg-emerald-500"
                                        style={{ width: `${percentage}%` }}
                                    />
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
