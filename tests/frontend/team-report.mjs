import assert from 'node:assert/strict';
import { readFile, writeFile, unlink } from 'node:fs/promises';
import ts from 'typescript';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
const fixture = new URL('./.team-report-render.mjs', import.meta.url);
try {
    const source = await readFile(new URL('../../resources/js/pages/reports/team-report.tsx', import.meta.url), 'utf8');
    const support = `import React from 'react'; const Head = () => null; const router = { get() {} }; const Button = ({children, ...props}) => React.createElement('button', props, children);`;
    const stripped = source.split('\n').filter(line => !/^import\s/.test(line)).join('\n');
    await writeFile(fixture, ts.transpileModule(support + stripped, { compilerOptions: { jsx: ts.JsxEmit.ReactJSX, module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText);
    const { default: Report } = await import(fixture.href);
    const props = { meet: { id: 1, name: 'Configured Meet', school_year: null, venue: null, starts_at: null, ends_at: null }, meetOptions: [{id: 1, name: 'Configured Meet'}], teamOptions: [{id: 1, name: 'Selected Team'}], team: null, summary: {gold: 0, silver: 0, bronze: 0, total: 0}, generatedAt: 'Today', sports: [] };
    let html = renderToStaticMarkup(React.createElement(Report, props));
    assert.match(html, /Select a Team \/ Delegation to preview/); assert.match(html, /disabled=""/);
    props.team = props.teamOptions[0];
    html = renderToStaticMarkup(React.createElement(Report, props));
    assert.match(html, /No accepted medal awards for this Team/); assert.match(html, /Medal summary/);
    const award = {id: 1, medal: 'gold', tally_count: 2, athletes: [], mark: null};
    props.summary = {gold: 4, silver: 0, bronze: 0, total: 4};
    props.sports = [{id: 1, name: null, events: [{id: 1, name: null, division: null, gender: null, awards: [award, {...award, id: 2}]}], signatories: [{label: 'Prepared by', name: null, position: null}]}];
    html = renderToStaticMarkup(React.createElement(Report, props));
    assert.equal((html.match(/>gold<\/td>/g) || []).length, 2);
    assert.match(html, /<thead>/); assert.match(html, /print:hidden/); assert.match(html, /Team: Selected Team/); assert.doesNotMatch(html, /undefined/);
    console.log('Team report rendering: passed');
} finally { await unlink(fixture).catch(() => {}); }
