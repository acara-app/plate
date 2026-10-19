import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

export const BOTTOM_SHEET_CONTENT_CLASS =
    'mx-auto max-h-[90dvh] w-full max-w-xl gap-0 overflow-y-auto overscroll-contain rounded-t-3xl border-x p-0 [&>button:last-child]:top-3 [&>button:last-child]:right-3 [&>button:last-child]:flex [&>button:last-child]:size-11 [&>button:last-child]:items-center [&>button:last-child]:justify-center [&>button:last-child]:rounded-full';

export default function BottomSheet({
    open,
    onClose,
    title,
    description,
    children,
}: {
    open: boolean;
    onClose: () => void;
    title: string;
    description: string;
    children: ReactNode;
}) {
    const { t } = useTranslation('common');

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent
                side="bottom"
                closeLabel={t('health_entries.timeline.close')}
                className={BOTTOM_SHEET_CONTENT_CLASS}
            >
                <SheetHeader className="px-5 pt-6 pr-16 pb-4">
                    <SheetTitle className="text-xl">{title}</SheetTitle>
                    <SheetDescription>{description}</SheetDescription>
                </SheetHeader>
                {children}
            </SheetContent>
        </Sheet>
    );
}
