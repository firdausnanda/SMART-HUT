import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { createPageRegistry, resolvePage } from './pageRegistry';

const pages = createPageRegistry({
    ...import.meta.glob('./Pages/**/*.jsx'),
    ...import.meta.glob('../../Modules/*/resources/js/Pages/**/*.jsx'),
});

const appName = window.document.querySelector("meta[name='app-name']")?.getAttribute("content") || import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePage(pages, name),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#eab308', // Gold for visibility
        showSpinner: false,
    },
});
