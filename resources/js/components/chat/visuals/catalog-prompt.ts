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
        'Greetings, simple facts and short answers stay prose. A visual does not count toward your length limit.',
        '',
        'FORMAT',
        `Write each visual as a \`\`\`spec fenced block of JSONL lines: RFC 6902 JSON Patch operations that build one spec with "root", "elements" and an optional "state". Close the fence before you continue writing. Use at most ${VISUAL_LIMITS.visualsPerReply} visuals in a reply and at most ${VISUAL_LIMITS.elements} elements in one visual.`,
        ...SPEC_EXAMPLE,
        '- Start each visual with its /root, then add each element with type, props and children.',
        '- children lists element keys, and leaves use []. Only components marked [children] take children, and every key you list must be added as an element.',
        '- Every Comparison row has one value per column: {"columns":["White rice","Brown rice"],"rows":[{"label":"Fibre","values":["0.6 g","3.5 g"]}]}.',
        '- Numbers are JSON numbers, with their unit in a unit prop. Days are ISO dates such as 2026-11-10, and times are ISO 8601.',
        "- Never type the person's recorded health figures (readings, logs, totals) into a visual, and never invent sample data. Keep recorded figures in prose. Literal numbers are fine for your own estimates, such as a suggested meal's macros or a routine's durations.",
        '- Your earlier visuals appear in the conversation as [visual] … [/visual] text. That is only a record of what was shown. To show a visual, always write a ```spec block.',
        '',
        'COMPONENTS',
        ...components,
        '',
        `icon: ${VISUAL_ICONS.join(', ')}.`,
        `tint: ${VISUAL_TINTS.join(', ')}.`,
        `tone: ${VISUAL_TONES.join(', ')}.`,
        '',
        'ACTIONS',
        'A Button runs one action from the on.press field of its element: "on":{"press":{"action":"sendMessage","params":{"text":"Plan my lunch"}}}. Each SuggestionChips item carries the same {"action","params"} object.',
        ...actions,
        '',
        'LOCAL STATE',
        '- Interaction state lives under /ui. Seed it with {"op":"add","path":"/state/ui","value":{"metric":"steps"}}.',
        '- {"$bindState":"/ui/metric"} reads and writes a value: use it for the Segmented value and the Checklist checked list. {"$state":"/ui/metric"} only reads it.',
        '- To switch what a visual shows, give each switched element a top-level "visible" field such as {"$state":"/ui/metric","eq":"steps"}.',
    ].join('\n');
}
