import { createRenderer } from '@json-render/react';
import { caffeineGuidanceCatalog } from './catalog';
import { ConditionCard } from './components/condition-card';
import { DrinkSizeGrid } from './components/drink-size-grid';
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
        DrinkSizeGrid: ({ element }) => <DrinkSizeGrid props={element.props} />,
        GuidanceList: ({ element }) => <GuidanceList props={element.props} />,
        ConditionCard: ({ element }) => <ConditionCard props={element.props} />,
        SafetyNote: ({ element }) => <SafetyNote props={element.props} />,
    },
);
