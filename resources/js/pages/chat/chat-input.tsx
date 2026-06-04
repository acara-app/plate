import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { FileUIPart } from 'ai';
import { Plus, Send, Square, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface Props {
    onSubmit: (message: string, files?: FileUIPart[]) => void;
    onStop?: () => void;
    onInputChange?: () => void;
    className?: string;
    disabled?: boolean;
    initialMessage?: string | null;
    isLoading?: boolean;
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

export default function ChatInput({
    className,
    onSubmit,
    onStop,
    onInputChange,
    disabled = false,
    initialMessage = null,
    isLoading = false,
}: Props) {
    const { t } = useTranslation('common');
    const [message, setMessage] = useState(initialMessage ?? '');
    const [selectedFiles, setSelectedFiles] = useState<FileUIPart[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const hasContent = message.trim() || selectedFiles.length > 0;

    const handleSubmit = () => {
        if (!hasContent) {
            return;
        }

        const text =
            message.trim() ||
            (selectedFiles.length > 0 ? 'Analyze this image' : '');
        onSubmit(text, selectedFiles.length > 0 ? selectedFiles : undefined);
        setMessage('');
        setSelectedFiles([]);
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
        setSelectedFiles((prev) => [...prev, ...fileParts]);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeFile = (index: number) => {
        setSelectedFiles((prev) => prev.filter((_, i) => i !== index));
    };

    return (
        <div className="mx-auto flex w-full max-w-3xl items-end bg-background p-0.5 md:px-4 md:py-2">
            <div
                className={cn(
                    'w-full rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900',
                    className,
                )}
            >
                {selectedFiles.length > 0 && (
                    <div className="flex flex-wrap gap-2 px-2 pt-2 sm:px-3 sm:pt-3">
                        {selectedFiles.map((file, index) => (
                            <div key={index} className="group relative">
                                <img
                                    src={file.url}
                                    alt={file.filename ?? 'Selected image'}
                                    className="size-16 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                                />
                                <button
                                    type="button"
                                    onClick={() => removeFile(index)}
                                    className="absolute -top-1.5 -right-1.5 flex size-5 items-center justify-center rounded-full bg-gray-800 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <X className="size-3" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                <div className="p-2 sm:p-3">
                    <textarea
                        value={message}
                        onChange={(e) => {
                            setMessage(e.target.value);
                            onInputChange?.();
                        }}
                        onKeyDown={handleKeyDown}
                        placeholder={t('chat.placeholder')}
                        disabled={disabled}
                        className="w-full resize-y bg-transparent text-base text-foreground placeholder:text-muted-foreground focus:outline-none disabled:opacity-50"
                        rows={1}
                    />
                </div>
                <div className="flex items-center justify-between px-2 pb-2">
                    <div className="flex items-center gap-2">
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept="image/*"
                            multiple
                            onChange={handleFileSelect}
                            className="hidden"
                        />
                        <Button
                            variant="outline"
                            size="icon"
                            className="size-8"
                            disabled={disabled}
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <Plus className="size-4" />
                        </Button>
                    </div>

                    <div className="flex items-center gap-1">
                        {isLoading && onStop ? (
                            <Button
                                type="button"
                                variant="default"
                                size="icon"
                                className="size-9 bg-emerald-600 text-white transition-all duration-200 hover:bg-emerald-700"
                                onClick={onStop}
                                aria-label={t('chat.stop')}
                            >
                                <Square className="size-4 fill-current" />
                            </Button>
                        ) : (
                            <Button
                                variant={
                                    hasContent && !disabled
                                        ? 'default'
                                        : 'ghost'
                                }
                                size="icon"
                                className={`size-9 transition-all duration-200 ${
                                    hasContent && !disabled
                                        ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                                onClick={handleSubmit}
                                disabled={!hasContent || disabled}
                            >
                                <Send className="size-4" />
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
