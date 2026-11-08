import { ArrowUpRight } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export interface SourceLink {
    url: string;
    title: string;
}

const VISIBLE_SOURCES = 4;

function normalizeKey(url: string): string {
    try {
        const parsed = new URL(url);

        return `${parsed.host}${parsed.pathname}`
            .replace(/\/$/, '')
            .toLowerCase();
    } catch {
        return url.toLowerCase();
    }
}

function hostOf(url: string): string {
    try {
        return new URL(url).host.replace(/^www\./, '');
    } catch {
        return url;
    }
}

function dedupe(sources: SourceLink[]): SourceLink[] {
    const seen = new Set<string>();

    return sources.filter((source) => {
        const key = normalizeKey(source.url);

        if (seen.has(key)) {
            return false;
        }

        seen.add(key);

        return true;
    });
}

export function SourcesSection({ sources }: { sources: SourceLink[] }) {
    const { t } = useTranslation('common');
    const [expanded, setExpanded] = useState(false);
    const items = dedupe(sources);

    if (items.length === 0) {
        return null;
    }

    const visible = expanded ? items : items.slice(0, VISIBLE_SOURCES);
    const hidden = items.length - visible.length;

    return (
        <nav
            aria-label={t('chat.sources.label')}
            className="flex flex-wrap items-center gap-x-2 gap-y-2 text-[13px]"
        >
            <span className="mr-1 text-muted-foreground">
                {t('chat.sources.label')}
            </span>
            <ul className="flex min-w-0 flex-wrap gap-2">
                {visible.map((source) => {
                    const host = hostOf(source.url);
                    const title = source.title || host;

                    return (
                        <li key={source.url} className="min-w-0">
                            <a
                                href={source.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                title={title}
                                aria-label={`${title} (${host})`}
                                className="group inline-flex max-w-56 items-center gap-1 rounded-md border border-border px-2 py-1 text-foreground/80 transition-colors hover:border-foreground/20 hover:bg-muted/60 hover:text-foreground focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:focus-visible:ring-emerald-400"
                            >
                                <span className="truncate">{host}</span>
                                <ArrowUpRight
                                    className="size-3 shrink-0 text-muted-foreground transition-colors group-hover:text-foreground"
                                    aria-hidden="true"
                                />
                            </a>
                        </li>
                    );
                })}
            </ul>
            {items.length > VISIBLE_SOURCES && (
                <button
                    type="button"
                    onClick={() => setExpanded((value) => !value)}
                    aria-expanded={expanded}
                    className="cursor-pointer rounded-md px-1.5 py-1 text-muted-foreground underline-offset-4 transition-colors hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:focus-visible:ring-emerald-400"
                >
                    {expanded
                        ? t('chat.sources.show_fewer')
                        : t('chat.sources.show_all', {
                              count: items.length,
                              hidden,
                          })}
                </button>
            )}
        </nav>
    );
}
