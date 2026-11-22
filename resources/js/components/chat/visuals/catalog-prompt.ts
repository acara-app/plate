import type { ZodType } from 'zod';
import {
    ACARA_CHAT_CATALOG,
    VISUAL_ICONS,
    VISUAL_LIMITS,
    VISUAL_TINTS,
    VISUAL_TONES,
    acaraChatActions,
    acaraChatComponents,
    acaraChatLocalActions,
} from './catalog';

type FormatZodType = (schema: ZodType) => string;

const SPEC_EXAMPLE = [
    '```spec',
    '{"op":"add","path":"/root","value":"meal"}',
    '{"op":"add","path":"/elements/meal","value":{"type":"Card","props":{"title":"Your breakfast"},"children":["macros"]}}',
    '{"op":"add","path":"/elements/macros","value":{"type":"MacroBreakdown","props":{"calories":410,"carbs":50,"protein":21,"fat":14},"children":[]}}',
    '```',
];

const BOUND_CHART_EXAMPLE = [
    '```spec',
    '{"op":"add","path":"/root","value":"steps"}',
    '{"op":"add","path":"/elements/steps","value":{"type":"BarChart","props":{"title":"Daily steps","data":{"$state":"/data/step_count"},"xKey":"day","yKey":"value","unit":"steps","goal":7000},"children":[]}}',
    '```',
];

export function buildAcaraChatPrompt(formatZodType: FormatZodType): string {
    const components = Object.entries(acaraChatComponents).map(
        ([name, definition]) =>
            `- ${name}: ${formatZodType(definition.props)} — ${definition.description}${'slots' in definition ? ' [children]' : ''}`,
    );
    const actions = Object.entries({
        ...acaraChatActions,
        ...acaraChatLocalActions,
    }).map(
        ([name, definition]) =>
            `- ${name}: ${formatZodType(definition.params)} — ${definition.description}`,
    );

    return [
        `VISUALS (${ACARA_CHAT_CATALOG.name} catalog, version ${ACARA_CHAT_CATALOG.version})`,
        '',
        'You can place visuals between the paragraphs of a reply. Use one only when it shows something prose cannot show as well: a trend, a comparison, a routine, a breakdown, or a list to tick off. Greetings, simple facts and short answers stay prose.',
        'Your prose must stand on its own, because some people only see the text. Every fact a visual shows must still make sense with the visual removed. Never write "see the chart" or "below".',
        '',
        'WHEN TO SHOW A VISUAL',
        "This person's app shows visuals inside your reply. Prefer one, after a short lead-in sentence, whenever the answer is:",
        '- a routine, workout or set of instructions: Steps',
        '- two or three options compared: Comparison',
        "- a meal's or a day's calories and macros: MacroBreakdown",
        '- whether a food will spike blood sugar: RiskMeter, with the reason as detail',
        '- a shopping or packing list: Checklist',
        '- one to four headline numbers: Metric, in a Grid when there are several',
        "- the person's own figures over several days, such as a week of steps or a month of sleep: BarChart or LineChart bound to a dataset (see DATA)",
        '- two or three versions of one answer, such as a weeknight and a weekend routine: one Card with a Segmented control that switches between them (see LOCAL STATE)',
        '- advice to log a meal, snap a photo of a plate or check the health sync in Plate: a Button that opens log, snap or sync, right after that advice, even when the reply already has another visual',
        'Greetings, simple facts and short answers stay prose. A visual does not count toward your length limit.',
        '',
        'FOLLOW-UPS',
        'After a substantive answer, such as a plan, a routine, a list, a meal, a comparison or a look at the person\'s data, end with one SuggestionChips visual of two or three follow-ups. Use it too in place of a closing question that asks which direction the person wants. Keep each label short, and make each one send a complete question or request in the person\'s language, such as {"label":"Meat-free ideas","action":{"action":"sendMessage","params":{"text":"Give me meat-free dinner ideas."}}}. Offer only follow-ups you can answer. Skip it after greetings, thanks, simple facts and short answers.',
        '',
        'FORMAT',
        `Write each visual as a \`\`\`spec fenced block of JSONL lines: RFC 6902 JSON Patch operations that build one spec with "root", "elements" and an optional "state". Close the fence before you continue writing. Use at most ${VISUAL_LIMITS.visualsPerReply} visuals in a reply and at most ${VISUAL_LIMITS.elements} elements in one visual.`,
        ...SPEC_EXAMPLE,
        '- Start each visual with its /root, then add each element with type, props and children.',
        '- children lists element keys, and leaves use []. Only components marked [children] take children, and every key you list must be added as an element.',
        '- Every Comparison row has one value per column: {"columns":["White rice","Brown rice"],"rows":[{"label":"Fibre","values":["0.6 g","3.5 g"]}]}.',
        '- Numbers are JSON numbers, with their unit in a unit prop. Days are ISO dates such as 2026-11-10, and times are ISO 8601.',
        "- Never type the person's recorded health figures (readings, logs, totals) into a visual, and never invent sample data. Chart recorded figures by binding a dataset, as DATA explains. Literal numbers are fine for your own estimates, such as a suggested meal's macros or a routine's durations.",
        '- Your earlier visuals appear in the conversation as [visual] … [/visual] text. That is only a record of what was shown. To show a visual, always write a ```spec block.',
        '',
        'DATA',
        'When a tool fetched the person\'s daily figures during this reply, its result ends with "Datasets for visuals", one handle per line, for example "- step_count: Steps, daily total in steps, 2026-11-16 to 2026-11-22, 7 days, 1 without a reading".',
        '- Each dataset holds one row per day, {"day":"2026-11-16","value":4200}, and value is null on a day without a reading.',
        '- Chart a dataset by binding it, never by copying its values: set data to {"$state":"/data/<handle>"}, xKey to "day" and yKey to "value". Plate fills in the rows, the unit and the day boundary.',
        ...BOUND_CHART_EXAMPLE,
        '- Bind only a handle listed in a tool result of this reply. Handles from earlier replies no longer exist, so fetch the data again.',
        "- Write a goal or a target range in the dataset's unit.",
        '- Never add /state/data yourself.',
        '',
        'COMPONENTS',
        ...components,
        '',
        `icon: ${VISUAL_ICONS.join(', ')}.`,
        `tint: ${VISUAL_TINTS.join(', ')}.`,
        `tone: ${VISUAL_TONES.join(', ')}.`,
        '',
        'ACTIONS',
        'A Button runs one action from the on.press field of its element: "on":{"press":{"action":"openDestination","params":{"destination":"log"}}}. Each SuggestionChips item carries the same {"action","params"} object.',
        '- An action object holds only "action" and "params", and its params are plain values, never $state. openLink takes a full https:// address.',
        ...actions,
        '',
        'LOCAL STATE',
        '- Interaction state lives under /ui. Seed it with {"op":"add","path":"/state/ui","value":{"metric":"steps"}}.',
        '- {"$bindState":"/ui/metric"} reads and writes a value: use it for the Segmented value and the Checklist checked list. {"$state":"/ui/metric"} only reads it.',
        '- To switch what a visual shows, give each switched element a top-level "visible" field such as {"$state":"/ui/metric","eq":"steps"}.',
        '- A Segmented value and a Checklist checked list must use $bindState, with their start values seeded in /state/ui: an option value for a Segmented, and a list such as [] for a Checklist.',
    ].join('\n');
}
