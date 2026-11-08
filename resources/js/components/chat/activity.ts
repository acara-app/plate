import type {
    ApprovalCardData,
    ProviderToolData,
    ReasoningData,
    ToolCallData,
} from '@/types/chat';
import type { UIMessage } from '@ai-sdk/react';
import { providerToolData, reasoningData, toolCallData } from './stream-parts';

type Part = UIMessage['parts'][number];

export type Translate = (
    key: string,
    options?: Record<string, unknown>,
) => string;

export type StepStatus =
    'running' | 'done' | 'failed' | 'stopped' | 'awaiting' | 'declined';

export type ActivityItem =
    | {
          kind: 'reasoning';
          id: string;
          status: StepStatus;
          reasoning: ReasoningData;
      }
    | {
          kind: 'tool';
          id: string;
          status: StepStatus;
          tool: ToolCallData;
      }
    | {
          kind: 'provider';
          id: string;
          status: StepStatus;
          provider: ProviderToolData;
      };

export interface ActivitySummary {
    status: StepStatus;
    label: string;
    showStatusWord: boolean;
}

export interface RequestField {
    key: string;
    label: string;
    value: string;
}

export interface Outcome {
    text: string;
    tone: 'default' | 'muted' | 'error';
}

const PRIMARY_ARGUMENT_KEYS = [
    'task',
    'query',
    'question',
    'q',
    'summary',
    'description',
    'text',
    'message',
    'food',
    'name',
    'url',
];

const RESULT_MESSAGE_KEYS = ['message', 'summary', 'error', 'suggestion'];

const MAX_REQUEST_FIELDS = 4;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isAwaitingApproval(approval: ApprovalCardData | undefined): boolean {
    return approval?.status === 'pending' || approval?.status === 'submitted';
}

function isDeclinedApproval(approval: ApprovalCardData | undefined): boolean {
    return approval?.status === 'rejected' || approval?.status === 'abandoned';
}

function toolStatus(
    tool: ToolCallData,
    approval: ApprovalCardData | undefined,
    live: boolean,
): StepStatus {
    if (tool.status === 'error') {
        return 'failed';
    }

    if (isDeclinedApproval(approval)) {
        return 'declined';
    }

    if (tool.status === 'complete') {
        return 'done';
    }

    if (live) {
        return 'running';
    }

    if (isAwaitingApproval(approval)) {
        return 'awaiting';
    }

    return approval?.status === 'approved' ? 'done' : 'stopped';
}

export function buildActivity(
    parts: Part[],
    approvals: ApprovalCardData[],
    live: boolean,
): ActivityItem[] {
    const approvalsByToolId = new Map<string, ApprovalCardData>();

    for (const approval of approvals) {
        approvalsByToolId.set(approval.toolCallId, approval);

        if (approval.ownerToolId) {
            approvalsByToolId.set(approval.ownerToolId, approval);
        }
    }

    const items: ActivityItem[] = [];

    for (const part of parts) {
        const reasoning = reasoningData(part);

        if (reasoning) {
            const thinking = reasoning.active && live;

            if (thinking || reasoning.text.trim() !== '') {
                items.push({
                    kind: 'reasoning',
                    id: reasoning.reasoningId,
                    status: thinking ? 'running' : 'done',
                    reasoning,
                });
            }

            continue;
        }

        const tool = toolCallData(part);

        if (tool) {
            items.push({
                kind: 'tool',
                id: tool.toolId,
                status: toolStatus(
                    tool,
                    approvalsByToolId.get(tool.toolId),
                    live,
                ),
                tool,
            });

            continue;
        }

        const provider = providerToolData(part);

        if (provider) {
            items.push({
                kind: 'provider',
                id: provider.itemId,
                status:
                    provider.status === 'complete'
                        ? 'done'
                        : live
                          ? 'running'
                          : 'stopped',
                provider,
            });
        }
    }

    return items;
}

export function formatDuration(milliseconds: number, t: Translate): string {
    const totalSeconds = Math.max(1, Math.round(milliseconds / 1000));

    if (totalSeconds < 60) {
        return t('chat.activity.duration_seconds', { seconds: totalSeconds });
    }

    return t('chat.activity.duration_minutes', {
        minutes: Math.floor(totalSeconds / 60),
        seconds: totalSeconds % 60,
    });
}

function humanize(identifier: string): string {
    const words = identifier.replace(/[_-]+/g, ' ').trim();

    return words.charAt(0).toUpperCase() + words.slice(1);
}

function reasoningLabel(reasoning: ReasoningData, t: Translate): string {
    if (reasoning.completedAt === null) {
        return t('chat.activity.thought');
    }

    return t('chat.activity.thought_for', {
        duration: formatDuration(
            reasoning.completedAt - reasoning.startedAt,
            t,
        ),
    });
}

export function stepLabel(item: ActivityItem, t: Translate): string {
    if (item.kind === 'reasoning') {
        return item.status === 'running'
            ? t('chat.activity.thinking')
            : reasoningLabel(item.reasoning, t);
    }

    if (item.kind === 'provider') {
        if (item.provider.kind === 'web_search') {
            return t('chat.activity.web_search');
        }

        return item.provider.kind === 'web_fetch'
            ? t('chat.activity.web_fetch')
            : t('chat.activity.provider_tool');
    }

    if (item.tool.title) {
        return item.tool.title;
    }

    return item.tool.toolName
        ? humanize(item.tool.toolName)
        : t('chat.activity.tool');
}

export function statusWord(status: StepStatus, t: Translate): string {
    return t(`chat.activity.status.${status}`);
}

