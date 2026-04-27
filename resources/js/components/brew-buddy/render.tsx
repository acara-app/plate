import { createRenderer } from '@json-render/react';
import { brewBuddyCatalog } from './catalog';
import { DrinkCard } from './components/drink-card';
import { Hero } from './components/hero';
import { Stack } from './components/stack';
import { Stat } from './components/stat';
import { Timeline } from './components/timeline';
import { Tip } from './components/tip';
import { Warning } from './components/warning';

export const BrewPlanRenderer = createRenderer(brewBuddyCatalog, {
    Stack: ({ children }) => <Stack>{children}</Stack>,
    Hero: ({ element }) => <Hero props={element.props} />,
    Stat: ({ element }) => <Stat props={element.props} />,
    DrinkCard: ({ element }) => <DrinkCard props={element.props} />,
    Timeline: ({ element }) => <Timeline props={element.props} />,
    Tip: ({ element }) => <Tip props={element.props} />,
    Warning: ({ element }) => <Warning props={element.props} />,
});
