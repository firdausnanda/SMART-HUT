import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = fileURLToPath(new URL('../..', import.meta.url));
const centralPages = path.join(projectRoot, 'resources/js/Pages');
const ownership = {
    Perlindungan: ['KebakaranHutan', 'PengunjungWisata'],
    Rhl: ['RhlTeknis', 'RehabLahan', 'RehabManggrove', 'ReboisasiPs', 'PenghijauanLingkungan'],
    BinaUsaha: ['HasilHutanKayu', 'HasilHutanBukanKayu', 'Pbphh', 'RealisasiPnbp'],
    Pemberdayaan: ['Kups', 'Skps', 'PerkembanganKth', 'NilaiEkonomi', 'NilaiTransaksiEkonomi'],
    Kepegawaian: ['Kepegawaian'],
    Master: ['MasterData'],
    Dashboard: ['Dashboard.jsx', 'Public'],
};

function filesUnder(directory, extension) {
    if (!existsSync(directory)) return [];

    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const entryPath = path.join(directory, entry.name);
        if (entry.isDirectory()) return filesUnder(entryPath, extension);
        return entry.name.endsWith(extension) ? [entryPath] : [];
    });
}

for (const [moduleName, groups] of Object.entries(ownership)) {
    test(`${moduleName} owns its React pages`, () => {
        for (const group of groups) {
            const destination = path.join(projectRoot, 'Modules', moduleName, 'resources/js/Pages', group);
            assert.ok(existsSync(destination), `Halaman belum dipindah: ${destination}`);
            assert.ok(!existsSync(path.join(centralPages, group)), `Salinan halaman masih ada: ${group}`);
        }
    });
}

test('every Inertia render name has exactly one React page', () => {
    const roots = [centralPages, ...Object.keys(ownership).map((name) =>
        path.join(projectRoot, 'Modules', name, 'resources/js/Pages'))];
    const pageSources = new Map();

    for (const root of roots) {
        for (const file of filesUnder(root, '.jsx')) {
            const name = path.relative(root, file).replaceAll('\\', '/').replace(/\.jsx$/, '');
            pageSources.set(name, [...(pageSources.get(name) ?? []), file]);
        }
    }

    const phpRoots = ['app', 'Modules', 'routes'].map((name) => path.join(projectRoot, name));
    const renderedNames = new Set();
    for (const root of phpRoots) {
        for (const file of filesUnder(root, '.php')) {
            const source = readFileSync(file, 'utf8');
            for (const match of source.matchAll(/Inertia::render\(\s*['"]([^'"]+)['"]/g)) {
                renderedNames.add(match[1]);
            }
        }
    }

    assert.ok(renderedNames.size > 0, 'Tidak ada nama halaman Inertia yang ditemukan');
    for (const name of renderedNames) {
        assert.equal(pageSources.get(name)?.length ?? 0, 1,
            `Halaman Inertia "${name}" harus memiliki tepat satu file JSX`);
    }
});
