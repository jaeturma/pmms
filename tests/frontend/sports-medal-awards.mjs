import assert from 'node:assert/strict';
import { readFile, writeFile, unlink } from 'node:fs/promises';
import ts from 'typescript';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
const fixture = new URL('./.sports-medal-awards-render.mjs', import.meta.url);
try {
    const source = await readFile(new URL('../../resources/js/pages/reports/sports-medal-awards.tsx', import.meta.url), 'utf8');
    const support = `import React from 'react'; const Head = () => null; const router = { get() {} }; const Button = ({children, ...props}) => React.createElement('button', props, children);`;
    const stripped = source.split('\n').filter(line => !/^import\s/.test(line)).join('\n');
    await writeFile(fixture, ts.transpileModule(support + stripped, { compilerOptions: { jsx: ts.JsxEmit.ReactJSX, module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText);
    const { default: Report } = await import(fixture.href);
    const props = { meet: { id: 1, name: 'Configured Meet', school_year: null, venue: null, starts_at: null, ends_at: null }, meetOptions: [{id: 1, name: 'Configured Meet'}], sportOptions: [{id: 1, name: 'Athletics'}], sportId: 1, canSelectAll: false, sportLabel: 'Athletics', generatedAt: 'Today', sports: [] };
    let html = renderToStaticMarkup(React.createElement(Report, props));
    assert.match(html, /No accepted medal awards/); assert.doesNotMatch(html, /<option value="">All Sports/);
    const award = {id: 1, medal: 'gold', tally_count: 3, physical_count: 5, athletes: [], team: null, coaches: [], mark: null};
    props.sports = [{id: 1, name: 'Athletics', events: [{id: 1, name: '100m', division: null, gender: null, awards: [award, {...award, id: 2}]}], signatories: [{label: 'Prepared by', name: null, position: null}]}];
    html = renderToStaticMarkup(React.createElement(Report, props));
    assert.equal((html.match(/>gold<\/td>/g) || []).length, 2); assert.match(html, /<thead>/); assert.match(html, /print:hidden/); assert.doesNotMatch(html, /undefined/);
    props.canSelectAll = true; props.sportId = null;
    assert.match(renderToStaticMarkup(React.createElement(Report, props)), /All Sports/);
    console.log('Sports medal awards rendering: passed');
} finally { await unlink(fixture).catch(() => {}); }
