import { defineCatalog, type Spec } from '@json-render/core';
import { createRenderer } from '@json-render/react';
import { schema } from '@json-render/react/schema';
import { acaraChatComponents } from './catalog';
import { Comparison } from './components/comparison';
import { Card, Disclosure, Grid, Separator, Stack } from './components/layout';
import { List, ListItem, Steps } from './components/list';
import { Metric, Progress } from './components/metric';
import { MacroBreakdown, RiskMeter } from './components/nutrition';
import { Badge, Callout, Heading, Text } from './components/text';

const RENDERED = [
    'Card',
    'Stack',
    'Grid',
    'Separator',
    'Heading',
    'Text',
    'Badge',
    'Callout',
    'Metric',
    'Progress',
    'List',
    'ListItem',
    'Steps',
    'Comparison',
    'MacroBreakdown',
    'RiskMeter',
    'Disclosure',
] as const satisfies ReadonlyArray<keyof typeof acaraChatComponents>;

const renderedTypes: ReadonlySet<string> = new Set(RENDERED);

const webCatalog = defineCatalog(schema, {
    components: Object.fromEntries(
        RENDERED.map((name) => [name, acaraChatComponents[name]]),
    ) as Pick<typeof acaraChatComponents, (typeof RENDERED)[number]>,
    actions: {},
});

export const ChatVisualRenderer = createRenderer(webCatalog, {
    Card: ({ element, children }) => (
        <Card props={element.props}>{children}</Card>
    ),
    Stack: ({ element, children }) => (
        <Stack props={element.props}>{children}</Stack>
    ),
    Grid: ({ element, children }) => (
        <Grid props={element.props}>{children}</Grid>
    ),
    Separator: () => <Separator />,
    Heading: ({ element }) => <Heading props={element.props} />,
    Text: ({ element }) => <Text props={element.props} />,
    Badge: ({ element }) => <Badge props={element.props} />,
    Callout: ({ element }) => <Callout props={element.props} />,
    Metric: ({ element }) => <Metric props={element.props} />,
    Progress: ({ element }) => <Progress props={element.props} />,
    List: ({ element, children }) => (
        <List props={element.props}>{children}</List>
    ),
    ListItem: ({ element, children }) => (
        <ListItem props={element.props}>{children}</ListItem>
    ),
    Steps: ({ element }) => <Steps props={element.props} />,
    Comparison: ({ element }) => <Comparison props={element.props} />,
    MacroBreakdown: ({ element }) => <MacroBreakdown props={element.props} />,
    RiskMeter: ({ element }) => <RiskMeter props={element.props} />,
    Disclosure: ({ element, children }) => (
        <Disclosure props={element.props}>{children}</Disclosure>
    ),
});

export function canRenderVisual(spec: Spec): boolean {
    const elements = spec.elements ?? {};

    return (
        elements[spec.root] !== undefined &&
        Object.values(elements).every((element) =>
            renderedTypes.has(element.type),
        )
    );
}
