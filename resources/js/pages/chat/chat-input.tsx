import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn, generateUUID } from '@/lib/utils';
import type { FileUIPart } from 'ai';
import { ArrowUp, ImagePlus, Square, X } from 'lucide-react';
import {
    forwardRef,
    useEffect,
    useId,
    useImperativeHandle,
    useRef,
    useState,
} from 'react';
import { useTranslation } from 'react-i18next';

interface Props {
    onSubmit: (message: string, files?: FileUIPart[]) => void;
    onStop?: () => void;
    onInputChange?: () => void;
    className?: string;
    disabled?: boolean;
    initialMessage?: string | null;
    isLoading?: boolean;
    placeholder?: string;
}

export interface ChatInputHandle {
    setMessage: (text: string) => void;
    focus: () => void;
}

const MAX_TEXTAREA_HEIGHT = 240;

const FOCUS_RING =
    'focus-visible:ring-2 focus-visible:ring-emerald-600 dark:focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';

function readFileAsDataURL(file: File): Promise<FileUIPart> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            resolve({
                type: 'file',
                mediaType: file.type,
                url: reader.result as string,
                filename: file.name,
            });
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

function PhotoPreview({
    file,
    onRemove,
}: {
    file: FileUIPart;
    onRemove: () => void;
}) {
    const { t } = useTranslation('common');
    const name = file.filename ?? t('chat.attachment.photo');

    return (
        <li className="relative duration-200 motion-safe:animate-in motion-safe:fade-in-0 motion-safe:zoom-in-95">
            <img
                src={file.url}
                alt={name}
                className="size-16 rounded-lg border border-border object-cover"
            />
            <button
                type="button"
                onClick={onRemove}
                aria-label={t('chat.composer.remove_photo', { name })}
                className={cn(
                    'absolute -top-1.5 -right-1.5 flex size-6 cursor-pointer items-center justify-center rounded-full bg-foreground text-background shadow-xs transition-transform hover:scale-105 active:scale-95',
                    FOCUS_RING,
                )}
            >
                <X className="size-3.5" strokeWidth={2.25} aria-hidden="true" />
            </button>
        </li>
    );
}

