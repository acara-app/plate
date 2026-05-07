import { cn } from '@/lib/utils';
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
const MAX_ICONS = 8;

function EspressoIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.4}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M8 8 h8 l-1 10 h-6 z" />
            <path d="M9 11 h6" />
        </svg>
    );
}

function DripCoffeeIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.4}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M5 8 h12 v8 a3 3 0 0 1-3 3 H8 a3 3 0 0 1-3-3 z" />
            <path d="M17 10 h2 a2 2 0 0 1 0 4 h-2" />
            <path d="M8 5 c0 1 1 1 1 2 M11 5 c0 1 1 1 1 2 M14 5 c0 1 1 1 1 2" />
        </svg>
    );
}

function LatteIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.4}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M6 9 h12 l-1 9 a2 2 0 0 1-2 2 H9 a2 2 0 0 1-2-2 z" />
            <path d="M5 9 h14" />
        </svg>
    );
}

function EnergyDrinkIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.4}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M8 5 h8 v14 a2 2 0 0 1-2 2 h-4 a2 2 0 0 1-2-2 z" />
            <path d="M9 9 h6" />
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
            strokeWidth={1.4}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M5 11 h13 v4 a4 4 0 0 1-4 4 H9 a4 4 0 0 1-4-4 z" />
            <path d="M18 12 a2 2 0 0 1 0 4" />
        </svg>
    );
}

function ColaIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.4}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden={true}
            {...props}
        >
            <path d="M10 3 h4 v4 l2 4 v9 a2 2 0 0 1-2 2 h-4 a2 2 0 0 1-2-2 v-9 l2-4 z" />
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
        <section className="rounded-none border border-[#D9CFBC] bg-[#F2EBDD] p-6">
            <h3 className="text-2xl leading-tight font-bold tracking-[-0.02em] text-[#1A1814]">
                {t('drink_grid_title', { limit })}
            </h3>
            <p className="mt-2 max-w-md text-sm leading-relaxed text-[#3D3833]">
                {t('drink_grid_subtitle')}
            </p>
            <div className="mt-6 grid grid-cols-2 gap-0 border-t border-l border-[#D9CFBC] sm:grid-cols-3">
                {DRINKS.map((drink) => {
                    const servings = drink.mg > 0 ? limit / drink.mg : 0;
                    const whole = Math.floor(servings);
                    const frac = servings - whole;
                    const total = Math.min(
                        MAX_ICONS,
                        Math.max(1, Math.round(servings)),
                    );
                    const display =
                        servings >= 10
                            ? Math.round(servings).toString()
                            : servings.toFixed(1);

                    return (
                        <div
                            key={drink.nameKey}
                            className="flex flex-col gap-4 border-r border-b border-[#D9CFBC] bg-[#F2EBDD] p-5 transition-colors hover:bg-[#EBE2D0]"
                        >
                            <div className="flex min-h-[28px] flex-wrap items-end gap-1">
                                {Array.from({ length: total }).map((_, j) => {
                                    const isFilled = j < whole;
                                    const isPartial =
                                        j === whole && frac > 0.1 && j < total;
                                    return (
                                        <drink.Icon
                                            key={j}
                                            className={cn(
                                                'size-[22px]',
                                                isFilled
                                                    ? 'text-[#6B3F1D] opacity-100'
                                                    : isPartial
                                                      ? 'text-[#6B3F1D] opacity-40'
                                                      : 'text-[#3D3833] opacity-20',
                                            )}
                                            aria-hidden={true}
                                        />
                                    );
                                })}
                            </div>
                            <div>
                                <div className="text-3xl leading-none font-bold tracking-[-0.02em] text-[#1A1814] tabular-nums">
                                    {display}
                                    <span className="ml-1.5 font-mono text-xs tracking-[0.1em] text-[#6E665C]">
                                        ×
                                    </span>
                                </div>
                                <p className="mt-2 text-sm font-medium text-[#1A1814]">
                                    {t(drink.nameKey)}
                                </p>
                                <p className="mt-1 font-mono text-[10px] tracking-[0.1em] text-[#6E665C] uppercase">
                                    {t(drink.servingKey)} · {drink.mg} mg
                                </p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
