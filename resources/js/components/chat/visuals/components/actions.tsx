import { cn } from '@/lib/utils';
import { useStateStore } from '@json-render/react';
import { ArrowRight, ExternalLink } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { pressActions, runPress } from '../visual-actions';
import { canSend, runVisualAction, useVisualSession } from '../visual-session';
import type { ViewProps } from './props';

const PRESSABLE =
    'inline-flex min-h-11 cursor-pointer touch-manipulation items-center gap-2 rounded-full text-[15px] select-none [-webkit-tap-highlight-color:transparent] transition-[scale,background-color] duration-150 ease-out active:scale-[0.97] focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none aria-disabled:cursor-not-allowed aria-disabled:opacity-50 aria-disabled:active:scale-100 dark:focus-visible:ring-emerald-400';

function Trailing({ action }: { action: string }) {
    const { t } = useTranslation('common');

    if (action === 'openLink') {
        return (
            <>
                <ExternalLink className="size-4 shrink-0" aria-hidden="true" />
                <span className="sr-only">{t('chat.visual.new_tab')}</span>
            </>
        );
    }

    return action === 'openDestination' ? (
        <ArrowRight className="size-4 shrink-0" aria-hidden="true" />
    ) : null;
}

export function VisualButton({ label, on }: { label: string; on: unknown }) {
    const session = useVisualSession();
    const store = useStateStore();
    const actions = pressActions(on);
    const unavailable = actions.includes('sendMessage') && !canSend(session);

    return (
        <button
            type="button"
            aria-disabled={unavailable || undefined}
            onClick={() => {
                if (!unavailable) {
                    runPress(on, store, session);
                }
            }}
            className={cn(
                PRESSABLE,
                'w-fit px-5 font-semibold text-white hover:bg-emerald-800 aria-disabled:hover:bg-emerald-700 dark:text-emerald-950 dark:hover:bg-emerald-400 dark:aria-disabled:hover:bg-emerald-500',
                'bg-emerald-700 dark:bg-emerald-500',
            )}
        >
            {label}
            <Trailing
                action={
                    actions.find(
                        (action) =>
                            action === 'openLink' ||
                            action === 'openDestination',
                    ) ?? ''
                }
            />
        </button>
    );
}

export function SuggestionChips({ props }: ViewProps<'SuggestionChips'>) {
    const { t } = useTranslation('common');
    const session = useVisualSession();

    return (
        <ul
            aria-label={t('chat.visual.suggestions')}
            className="flex flex-wrap gap-2"
        >
            {props.items.map((item, index) => {
                const { action, params } = item.action;
                const unavailable =
                    action === 'sendMessage' && !canSend(session);

                return (
                    <li key={index} className="flex max-w-full">
                        <button
                            type="button"
                            aria-disabled={unavailable || undefined}
                            onClick={() => {
                                if (!unavailable) {
                                    runVisualAction(session, action, params);
                                }
                            }}
                            className={cn(
                                PRESSABLE,
                                'max-w-full bg-foreground/[0.06] px-4 py-2 text-left leading-5 font-medium text-foreground/90 hover:bg-foreground/10 aria-disabled:hover:bg-foreground/[0.06] dark:bg-white/[0.08] dark:hover:bg-white/[0.12] dark:aria-disabled:hover:bg-white/[0.08]',
                            )}
                        >
                            <span className="[overflow-wrap:anywhere]">
                                {item.label}
                            </span>
                            <Trailing action={action} />
                        </button>
                    </li>
                );
            })}
        </ul>
    );
}
