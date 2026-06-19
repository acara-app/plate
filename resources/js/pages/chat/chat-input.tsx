import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn, generateUUID } from '@/lib/utils';
import type { FileUIPart } from 'ai';
import { Paperclip, Send, Square, X } from 'lucide-react';
import {
    forwardRef,
    useEffect,
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

function FilePreview({
    file,
    onRemove,
}: {
    file: FileUIPart;
    onRemove: () => void;
}) {
    return (
        <div className="group relative duration-200 animate-in fade-in-0 zoom-in-95">
            <img
                src={file.url}
                alt={file.filename ?? 'Attached image'}
                className="size-16 rounded-xl border border-border/60 object-cover shadow-sm transition-shadow group-hover:shadow-md sm:size-20"
            />
            <button
                type="button"
                onClick={onRemove}
                aria-label={`Remove ${file.filename ?? 'attached image'}`}
                className="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full border-2 border-background bg-destructive text-white shadow-md transition-all duration-200 hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring active:scale-95"
            >
                <X className="size-3.5" strokeWidth={2.5} />
            </button>
        </div>
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
    const [message, setMessage] = useState(initialMessage ?? '');
    const [selectedFiles, setSelectedFiles] = useState<
        { id: string; part: FileUIPart }[]
    >([]);
    const [isFocused, setIsFocused] = useState(false);
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

    const hasContent = message.trim() || selectedFiles.length > 0;

    useEffect(() => {
        const textarea = textareaRef.current;
        if (!textarea) {
            return;
        }
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 240)}px`;
    }, [message]);

    const handleSubmit = () => {
        if (!hasContent) {
            return;
        }

        const text =
            message.trim() ||
            (selectedFiles.length > 0 ? 'Analyze this image' : '');
        const fileParts = selectedFiles.map((file) => file.part);
        onSubmit(text, fileParts.length > 0 ? fileParts : undefined);
        setMessage('');
        setSelectedFiles([]);
        if (textareaRef.current) {
            textareaRef.current.style.height = 'auto';
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit();
        }
    };

    const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = e.target.files;
        if (!files || files.length === 0) {
            return;
        }

        const fileParts = await Promise.all(
            Array.from(files).map(readFileAsDataURL),
        );
        setSelectedFiles((prev) => [
            ...prev,
            ...fileParts.map((part) => ({ id: generateUUID(), part })),
        ]);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeFile = (id: string) => {
        setSelectedFiles((prev) => prev.filter((file) => file.id !== id));
    };

    return (
        <div
            role="group"
            aria-label="Message composer"
            className={cn(
                'mx-auto flex w-full items-end px-2 py-3 sm:px-4 sm:py-4',
                className,
            )}
        >
            <div className="flex w-full flex-col gap-2">
                {selectedFiles.length > 0 && (
                    <div
                        className="flex flex-wrap gap-2 px-2"
                        aria-label={`${selectedFiles.length} file${selectedFiles.length > 1 ? 's' : ''} attached`}
                    >
                        {selectedFiles.map((file) => (
                            <FilePreview
                                key={file.id}
                                file={file.part}
                                onRemove={() => removeFile(file.id)}
                            />
                        ))}
                    </div>
                )}

                <div
                    className={cn(
                        'flex w-full items-end rounded-3xl border bg-card shadow-md transition-all duration-200',
                        isFocused && !disabled
                            ? 'border-emerald-500/50 shadow-[0_0_0_3px_rgba(16,185,129,0.12),0_4px_20px_rgba(0,0,0,0.08)] dark:border-emerald-400/40 dark:shadow-[0_0_0_3px_rgba(52,211,153,0.08)]'
                            : 'border-border shadow-md',
                        disabled && 'opacity-60',
                    )}
                >
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/*"
                        multiple
                        onChange={handleFileSelect}
                        className="hidden"
                        aria-hidden="true"
                    />
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="m-1.5 size-11 shrink-0 rounded-xl text-muted-foreground transition-colors hover:bg-accent hover:text-foreground active:scale-95"
                                disabled={disabled}
                                onClick={() => fileInputRef.current?.click()}
                                aria-label="Attach image"
                            >
                                <Paperclip className="size-5" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent side="top">Attach image</TooltipContent>
                    </Tooltip>

                    <textarea
                        ref={textareaRef}
                        value={message}
                        onChange={(e) => {
                            setMessage(e.target.value);
                            onInputChange?.();
                        }}
                        onKeyDown={handleKeyDown}
                        onFocus={() => setIsFocused(true)}
                        onBlur={() => setIsFocused(false)}
                        placeholder={placeholder ?? t('chat.placeholder')}
                        disabled={disabled}
                        aria-label="Message input"
                        aria-describedby="chat-input-hint"
                        className="max-h-[240px] min-h-12 w-full flex-1 resize-none bg-transparent py-3.5 text-base leading-relaxed text-foreground placeholder:text-muted-foreground/60 focus:outline-none disabled:cursor-not-allowed"
                        rows={1}
                    />

                    {isLoading && onStop ? (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    type="button"
                                    variant="default"
                                    size="icon"
                                    className="m-1.5 size-11 shrink-0 rounded-xl bg-linear-to-br from-red-500 to-red-600 text-white shadow-lg shadow-red-500/25 transition-all duration-200 hover:from-red-600 hover:to-red-700 hover:shadow-red-500/30 active:scale-95"
                                    onClick={onStop}
                                    aria-label="Stop generating"
                                >
                                    <Square className="size-5 fill-current" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top">
                                Stop generating
                            </TooltipContent>
                        </Tooltip>
                    ) : (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant={
                                        hasContent && !disabled
                                            ? 'default'
                                            : 'ghost'
                                    }
                                    size="icon"
                                    className={cn(
                                        'm-1.5 size-11 shrink-0 rounded-xl transition-all duration-200 active:scale-95',
                                        hasContent && !disabled
                                            ? 'bg-linear-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-600 hover:to-emerald-700 hover:shadow-emerald-500/30'
                                            : 'text-muted-foreground',
                                    )}
                                    onClick={handleSubmit}
                                    disabled={!hasContent || disabled}
                                    aria-label="Send message"
                                >
                                    <Send className="size-5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top">
                                Send message
                            </TooltipContent>
                        </Tooltip>
                    )}
                </div>

                <span id="chat-input-hint" className="sr-only">
                    Press Enter to send, Shift+Enter for a new line
                </span>
            </div>
        </div>
    );
});

export default ChatInput;
