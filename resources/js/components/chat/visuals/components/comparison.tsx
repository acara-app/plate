import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { ViewProps } from './props';
import { useVisualFormat } from './use-visual-format';

export function Comparison({ props }: ViewProps<'Comparison'>) {
    const { t } = useTranslation('common');
    const format = useVisualFormat();

    return (
        <div className="-mx-1 overflow-x-auto px-1">
            <table className="w-full text-left text-[15px] leading-6">
                <thead>
                    <tr className="border-b border-foreground/10">
                        <td className="pb-2" />
                        {props.columns.map((column, index) => (
                            <th
                                key={`${index}-${column}`}
                                scope="col"
                                className="px-3 pb-2 align-bottom text-[13px] leading-5 font-medium text-muted-foreground last:pr-0"
                            >
                                <span
                                    className={cn(
                                        props.winner === index &&
                                            'text-foreground',
                                    )}
                                >
                                    {column}
                                </span>
                                {props.winner === index && (
                                    <span className="mt-1 flex w-fit items-center gap-1 rounded-full bg-emerald-600/10 px-2 py-0.5 text-[11px] leading-4 font-medium text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300">
                                        <Check
                                            className="size-3 shrink-0"
                                            aria-hidden="true"
                                        />
                                        {t('chat.visual.better_choice')}
                                    </span>
                                )}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-foreground/10">
                    {props.rows.map((row, index) => (
                        <tr key={`${index}-${row.label}`}>
                            <th
                                scope="row"
                                className="py-2.5 pr-3 align-top text-[13px] leading-6 font-normal text-muted-foreground"
                            >
                                {row.label}
                            </th>
                            {row.values.map((value, column) => (
                                <td
                                    key={column}
                                    className={cn(
                                        'px-3 py-2.5 align-top tabular-nums last:pr-0',
                                        props.winner === column
                                            ? 'font-semibold text-foreground'
                                            : 'text-foreground/80',
                                    )}
                                >
                                    {format.quantity(value)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