const ChatInput = forwardRef<ChatInputHandle, Props>(function ChatInput(
    {
        className,
        onSubmit,
        onStop,
        onInputChange,
        disabled = false,
        initialMessage = null,
        isLoading = false,
        placeholder,
    },
    ref,
) {
    const { t } = useTranslation('common');
    const hintId = useId();
    const [message, setMessage] = useState(initialMessage ?? '');
    const [selectedFiles, setSelectedFiles] = useState<
        { id: string; part: FileUIPart }[]
    >([]);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    useImperativeHandle(
        ref,
        () => ({
            setMessage: (text: string) => setMessage(text),
            focus: () => textareaRef.current?.focus(),
        }),
        [],
    );

    const hasContent = message.trim() !== '' || selectedFiles.length > 0;
    const canSend = hasContent && !disabled && !isLoading;
    const showStop = isLoading && onStop !== undefined;

    useEffect(() => {
        const textarea = textareaRef.current;

        if (!textarea) {
            return;
        }

        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, MAX_TEXTAREA_HEIGHT)}px`;
    }, [message]);

    const handleSubmit = () => {
        if (!canSend) {
            return;
        }

        const text =
            message.trim() ||
            (selectedFiles.length > 0 ? t('chat.composer.photo_prompt') : '');
        const fileParts = selectedFiles.map((file) => file.part);

        onSubmit(text, fileParts.length > 0 ? fileParts : undefined);
        setMessage('');
        setSelectedFiles([]);
    };

    const handleKeyDown = (event: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (
            event.key === 'Enter' &&
            !event.shiftKey &&
            !event.nativeEvent.isComposing
        ) {
            event.preventDefault();
            handleSubmit();
        }
    };

    const handleFileSelect = async (
        event: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const files = event.target.files;

        if (!files || files.length === 0) {
            return;
        }

        const fileParts = await Promise.all(
            Array.from(files).map(readFileAsDataURL),
        );

        setSelectedFiles((previous) => [
            ...previous,
            ...fileParts.map((part) => ({ id: generateUUID(), part })),
        ]);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }

        textareaRef.current?.focus();
    };

    const removeFile = (id: string) => {
        setSelectedFiles((previous) =>
            previous.filter((file) => file.id !== id),
        );
        textareaRef.current?.focus();
    };

    return (
        <div
            role="group"
            aria-label={t('chat.composer.label')}
            className={cn('w-full', className)}
        >
            <div
                className={cn(
                    'rounded-2xl border border-border bg-background shadow-xs transition-[border-color,box-shadow] duration-150 focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/40 dark:bg-muted/30',
                    disabled && 'opacity-60',
                )}
            >
                {selectedFiles.length > 0 && (
                    <ul
                        aria-label={t('chat.composer.attached', {
                            count: selectedFiles.length,
                        })}
                        className="flex flex-wrap gap-3 px-3 pt-3"
                    >
                        {selectedFiles.map((file) => (
                            <PhotoPreview
                                key={file.id}
                                file={file.part}
                                onRemove={() => removeFile(file.id)}
                            />
                        ))}
                    </ul>
                )}

                <textarea
                    ref={textareaRef}
                    value={message}
                    onChange={(event) => {
                        setMessage(event.target.value);
                        onInputChange?.();
                    }}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder ?? t('chat.placeholder')}
                    disabled={disabled}
                    aria-label={t('chat.composer.label')}
                    aria-describedby={hintId}
                    rows={1}
                    className="block max-h-60 min-h-13 w-full resize-none bg-transparent px-4 pt-3.5 pb-1 text-base leading-6 text-foreground placeholder:text-muted-foreground/80 focus:outline-none disabled:cursor-not-allowed"
                />

                <div className="flex items-center justify-between gap-2 px-2 pb-2">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/*"
                        multiple
                        onChange={handleFileSelect}
                        className="hidden"
                        tabIndex={-1}
                        aria-hidden="true"
                    />
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button
                                type="button"
                                disabled={disabled}
                                onClick={() => fileInputRef.current?.click()}
                                aria-label={t('chat.composer.add_photo')}
                                className={cn(
                                    'inline-flex size-9 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50',
                                    FOCUS_RING,
                                )}
                            >
                                <ImagePlus
                                    className="size-[18px]"
                                    strokeWidth={1.75}
                                    aria-hidden="true"
                                />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent side="top">
                            {t('chat.composer.add_photo')}
                        </TooltipContent>
                    </Tooltip>

                    {showStop ? (
                        <button
                            type="button"
                            onClick={onStop}
                            aria-label={t('chat.composer.stop')}
                            className={cn(
                                'inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full bg-foreground pr-3.5 pl-3 text-sm font-medium text-background transition-colors hover:bg-foreground/85',
                                FOCUS_RING,
                            )}
                        >
                            <Square
                                className="size-3 fill-current"
                                aria-hidden="true"
                            />
                            {t('chat.stop')}
                        </button>
                    ) : (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <button
                                    type="button"
                                    onClick={handleSubmit}
                                    disabled={!canSend}
                                    aria-label={t('chat.composer.send')}
                                    className={cn(
                                        'inline-flex size-9 items-center justify-center rounded-full transition-colors',
                                        FOCUS_RING,
                                        canSend
                                            ? 'cursor-pointer bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400'
                                            : 'cursor-not-allowed bg-muted text-muted-foreground/70',
                                    )}
                                >
                                    <ArrowUp
                                        className="size-[18px]"
                                        strokeWidth={2.25}
                                        aria-hidden="true"
                                    />
                                </button>
                            </TooltipTrigger>
                            <TooltipContent side="top">
                                {t('chat.composer.send')}
                            </TooltipContent>
                        </Tooltip>
                    )}
                </div>
            </div>

            <span id={hintId} className="sr-only">
                {t('chat.composer.hint')}
            </span>
        </div>
    );
});

export default ChatInput;
