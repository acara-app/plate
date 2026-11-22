import type { ReactNode } from 'react';
import type { z } from 'zod';
import type { acaraChatComponents } from '../catalog';

export type VisualComponent = keyof typeof acaraChatComponents;

export interface ViewProps<K extends VisualComponent> {
    props: z.infer<(typeof acaraChatComponents)[K]['props']>;
    children?: ReactNode;
}
