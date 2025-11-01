import { PropsWithChildren } from 'react';

interface SectionProps
    extends PropsWithChildren,
        React.ComponentProps<'section'> {}

export default function Section({ children, ...props }: SectionProps) {
    return <section {...props}>{children}</section>;
}
