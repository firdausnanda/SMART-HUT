export function createPageRegistry(loaders) {
    const registry = new Map();

    for (const [sourcePath, load] of Object.entries(loaders)) {
        const name = sourcePath.replaceAll('\\', '/').match(/\/Pages\/(.+)\.jsx$/)?.[1];
        if (!name) {
            throw new Error(`Path halaman Inertia tidak valid: ${sourcePath}`);
        }

        const existing = registry.get(name);
        if (existing) {
            throw new Error(`Halaman Inertia ganda "${name}": ${existing.sourcePath} dan ${sourcePath}`);
        }

        registry.set(name, { sourcePath, load });
    }

    return registry;
}

export function resolvePage(registry, name) {
    const page = registry.get(name);
    if (!page) {
        throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
    }

    return page.load();
}
