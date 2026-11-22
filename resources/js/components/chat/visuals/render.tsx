import type { ActionBinding, Spec } from '@json-render/core';
import { createRenderer } from '@json-render/react';
import { acaraChatCatalog, acaraChatComponents } from './catalog';
import { SuggestionChips, VisualButton } from './components/actions';
import { BarChart, LineChart } from './components/chart';
import { Comparison } from './components/comparison';
import { Checklist, Segmented } from './components/controls';
import { Card, Disclosure, Grid, Separator, Stack } from './components/layout';
import { List, ListItem, Steps } from './components/list';
import { Metric, Progress } from './components/metric';
import { MacroBreakdown, RiskMeter } from './components/nutrition';
import { Badge, Callout, Heading, Text } from './components/text';

function pressBindings(
    binding: ActionBinding | ActionBinding[] | undefined,
): ActionBinding[] {
    if (binding === undefined) {
        return [];
    }

    return Array.isArray(binding) ? binding : [binding];
}

export const ChatVisualRenderer = createRenderer(acaraChatCatalog, {
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
    BarChart: ({ element }) => <BarChart props={element.props} />,
    LineChart: ({ element }) => <LineChart props={element.props} />,
    MacroBreakdown: ({ element }) => <MacroBreakdown props={element.props} />,
    RiskMeter: ({ element }) => <RiskMeter props={element.props} />,
    Segmented: ({ element, bindings }) => (
        <Segmented props={element.props} bindings={bindings} />
    ),
    Disclosure: ({ element, children }) => (
        <Disclosure props={element.props}>{children}</Disclosure>
    ),
    Checklist: ({ element, bindings }) => (
        <Checklist props={element.props} bindings={bindings} />
    ),
    Button: ({ element, emit }) => (
        <VisualButton
            label={element.props.label}
            bindings={pressBindings(element.on?.press)}
            onPress={() => emit('press')}
        />
    ),
    SuggestionChips: ({ element }) => <SuggestionChips props={element.props} />,
});

export function canRenderVisual(spec: Spec): boolean {
    const elements = spec.elements ?? {};

    return (
        elements[spec.root] !== undefined &&
        Object.values(elements).every((element) =>
            Object.hasOwn(acaraChatComponents, element.type),
        )
    );
}
