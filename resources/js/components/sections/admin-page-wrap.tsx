import React from 'react';

interface Props {
    children: React.ReactNode;
    variant?: 'lg' | 'full' | 'md' | 'sm';
}
export default function AdminPageWrap({ children, variant = 'sm' }: Props) {
    function getContainerClass() {
        switch (variant) {
            case 'lg':
                return 'max-w-7xl';
            case 'full':
                return 'max-w-full';
            case 'md':
                return 'max-w-4xl';
            case 'sm':
                return 'max-w-2xl';
            default:
                return 'max-w-4xl';
        }
    }
    return (
        <section className="px-6 py-6">
            <div
                className={`mx-auto grid ${getContainerClass()} grid-cols-1 gap-6`}
            >
                {children}
            </div>
        </section>
    );
}