export function summarizeActivity(
    items: ActivityItem[],
    t: Translate,
): ActivitySummary | null {
    if (items.length === 0) {
        return null;
    }

    const running = items.findLast((item) => item.status === 'running');

    if (running) {
        return {
            status: 'running',
            label:
                running.kind === 'reasoning'
                    ? t('chat.activity.thinking')
                    : t('chat.activity.running', {
                          label: stepLabel(running, t),
                      }),
            showStatusWord: false,
        };
    }

    if (items.some((item) => item.status === 'awaiting')) {
        return {
            status: 'awaiting',
            label: t('chat.activity.awaiting'),
            showStatusWord: false,
        };
    }

    const steps = items.filter((item) => item.kind !== 'reasoning');

    if (steps.length === 0) {
        const thinkingTime = items.reduce(
            (total, item) =>
                item.kind === 'reasoning' && item.reasoning.completedAt
                    ? total +
                      (item.reasoning.completedAt - item.reasoning.startedAt)
                    : total,
            0,
        );

        return {
            status: 'done',
            label:
                thinkingTime > 0
                    ? t('chat.activity.thought_for', {
                          duration: formatDuration(thinkingTime, t),
                      })
                    : t('chat.activity.thought'),
            showStatusWord: false,
        };
    }

    if (steps.length === 1) {
        return {
            status: steps[0].status,
            label: stepLabel(steps[0], t),
            showStatusWord: true,
        };
    }

    const failed = steps.filter((step) => step.status === 'failed').length;

    if (failed > 0) {
        return {
            status: 'failed',
            label: t('chat.activity.failed', { failed, total: steps.length }),
            showStatusWord: false,
        };
    }

    const stopped = steps.filter((step) => step.status === 'stopped').length;

    if (stopped > 0) {
        return {
            status: 'stopped',
            label: t('chat.activity.stopped', {
                done: steps.length - stopped,
                total: steps.length,
            }),
            showStatusWord: false,
        };
    }

    return {
        status: 'done',
        label: t('chat.activity.completed', { count: steps.length }),
        showStatusWord: false,
    };
}

function displayValue(value: unknown): string | null {
    if (typeof value === 'string') {
        const trimmed = value.trim();

        return trimmed === '' ? null : trimmed;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (Array.isArray(value)) {
        const scalars = value
            .map(displayValue)
            .filter((entry): entry is string => entry !== null);

        return scalars.length > 0 ? scalars.join(', ') : null;
    }

    return null;
}

export function requestFields(
    args: Record<string, unknown> | null,
): RequestField[] {
    if (!args) {
        return [];
    }

    const priority = (key: string): number => {
        const index = PRIMARY_ARGUMENT_KEYS.indexOf(key);

        return index === -1 ? PRIMARY_ARGUMENT_KEYS.length : index;
    };

    return Object.entries(args)
        .map(([key, value]) => ({
            key,
            label: humanize(key),
            value: displayValue(value),
        }))
        .filter((field): field is RequestField => field.value !== null)
        .sort((a, b) => priority(a.key) - priority(b.key))
        .slice(0, MAX_REQUEST_FIELDS);
}

export function isPrimaryArgument(field: RequestField): boolean {
    return PRIMARY_ARGUMENT_KEYS.includes(field.key);
}

function parseResult(result: unknown): unknown {
    if (typeof result !== 'string') {
        return result;
    }

    const trimmed = result.trim();

    if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
        return trimmed;
    }

    try {
        return JSON.parse(trimmed) as unknown;
    } catch {
        return trimmed;
    }
}

function resultMessage(result: unknown): string | null {
    const value = parseResult(result);

    if (typeof value === 'string') {
        return value === '' ? null : value;
    }

    if (!isRecord(value)) {
        return null;
    }

    for (const key of RESULT_MESSAGE_KEYS) {
        const message = value[key];

        if (typeof message === 'string' && message.trim() !== '') {
            return message.trim();
        }
    }

    return null;
}

export function stepOutcome(item: ActivityItem, t: Translate): Outcome {
    switch (item.status) {
        case 'failed':
            return {
                text:
                    (item.kind === 'tool' ? item.tool.error : null) ??
                    t('chat.activity.outcome_failed'),
                tone: 'error',
            };
        case 'running':
            return { text: t('chat.activity.outcome_running'), tone: 'muted' };
        case 'stopped':
            return { text: t('chat.activity.outcome_stopped'), tone: 'muted' };
        case 'awaiting':
            return { text: t('chat.activity.outcome_awaiting'), tone: 'muted' };
        case 'declined':
            return { text: t('chat.activity.outcome_declined'), tone: 'muted' };
        case 'done': {
            const message =
                item.kind === 'tool' ? resultMessage(item.tool.result) : null;

            return message
                ? { text: message, tone: 'default' }
                : { text: t('chat.activity.outcome_done'), tone: 'muted' };
        }
    }
}

export function formatTechnicalValue(value: unknown): string | null {
    if (value === null || value === undefined) {
        return null;
    }

    const parsed = parseResult(value);

    if (typeof parsed === 'string') {
        return parsed === '' ? null : parsed;
    }

    try {
        return JSON.stringify(parsed, null, 2);
    } catch {
        return String(parsed);
    }
}

export function latestThought(items: ActivityItem[]): string | null {
    const thinking = items.findLast(
        (item) => item.kind === 'reasoning' && item.status === 'running',
    );

    if (!thinking || thinking.kind !== 'reasoning') {
        return null;
    }

    const text = thinking.reasoning.text.trim();

    return text === '' ? null : text.slice(-240);
}
