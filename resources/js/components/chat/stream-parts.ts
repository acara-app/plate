import type {
    ProviderToolData,
    ReasoningData,
    ToolCallData,
} from '@/types/chat';
import type { UIMessage } from '@ai-sdk/react';

type Part = UIMessage['parts'][number];

function dataOfType<T>(part: Part, type: string): T | null {
    if (part.type === type && 'data' in part) {
        return (part as { data: T }).data;
    }

    return null;
}

export function reasoningData(part: Part): ReasoningData | null {
    return dataOfType<ReasoningData>(part, 'data-reasoning');
}

export function toolCallData(part: Part): ToolCallData | null {
    return dataOfType<ToolCallData>(part, 'data-tool-call');
}

export function providerToolData(part: Part): ProviderToolData | null {
    return dataOfType<ProviderToolData>(part, 'data-provider-tool');
}

export function approvalOwnerToolId(part: Part): string | null {
    if (part.type === 'data-approval' && 'data' in part) {
        return (
            (part as { data: { ownerToolId?: string | null } }).data
                .ownerToolId ?? null
        );
    }

    return null;
}
