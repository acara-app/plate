import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import {
    ChevronDown,
    Loader2,
    MessageSquare,
    Plus,
    Send,
    UtensilsCrossed,
} from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export const AI_MODELS = {
    'gemini-3-flash-preview': 'chat.models.gemini_3_flash',
} as const;

export type AIModel = keyof typeof AI_MODELS;

export const CHAT_MODES = {
    ask: {
        label: 'chat.modes.ask',
        icon: MessageSquare,
    },
    'generate-meal-plan': {
        label: 'chat.modes.meal_plan',
        icon: UtensilsCrossed,
    },
} as const;

export type ChatMode = keyof typeof CHAT_MODES;

interface Props {
    onSubmit: (message: string, mode: ChatMode, model: AIModel) => void;
    className?: string;
    disabled?: boolean;
    isLoading?: boolean;
    mode: ChatMode;
}

export default function ChatInput({
    className,
    onSubmit,
    disabled = false,
    isLoading = false,
    mode,
}: Props) {
    const { t } = useTranslation('common');
    const [message, setMessage] = useState('');
    const [selectedMode, setSelectedMode] = useState<ChatMode>(mode || 'ask');
    const [selectedModel, setSelectedModel] = useState<AIModel>(
        'gemini-3-flash-preview',
    );

    const handleSubmit = () => {
        if (message.trim()) {
            onSubmit(message, selectedMode, selectedModel);
            setMessage('');
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit();
        }
    };

    const SelectedModeIcon = CHAT_MODES[selectedMode].icon;

    return (
        <div className="mx-auto flex w-full max-w-3xl items-end bg-background p-0.5 md:px-4 md:py-4">
            <div
                className={cn(
                    'w-full rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900',
                    className,
                )}
            >
                <div className="p-2">
                    <textarea
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder={t('chat.placeholder')}
                        disabled={disabled}
                        className="min-h-10 w-full resize-y bg-transparent text-base text-foreground placeholder:text-muted-foreground focus:outline-none disabled:opacity-50"
                        rows={2}
                    />
                </div>
                <div className="flex items-center justify-between px-2 pb-2">
                    <div className="flex items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    className="gap-1.5"
                                >
                                    <SelectedModeIcon className="size-4" />
                                    {selectedMode !== 'generate-meal-plan' && (
                                        <span>
                                            {t(CHAT_MODES[selectedMode].label)}
                                        </span>
                                    )}
                                    <ChevronDown className="size-3.5 opacity-60" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" className="w-48">
                                {Object.entries(CHAT_MODES).map(
                                    ([key, { label, icon: Icon }]) => (
                                        <DropdownMenuItem
                                            key={key}
                                            onClick={() =>
                                                setSelectedMode(key as ChatMode)
                                            }
                                            className={cn(
                                                'gap-2',
                                                selectedMode === key &&
                                                    'bg-accent text-accent-foreground',
                                            )}
                                        >
                                            <Icon className="size-4" />
                                            <span>{t(label)}</span>
                                        </DropdownMenuItem>
                                    ),
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            variant="outline"
                            size="icon"
                            className="size-8"
                            disabled
                        >
                            <Plus className="size-4" />
                        </Button>
                    </div>

                    <div className="flex items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="gap-1.5 text-muted-foreground hover:text-foreground"
                                >
                                    <span className="max-w-[80px] truncate sm:max-w-[90px]">
                                        {t(AI_MODELS[selectedModel])}
                                    </span>
                                    <ChevronDown className="size-3.5 opacity-60" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                {Object.entries(AI_MODELS).map(
                                    ([key, label]) => (
                                        <DropdownMenuItem
                                            key={key}
                                            onClick={() =>
                                                setSelectedModel(key as AIModel)
                                            }
                                            className={cn(
                                                selectedModel === key &&
                                                    'bg-accent text-accent-foreground',
                                            )}
                                        >
                                            <span>{t(label)}</span>
                                        </DropdownMenuItem>
                                    ),
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            variant={
                                message.trim() && !disabled
                                    ? 'default'
                                    : 'ghost'
                            }
                            size="icon"
                            className={`size-8 transition-all duration-200 ${
                                message.trim() && !disabled
                                    ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                            onClick={handleSubmit}
                            disabled={!message.trim() || disabled}
                        >
                            {isLoading ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Send className="size-4" />
                            )}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
