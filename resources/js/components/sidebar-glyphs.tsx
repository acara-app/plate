import { cn } from '@/lib/utils';
import { type SVGAttributes } from 'react';

type GlyphProps = SVGAttributes<SVGSVGElement>;

const FRESH =
    'fill-emerald-500 stroke-none transition-transform duration-200 ease-out motion-reduce:transition-none dark:fill-emerald-400';

function Glyph({ className, children, ...props }: GlyphProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.75}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className={cn('size-6 overflow-visible md:size-5', className)}
            {...props}
        >
            {children}
        </svg>
    );
}

export function SproutMenuGlyph(props: GlyphProps) {
    return (
        <Glyph {...props}>
            <path d="M4 9.25h5M4 14.25h16M4 19.25h16" />
            <path
                d="M11.2 10.9C10.6 6.1 14.2 2.9 20.9 2.9c.4 5.6-3.3 9.1-9.7 8Z"
                className={cn(
                    FRESH,
                    'origin-[47%_45%] group-hover/glyph:scale-110 group-hover/glyph:-rotate-6',
                )}
            />
        </Glyph>
    );
}

export function TuckSidebarGlyph(props: GlyphProps) {
    return (
        <Glyph {...props}>
            <path
                d="M7.5 4h2v16h-2A4.5 4.5 0 0 1 3 15.5v-7A4.5 4.5 0 0 1 7.5 4Z"
                className={cn(
                    FRESH,
                    'origin-[12.5%_50%] group-hover/glyph:scale-x-50',
                )}
            />
            <rect x="3" y="4" width="18" height="16" rx="4.5" />
            <path
                d="M16.6 9 13.6 12l3 3"
                className="transition-transform duration-200 ease-out group-hover/glyph:-translate-x-[1.5px] motion-reduce:transition-none"
            />
        </Glyph>
    );
}
