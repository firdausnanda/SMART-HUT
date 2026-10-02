import test from 'node:test';
import assert from 'node:assert/strict';

import { createPageRegistry, resolvePage } from '../../resources/js/pageRegistry.js';

test('central and module pages retain their Inertia names and load lazily', async () => {
    const loaded = [];
    const registry = createPageRegistry({
        './Pages/Profile/Edit.jsx': () => { loaded.push('profile'); return Promise.resolve('profile page'); },
        '../../Modules/Perlindungan/resources/js/Pages/KebakaranHutan/Index.jsx': () => {
            loaded.push('fire');
            return Promise.resolve('fire page');
        },
    });

    assert.deepEqual(loaded, []);
    assert.equal(await resolvePage(registry, 'Profile/Edit'), 'profile page');
    assert.equal(await resolvePage(registry, 'KebakaranHutan/Index'), 'fire page');
    assert.deepEqual(loaded, ['profile', 'fire']);
});

test('duplicate page names report both source files', () => {
    assert.throws(() => createPageRegistry({
        './Pages/KebakaranHutan/Index.jsx': () => null,
        '../../Modules/Perlindungan/resources/js/Pages/KebakaranHutan/Index.jsx': () => null,
    }), /KebakaranHutan\/Index.*\.\/Pages\/KebakaranHutan\/Index\.jsx.*Modules\/Perlindungan/s);
});

test('missing pages report their Inertia name', () => {
    const registry = createPageRegistry({ './Pages/Profile/Edit.jsx': () => null });
    assert.throws(() => resolvePage(registry, 'KebakaranHutan/Index'), /KebakaranHutan\/Index/);
});
