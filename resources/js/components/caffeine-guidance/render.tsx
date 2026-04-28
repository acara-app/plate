import { createRenderer } from '@json-render/react';
import { caffeineGuidanceCatalog } from './catalog';
import { ContextNote } from './components/context-note';
import { GuidanceList } from './components/guidance-list';
import { LimitGauge } from './components/limit-gauge';
import { SafetyNote } from './components/safety-note';
import { Stack } from './components/stack';
import { VerdictCard } from './components/verdict-card';

export const CaffeineGuidanceRenderer = createRenderer(
    caffeineGuidanceCatalog,
    {
        Stack: ({ children }) => <Stack>{children}</Stack>,
        VerdictCard: ({ element }) => <VerdictCard props={element.props} />,
        LimitGauge: ({ element }) => <LimitGauge props={element.props} />,
        GuidanceList: ({ element }) => <GuidanceList props={element.props} />,
        ContextNote: ({ element }) => <ContextNote props={element.props} />,
        SafetyNote: ({ element }) => <SafetyNote props={element.props} />,
    },
);
