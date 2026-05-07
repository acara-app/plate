import {
    Activity,
    AlertTriangle,
    Baby,
    BedDouble,
    Brain,
    ExternalLink,
    Flame,
    HeartPulse,
    Pill,
    ShieldCheck,
} from 'lucide-react';
import type { ComponentType } from 'react';

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
    slate: 'border-[#D9CFBC]',
};

const CONDITION_ICONS: Record<
    string,
    ComponentType<{ className?: string; 'aria-hidden'?: boolean }>
> = {
    pregnancy: Baby,
    breastfeeding: Baby,
    trying_to_conceive: Baby,
    heart_condition: HeartPulse,
    medication: Pill,
    anxiety: Brain,
    insomnia: BedDouble,
    gerd: Flame,
};

function iconFor(condition: string, tone: Tone) {
    if (CONDITION_ICONS[condition]) {
        return CONDITION_ICONS[condition];
    }
    if (tone === 'amber' || tone === 'red') {
        return AlertTriangle;
    }
    if (tone === 'green') {
        return ShieldCheck;
    }
    return Activity;
}

export function ConditionCard({
    props,
}: {
    props: {
        condition: string;
        title: string;
        body: string;
        tone: Tone;
        link_url?: string | null;
        link_label?: string | null;
    };
}) {
    const Icon = iconFor(props.condition, props.tone);
    const accent = TONE_ACCENT[props.tone] ?? TONE_ACCENT.slate;
    const rule = TONE_RULE[props.tone] ?? TONE_RULE.slate;

    return (
        <section
            className={`rounded-none border-l-2 border-y border-r border-y-[#D9CFBC] border-r-[#D9CFBC] bg-[#EBE2D0] p-5 ${rule}`}
        >
            <div className="flex gap-3">
                <Icon
                    className={`mt-1 size-5 shrink-0 ${accent}`}
                    aria-hidden={true}
                />
                <div className="flex-1">
                    <h3 className="font-bold text-lg leading-tight text-[#1A1814]">
                        {props.title}
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-[#3D3833]">
                        {props.body}
                    </p>
                    {props.link_url ? (
                        <a
                            href={props.link_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={`mt-3 inline-flex items-center gap-1 font-mono text-[11px] tracking-[0.14em] uppercase underline-offset-4 hover:underline ${accent}`}
                        >
                            {props.link_label ?? 'Learn more'}
                            <ExternalLink
                                className="size-3.5"
                                aria-hidden={true}
                            />
                        </a>
                    ) : null}
                </div>
            </div>
        </section>
    );
}
