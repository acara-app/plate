import { BookOpen, ExternalLink, Globe } from 'lucide-react';
import { CollapsibleSection } from './collapsible-section';

export interface SourceLink {
    url: string;
    title: string;
}

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
    const items = dedupe(sources);

    if (items.length === 0) {
        return null;
    }

    const label = `Sources (${items.length})`;

    return (
        <CollapsibleSection
            icon={<BookOpen className="size-3.5 shrink-0" aria-hidden="true" />}
            label={label}
            preview={
                <div className="flex gap-2 overflow-x-auto px-3 pb-2">
                    {items.map((source) => (
                        <a
                            key={source.url}
                            href={source.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex max-w-48 shrink-0 items-center gap-1 rounded-full border border-border/60 px-2 py-1 text-xs text-muted-foreground transition-colors outline-none hover:bg-muted/60 focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <Globe
                                className="size-3 shrink-0"
                                aria-hidden="true"
                            />
                            <span className="truncate">
                                {source.title || hostOf(source.url)}
                            </span>
                        </a>
                    ))}
                </div>
            }
        >
            <div className="space-y-1 px-3 pb-2">
                {items.map((source) => (
                    <a
                        key={source.url}
                        href={source.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-start gap-2 rounded-md px-2 py-1 text-xs transition-colors outline-none hover:bg-muted/60 focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <ExternalLink
                            className="mt-0.5 size-3.5 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span className="min-w-0">
                            <span className="block truncate font-medium text-foreground">
                                {source.title || hostOf(source.url)}
                            </span>
                            <span className="block truncate text-muted-foreground">
                                {hostOf(source.url)}
                            </span>
                        </span>
                    </a>
                ))}
            </div>
        </CollapsibleSection>
    );
}
