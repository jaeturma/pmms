import assert from 'node:assert/strict';
import { readFile, writeFile, unlink } from 'node:fs/promises';
import ts from 'typescript';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

const fixture = new URL('./.direct-versus-render.mjs', import.meta.url);
const attributionFixture = new URL('./.versus-attribution-render.mjs', import.meta.url);
const compile = (source) => ts.transpileModule(source, { compilerOptions: { jsx: ts.JsxEmit.ReactJSX, module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
try {
    const attribution = await readFile(new URL('../../resources/js/components/result-attribution.tsx', import.meta.url), 'utf8');
    await writeFile(attributionFixture, compile(attribution));
    const source = await readFile(new URL('../../resources/js/pages/results/index.tsx', import.meta.url), 'utf8');
    const parsed = ts.createSourceFile('index.tsx', source, ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
    const form = parsed.statements.find((node) => ts.isFunctionDeclaration(node) && node.name?.text === 'DirectResultForm');
    assert.ok(form);
    const support = `import React, { useState, useEffect } from 'react';
import { AttributionFields, emptyAttribution } from './.versus-attribution-render.mjs';
const shell = (tag) => ({ children }) => React.createElement(tag, null, children);
const Head = () => null;
const PageHeader = ({title}) => React.createElement('h1', null, title);
const Button = shell('button'), Label = shell('label'), Select = shell('select'), SelectTrigger = shell('span'), SelectValue = shell('span'), SelectContent = shell('span'), SelectItem = shell('option'), Badge = shell('span');
const Input = ({value, type, required, placeholder, ...rest}) => React.createElement('input', {value, type, required, placeholder, readOnly: true});
const InputError = () => null, Award = () => null, FileUp = () => null, Send = () => null;
export let transformed;
const useForm = (data) => ({ data, setData(){}, post(){}, processing: false, errors: {}, reset(){}, transform(fn){ transformed = fn(data); } });
`;
    await writeFile(fixture, compile(`${support}\nexport ${form.getText(parsed)}`));
    const module = await import(fixture.href);
    for (const team of [true, false]) {
        const props = { onOpenChange(){}, events: [{id: 10, label: 'Event', meet_id: 1, is_team_event: team, default_result_type: 'versus'}], delegations: [{id:1,label:'Team A'}, {id:2,label:'Team B'}], result: {id:1,event_id:10,result_type:'versus',measurement_type:'points',placements:[{rank:1,delegation_id:1,mark:'3.5'},{rank:2,delegation_id:2,mark:'2.5'}]} };
        const html = renderToStaticMarkup(React.createElement(module.DirectResultForm, props));
        assert.match(html, />Winner</);
        assert.match(html, />Loser</);
        assert.doesNotMatch(html, />Gold<|>Silver<|>Bronze<|1st place|2nd place|First Place|Second Place/);
        assert.match(html, /Measurement Type/);
        assert.match(html, /Score/); assert.match(html, /Points/); assert.match(html, /Time/); assert.match(html, /Distance/);
        assert.equal((html.match(/>Team A<\/option>/g) ?? []).length, 2);
        assert.equal((html.match(/>Team B<\/option>/g) ?? []).length, 2);
        assert.equal((html.match(/aria-label="Athlete \(optional\)"/g) ?? []).length, team ? 0 : 2);
        assert.equal(module.transformed.winner_value, '3.5');
        assert.equal(module.transformed.loser_value, '2.5');
        assert.equal(module.transformed.result_type, 'versus');
        assert.ok(!('gold_count' in module.transformed));
        assert.ok(!('bronze_delegation_id' in module.transformed));
    }
    console.log('Passed versus form rendering and submission-shape checks for team and individual events.');
} finally {
    await unlink(fixture).catch(() => {});
    await unlink(attributionFixture).catch(() => {});
}
