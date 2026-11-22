import type { Spec } from '@json-render/core';
import { VISUAL_DESTINATIONS, VISUAL_LIMITS } from './catalog';
import {
    runVisualAction,
    safeLink,
    type VisualSession,
} from './visual-session';

type Fields = Record<string, unknown>;

type VisualBinding =
    | { action: 'setState'; params: { statePath: string; value: unknown } }
    | {
          action: 'pushState';
          params: {
              statePath: string;
              value: unknown;
              clearStatePath?: string | null;
          };
      }
    | { action: 'removeState'; params: { statePath: string; index: number } }
    | {
          action: 'sendMessage' | 'openDestination' | 'openLink';
          params: Fields;
      };

export interface LocalStateStore {
    get: (path: string) => unknown;
    set: (path: string, value: unknown) => void;
    getSnapshot: () => unknown;
}

const APP_ACTIONS: ReadonlySet<string> = new Set([
    'sendMessage',
    'openDestination',
    'openLink',
]);

const ALL_ACTIONS: ReadonlySet<string> = new Set([
    ...APP_ACTIONS,
    'setState',
    'pushState',
    'removeState',
]);

const UNSAFE_SEGMENTS: ReadonlySet<string> = new Set([
    '__proto__',
    'constructor',
    'prototype',
]);

const MAX_PATH_BYTES = 512;

function isFields(value: unknown): value is Fields {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function pathSegments(path: string): string[] {
    return path
        .slice(1)
        .split('/')
        .map((segment) => segment.replaceAll('~1', '/').replaceAll('~0', '~'));
}

function isLocalPath(path: unknown): path is string {
    if (
        typeof path !== 'string' ||
        !path.startsWith('/ui/') ||
        new TextEncoder().encode(path).length > MAX_PATH_BYTES
    ) {
        return false;
    }

    const segments = pathSegments(path);

    return (
        segments.length <= VISUAL_LIMITS.depth &&
        segments.every(
            (segment) => segment !== '' && !UNSAFE_SEGMENTS.has(segment),
        )
    );
}

function isLiteral(value: unknown): boolean {
    if (Array.isArray(value)) {
        return value.every(isLiteral);
    }

    return (
        !isFields(value) ||
        Object.entries(value).every(
            ([key, item]) => !key.startsWith('$') && isLiteral(item),
        )
    );
}

function hasValidParams(action: string, params: Fields): boolean {
    switch (action) {
        case 'sendMessage':
            return typeof params.text === 'string' && params.text.trim() !== '';
        case 'openDestination':
            return VISUAL_DESTINATIONS.some(
                (destination) => destination === params.destination,
            );
        case 'openLink':
            return safeLink(params.url) !== null;
        case 'setState':
            return isLocalPath(params.statePath) && 'value' in params;
        case 'pushState':
            return (
                isLocalPath(params.statePath) &&
                'value' in params &&
                (params.clearStatePath === undefined ||
                    params.clearStatePath === null ||
                    isLocalPath(params.clearStatePath))
            );
        case 'removeState':
            return (
                isLocalPath(params.statePath) &&
                typeof params.index === 'number' &&
                Number.isInteger(params.index) &&
                params.index >= 0
            );
        default:
            return false;
    }
}

function isAllowedBinding(
    binding: unknown,
    actions: ReadonlySet<string> = ALL_ACTIONS,
): binding is VisualBinding {
    if (
        !isFields(binding) ||
        !Object.keys(binding).every(
            (key) => key === 'action' || key === 'params',
        )
    ) {
        return false;
    }

    const { action, params } = binding;

    return (
        typeof action === 'string' &&
        actions.has(action) &&
        isFields(params) &&
        isLiteral(params) &&
        hasValidParams(action, params)
    );
}

export function pressActions(on: unknown): string[] {
    return pressBindings(on).flatMap((binding) =>
        isFields(binding) && typeof binding.action === 'string'
            ? [binding.action]
            : [],
    );
}

function pressBindings(on: unknown): unknown[] {
    if (!isFields(on) || Object.keys(on).some((event) => event !== 'press')) {
        return [];
    }

    const press = on.press;

    if (press === undefined) {
        return [];
    }

    return Array.isArray(press) ? press : [press];
}

function hasLocalBindings(props: unknown): boolean {
    return (
        !isFields(props) ||
        Object.values(props).every(
            (value) =>
                !isFields(value) ||
                !('$bindState' in value) ||
                isLocalPath(value.$bindState),
        )
    );
}

export function hasAllowedActions(spec: Spec): boolean {
    return Object.values(spec.elements ?? {}).every((element) => {
        const props: unknown = element.props;

        if (element.type === 'Button') {
            const bindings = pressBindings(element.on);

            return (
                bindings.length > 0 &&
                bindings.every((binding) => isAllowedBinding(binding))
            );
        }

        if (element.on !== undefined || !hasLocalBindings(props)) {
            return false;
        }

        if (element.type !== 'SuggestionChips') {
            return true;
        }

        const items = isFields(props) ? props.items : undefined;

        return (
            Array.isArray(items) &&
            items.every(
                (item) =>
                    isFields(item) &&
                    isAllowedBinding(item.action, APP_ACTIONS),
            )
        );
    });
}

function isDenseWrite(root: unknown, segments: string[]): boolean {
    let node = root;

    for (const [position, segment] of segments.entries()) {
        const index = /^\d+$/.test(segment) ? Number(segment) : null;

        if (Array.isArray(node)) {
            if (segment === '-' && position === segments.length - 1) {
                return true;
            }

            if (index === null || index > node.length) {
                return false;
            }

            node = node[index];
        } else if (isFields(node)) {
            node = node[segment];
        } else if (index !== null && index > 0) {
            return false;
        } else {
            node = undefined;
        }
    }

    return true;
}

export function writeLocalState(
    store: LocalStateStore,
    path: string,
    value: unknown,
): boolean {
    if (
        !isLocalPath(path) ||
        !isDenseWrite(store.getSnapshot(), pathSegments(path))
    ) {
        return false;
    }

    store.set(path, value);

    return true;
}

export function runPress(
    on: unknown,
    store: LocalStateStore,
    session: VisualSession | null,
): void {
    for (const binding of pressBindings(on)) {
        if (!isAllowedBinding(binding)) {
            continue;
        }

        switch (binding.action) {
            case 'setState':
                writeLocalState(
                    store,
                    binding.params.statePath,
                    binding.params.value,
                );
                break;
            case 'pushState': {
                const { statePath, value, clearStatePath } = binding.params;
                const list = store.get(statePath);

                if (
                    writeLocalState(store, statePath, [
                        ...(Array.isArray(list) ? list : []),
                        value,
                    ]) &&
                    typeof clearStatePath === 'string'
                ) {
                    writeLocalState(store, clearStatePath, '');
                }
                break;
            }
            case 'removeState': {
                const { statePath, index } = binding.params;
                const list = store.get(statePath);

                if (Array.isArray(list)) {
                    writeLocalState(
                        store,
                        statePath,
                        list.filter((_, position) => position !== index),
                    );
                }
                break;
            }
            default:
                runVisualAction(session, binding.action, binding.params);
        }
    }
}
