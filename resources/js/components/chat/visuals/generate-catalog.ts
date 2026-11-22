/// <reference types="node" />

import { defineCatalog, defineSchema } from '@json-render/core';
import { schema } from '@json-render/react/schema';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { z } from 'zod';
import {
    ACARA_CHAT_CATALOG,
    ACTION_VERSIONS,
    COMPONENT_VERSIONS,
    VISUAL_ICONS,
    VISUAL_LIMITS,
    VISUAL_TINTS,
    VISUAL_TONES,
    acaraChatActions,
    acaraChatComponents,
    acaraChatLocalActions,
} from './catalog';
import { buildAcaraChatPrompt } from './catalog-prompt';

const outputDirectory = fileURLToPath(new URL('./generated/', import.meta.url));

function componentEntries() {
    return (
        Object.keys(acaraChatComponents) as Array<
            keyof typeof acaraChatComponents
        >
    ).map((name) => {
        const definition = acaraChatComponents[name];
        definition.props.parse(definition.example);

        return [
            name,
            {
                introducedIn: COMPONENT_VERSIONS[name],
                description: definition.description,
                acceptsChildren: 'slots' in definition,
                props: z.toJSONSchema(definition.props),
                example: definition.example,
            },
        ];
    });
}

function actionEntries() {
    const appActions = (
        Object.keys(acaraChatActions) as Array<keyof typeof acaraChatActions>
    ).map((name) => [
        name,
        {
            introducedIn: ACTION_VERSIONS[name],
            builtIn: false,
            description: acaraChatActions[name].description,
            params: z.toJSONSchema(acaraChatActions[name].params),
        },
    ]);
    const localActions = (
        Object.keys(acaraChatLocalActions) as Array<
            keyof typeof acaraChatLocalActions
        >
    ).map((name) => [
        name,
        {
            introducedIn: ACTION_VERSIONS[name],
            builtIn: true,
            description: acaraChatLocalActions[name].description,
            params: z.toJSONSchema(acaraChatLocalActions[name].params),
        },
    ]);

    return [...appActions, ...localActions];
}

function catalogJson(): string {
    const catalog = {
        name: ACARA_CHAT_CATALOG.name,
        version: ACARA_CHAT_CATALOG.version,
        propsDescribe: 'resolved values',
        limits: VISUAL_LIMITS,
        vocabularies: {
            icon: VISUAL_ICONS,
            tint: VISUAL_TINTS,
            tone: VISUAL_TONES,
        },
        components: Object.fromEntries(componentEntries()),
        actions: Object.fromEntries(actionEntries()),
    };

    return `${JSON.stringify(catalog, null, 4)}\n`;
}

function catalogPrompt(): string {
    const promptSchema = defineSchema(() => schema.definition, {
        promptTemplate: (context) =>
            buildAcaraChatPrompt(context.formatZodType),
    });
    const promptCatalog = defineCatalog(promptSchema, {
        components: acaraChatComponents,
        actions: acaraChatActions,
    });

    return `${promptCatalog.prompt()}\n`;
}

const files: Record<string, string> = {
    'acara-chat.json': catalogJson(),
    'acara-chat.prompt.md': catalogPrompt(),
};

if (process.argv.includes('--check')) {
    const staleFiles = Object.entries(files)
        .filter(([name, contents]) => {
            const path = join(outputDirectory, name);

            return !existsSync(path) || readFileSync(path, 'utf8') !== contents;
        })
        .map(([name]) => join(outputDirectory, name));

    if (staleFiles.length > 0) {
        process.stderr.write(
            `The chat catalog files are out of date: ${staleFiles.join(', ')}\nRun \`bun run catalog\` and commit the result.\n`,
        );
        process.exit(1);
    }

    process.stdout.write('The chat catalog files are up to date.\n');
} else {
    mkdirSync(outputDirectory, { recursive: true });

    for (const [name, contents] of Object.entries(files)) {
        writeFileSync(join(outputDirectory, name), contents);
    }

    process.stdout.write(
        `Wrote the chat catalog files to ${outputDirectory}\n`,
    );
}
