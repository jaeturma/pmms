import assert from 'node:assert/strict';
import { readFile, writeFile, unlink } from 'node:fs/promises';
import ts from 'typescript';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

const compile = (source) =>
    ts.transpileModule(source, {
        compilerOptions: {
            jsx: ts.JsxEmit.ReactJSX,
            module: ts.ModuleKind.ESNext,
            target: ts.ScriptTarget.ES2022,
        },
    }).outputText;

const abbrFixture = new URL(
    './.team-abbreviations-render.mjs',
    import.meta.url,
);
const boardFixture = new URL('./.medal-board-render.mjs', import.meta.url);

try {
    // --- teamAbbreviation(): the pure mapping ------------------------------
    const abbrSource = await readFile(
        new URL(
            '../../resources/js/apps/portal/lib/team-abbreviations.ts',
            import.meta.url,
        ),
        'utf8',
    );
    await writeFile(abbrFixture, compile(abbrSource));
    const { teamAbbreviation } = await import(abbrFixture.href);

    assert.equal(teamAbbreviation('Compostela'), 'Com');
    assert.equal(teamAbbreviation('Nabunturan'), 'Nab');
    assert.equal(teamAbbreviation('New Bataan'), 'NwB');
    assert.equal(teamAbbreviation('Monkayo'), 'Mnk');
    // Unknown / unexpected Team name → full name, never blank or broken.
    assert.equal(teamAbbreviation('Sampletown'), 'Sampletown');
    assert.equal(teamAbbreviation(''), '');
    // Tolerates incidental whitespace in stored names.
    assert.equal(teamAbbreviation('  Maco  '), 'Mac');

    // --- PortalMedalBoard: label + responsive Team name rendering ---------
    const boardSource = await readFile(
        new URL(
            '../../resources/js/apps/portal/components/medal-board.tsx',
            import.meta.url,
        ),
        'utf8',
    );
    const support = `import React, { useMemo } from 'react';
import { teamAbbreviation } from './.team-abbreviations-render.mjs';
const Link = ({ href, children, ...rest }) => React.createElement('a', { href, ...rest }, children);
const Medal = () => null;
const MunicipalityCrest = ({ name }) => React.createElement('span', { 'data-crest': name });
const PortalAnimatedNumber = ({ value }) => value;
const usePortalFlipRows = () => ({ rowRef: () => () => {}, reduced: true });
const teamShow = (slug) => ({ url: '/teams/' + slug });
const cn = (...a) => a.filter(Boolean).join(' ');
`;
    // Drop the component's own import lines (mocked in `support`), keep the
    // rest of the module — MEDALS, PortalMedalBoard, rowKey — verbatim.
    const stripped = boardSource
        .split('\n')
        .filter((line) => !/^import\s/.test(line))
        .join('\n');
    await writeFile(boardFixture, compile(`${support}\n${stripped}`));
    const { PortalMedalBoard } = await import(boardFixture.href);

    const rows = [
        {
            district: 'Compostela',
            district_id: 1,
            slug: 'compostela',
            gold: 5,
            silver: 2,
            bronze: 1,
            total: 8,
        },
        {
            district: 'Nabunturan',
            district_id: 2,
            slug: null,
            gold: 3,
            silver: 3,
            bronze: 0,
            total: 6,
        },
        {
            district: 'Sampletown',
            district_id: 3,
            slug: null,
            gold: 0,
            silver: 0,
            bronze: 1,
            total: 1,
        },
    ];
    const html = renderToStaticMarkup(
        React.createElement(PortalMedalBoard, { rows, animate: false }),
    );

    // Visible column label is "Team", never "Delegation".
    assert.match(html, /<th[^>]*>Team<\/th>/);
    assert.doesNotMatch(html, /Delegation/);

    // Both the full name (tablet/desktop span) and the mobile short label
    // are present in the markup — CSS switches which one shows.
    assert.match(html, /class="sm:hidden">Com</);
    assert.match(html, /class="hidden sm:inline">Compostela</);
    assert.match(html, /class="sm:hidden">Nab</);
    assert.match(html, /class="hidden sm:inline">Nabunturan</);

    // Unknown Team → full name in BOTH spans (safe fallback).
    assert.match(html, /class="sm:hidden">Sampletown</);
    assert.match(html, /class="hidden sm:inline">Sampletown</);

    console.log('tally-medal-board.mjs: OK');
} finally {
    await unlink(abbrFixture).catch(() => {});
    await unlink(boardFixture).catch(() => {});
}
