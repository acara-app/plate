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

const TONE_CLASSES: Record<Tone, string> = {
    green: 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-50',
    amber: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-50',
    red: 'border-red-200 bg-red-50 text-red-950 dark:border-red-900 dark:bg-red-950/40 dark:text-red-50',
    blue: 'border-sky-200 bg-sky-50 text-sky-950 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-50',
    slate: 'border-slate-200 bg-slate-50 text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50',
};

const TONE_ICON_CLASSES: Record<Tone, string> = {
    green: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    red: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
    blue: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
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
    const wrapper = TONE_CLASSES[props.tone] ?? TONE_CLASSES.slate;
    const iconWrapper =
        TONE_ICON_CLASSES[props.tone] ?? TONE_ICON_CLASSES.slate;

    return (
        <section className={`rounded-xl border p-4 ${wrapper}`}>
            <div className="flex gap-3">
                <span
                    className={`mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full ${iconWrapper}`}
                >
                    <Icon className="size-5" aria-hidden={true} />
                </span>
                <div className="flex-1">
                    <h3 className="text-sm font-semibold">{props.title}</h3>
                    <p className="mt-1 text-sm leading-relaxed opacity-85">
                        {props.body}
                    </p>
                    {props.link_url ? (
                        <a
                            href={props.link_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="mt-2 inline-flex items-center gap-1 text-sm font-semibold underline-offset-4 hover:underline"
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
