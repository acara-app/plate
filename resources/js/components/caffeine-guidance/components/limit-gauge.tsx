import { useTranslation } from 'react-i18next';

type Tone = 'green' | 'amber' | 'red' | 'blue' | 'slate';

const TONE_ACCENT: Record<Tone, string> = {
    green: 'text-[#6F8654]',
    amber: 'text-[#B8843E]',
    red: 'text-[#B5482E]',
    blue: 'text-[#6B3F1D]',
    slate: 'text-[#3D3833]',
};

const TONE_RULE: Record<Tone, string> = {
    green: 'border-[#6F8654]',
    amber: 'border-[#B8843E]',
    red: 'border-[#B5482E]',
    blue: 'border-[#6B3F1D]',
    slate: 'border-[#3D3833]',
};

export function LimitGauge({
    props,
}: {
    props: {
        label: string;
        value_label: string;
        limit_mg: number | null;
        max_mg: number;
        tone: Tone;
        caption: string;
    };
}) {
    const { t } = useTranslation('caffeine');

    const percentage =
        props.limit_mg === null || props.max_mg <= 0
            ? 0
            : Math.round((props.limit_mg / props.max_mg) * 100);

    const limitDisplay = props.limit_mg === null ? '0' : String(props.limit_mg);
    const accent = TONE_ACCENT[props.tone] ?? TONE_ACCENT.slate;
    const rule = TONE_RULE[props.tone] ?? TONE_RULE.slate;

    return (
        <section className="rounded-none border border-[#D9CFBC] bg-[#F2EBDD] p-6">
            <h3 className="font-mono text-[11px] tracking-[0.18em] text-[#6E665C] uppercase">
                {props.label}
            </h3>
            <p className="mt-2 text-sm leading-relaxed text-[#3D3833]">
                {props.caption}
            </p>

            <div className="mt-5 grid grid-cols-1 gap-0 border-y border-[#D9CFBC] sm:grid-cols-2 sm:divide-x sm:divide-[#D9CFBC]">
                {/* Your daily limit */}
                <div className={`border-l-2 px-5 py-6 text-left ${rule}`}>
                    <div
                        className={`font-bold text-5xl leading-none tracking-[-0.04em] tabular-nums sm:text-6xl ${accent}`}
                    >
                        {limitDisplay}
                    </div>
                    <div className="mt-2 font-mono text-[10px] tracking-[0.18em] text-[#6E665C] uppercase">
                        mg / day
                    </div>
                    <div className="mt-3 font-mono text-[11px] tracking-[0.14em] text-[#1A1814] uppercase">
                        {t('gauge_your_limit')}
                    </div>
                </div>

                {/* General adult maximum */}
                <div className="px-5 py-6 text-left">
                    <div className="font-bold text-5xl leading-none tracking-[-0.04em] text-[#3D3833] tabular-nums sm:text-6xl">
                        {props.max_mg}
                    </div>
                    <div className="mt-2 font-mono text-[10px] tracking-[0.18em] text-[#6E665C] uppercase">
                        mg / day
                    </div>
                    <div className="mt-3 font-mono text-[11px] tracking-[0.14em] text-[#6E665C] uppercase">
                        {t('gauge_general_max')}
                    </div>
                </div>
            </div>

            <p className="mt-5 text-sm leading-relaxed text-[#3D3833]">
                {props.limit_mg === null || percentage === 0
                    ? t('gauge_avoid')
                    : t('gauge_comparison', { percentage })}
            </p>
        </section>
    );
}
