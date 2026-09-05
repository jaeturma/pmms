import assert from 'node:assert/strict';
import { readFile, writeFile, unlink } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import ts from 'typescript';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

const generated = new URL('./.result-attribution-render.mjs', import.meta.url);
try {
    const source = await readFile(
        new URL(
            '../../resources/js/components/result-attribution.tsx',
            import.meta.url,
        ),
        'utf8',
    );
    await writeFile(
        generated,
        ts.transpileModule(source, {
            compilerOptions: {
                jsx: ts.JsxEmit.ReactJSX,
                module: ts.ModuleKind.ESNext,
                target: ts.ScriptTarget.ES2022,
            },
        }).outputText,
    );
    const { AttributionFields, emptyAttribution } = await import(
        pathToFileURL(generated.pathname.replace(/^\/([A-Za-z]:)/, '$1')).href
    );
    const props = {
        eventId: 1,
        delegationId: 1,
        value: emptyAttribution(),
        onChange() {},
    };
    const individual = renderToStaticMarkup(
        React.createElement(AttributionFields, { ...props, team: false }),
    );
    const team = renderToStaticMarkup(
        React.createElement(AttributionFields, { ...props, team: true }),
    );
    assert.match(individual, /aria-label="Athlete \(optional\)"/);
    assert.doesNotMatch(individual, /required=""/);
    assert.match(individual, /Search athlete by name/);
    assert.doesNotMatch(team, /aria-label="Athlete \(optional\)"/);
    assert.match(team, /View \/ Manage Roster/);
    assert.match(team, /Team Coaches \(optional\)/);
    assert.match(team, /Roster may remain incomplete/);
    console.log('Passed 7 attribution UI assertions.');
} finally {
    await unlink(generated).catch(() => {});
}
