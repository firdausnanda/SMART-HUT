import { Link, Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const pageTitle = 'SMART-HUT | Sistem Monitoring Data Kehutanan Jawa Timur';
const pageDescription = 'SMART-HUT adalah platform Dinas Kehutanan Provinsi Jawa Timur untuk monitoring, pengelolaan, dan pelaporan data kehutanan.';

const CountUp = ({ end, duration }) => {
    const [count, setCount] = useState(0);

function ArrowIcon() {
    return <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" /></svg>;
}

function EastJavaMap() {
    const [view, setView] = useState(initialView);
    const [dragging, setDragging] = useState(false);
    const dragStart = useRef(null);

    const reset = () => setView(initialView);

    const changeZoom = (difference) => {
        setView((current) => {
            const zoom = Math.max(1, Math.min(2.5, current.zoom + difference));
            const centerX = (50 - current.x) / current.zoom;
            const centerY = (50 - current.y) / current.zoom;
            return constrain({ zoom, x: 50 - centerX * zoom, y: 50 - centerY * zoom });
        });
    };

    const startDrag = (event) => {
        if (view.zoom <= 1 || event.pointerType !== 'mouse' || event.button !== 0 || event.target.closest('button')) return;
        event.currentTarget.setPointerCapture(event.pointerId);
        dragStart.current = { x: event.clientX, y: event.clientY, view };
        setDragging(true);
    };

    const moveDrag = (event) => {
        if (!dragStart.current) return;
        const bounds = event.currentTarget.getBoundingClientRect();
        const x = dragStart.current.view.x + ((event.clientX - dragStart.current.x) / bounds.width) * 100;
        const y = dragStart.current.view.y + ((event.clientY - dragStart.current.y) / bounds.height) * 100;
        setView(constrain({ ...dragStart.current.view, x, y }));
    };

    const stopDrag = () => {
        dragStart.current = null;
        setDragging(false);
    };

export default function Welcome({ auth, laravelVersion, phpVersion, totalData = 0, seo }) {
    const homeUrl = seo.homeUrl;
    const imageUrl = `${homeUrl}img/hutan_indonesia.jpeg`;
    const organization = {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'Dinas Kehutanan Provinsi Jawa Timur',
        url: homeUrl,
        logo: `${homeUrl}img/logo.webp`,
    };

    return (
        <>
            <Head title="Sistem Monitoring Data Kehutanan Jawa Timur">
                <meta head-key="description" name="description" content={pageDescription} />
                <link head-key="canonical" rel="canonical" href={homeUrl} />
                <meta head-key="og-type" property="og:type" content="website" />
                <meta head-key="og-locale" property="og:locale" content="id_ID" />
                <meta head-key="og-title" property="og:title" content={pageTitle} />
                <meta head-key="og-description" property="og:description" content={pageDescription} />
                <meta head-key="og-url" property="og:url" content={homeUrl} />
                <meta head-key="og-image" property="og:image" content={imageUrl} />
                <meta head-key="twitter-card" name="twitter:card" content="summary_large_image" />
                <meta head-key="twitter-title" name="twitter:title" content={pageTitle} />
                <meta head-key="twitter-description" name="twitter:description" content={pageDescription} />
                <meta head-key="twitter-image" name="twitter:image" content={imageUrl} />
                <script head-key="organization" type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(organization) }} />
            </Head>
            <div className="min-h-screen bg-white text-gray-800 font-sans selection:bg-primary-500 selection:text-white overflow-hidden">
                {/* Navbar */}
                <nav className="absolute top-0 w-full z-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-20">
                            <div className="flex-shrink-0 flex items-center gap-4">

                                {/* Logo Mark - Enhanced */}
                                <div className="group cursor-pointer flex-shrink-0">
                                    <div className="w-12 h-12 flex items-center justify-center p-0.5">
                                        <img src="/img/logo.webp" alt="Logo CDK" className="w-full h-full object-contain" />
                                    </div>
                                </div>

                                <div className="hidden sm:flex flex-col">
                                    <span className="font-display font-bold text-lg text-gray-900 tracking-tight leading-tight">
                                        Dinas Kehutanan
                                    </span>
                                    <span className="text-[10px] uppercase tracking-wider text-primary-700/80 font-bold">
                                        Provinsi Jawa Timur
                                    </span>
                                </div>
                            </div>

                            {/* Logo Mark - Gerbang Nusantara (Right Side) */}
                            <div className="group cursor-pointer flex-shrink-0">
                                <div className="w-32 h-12 flex items-center justify-center p-0.5">
                                    <img src="/img/logo_gerbang_nusantara.png" alt="Logo Gerbang Nusantara" className="w-full h-full object-contain" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <span className="welcome-map-north" aria-hidden="true"><span>↑</span><small>U</small></span>
                    <div className="welcome-map-controls" role="group" aria-label="Kontrol peta">
                        <button type="button" onClick={() => changeZoom(0.5)} disabled={view.zoom >= 2.5} aria-label="Perbesar peta">+</button>
                        <span aria-live="polite">{Math.round(view.zoom * 100)}%</span>
                        <button type="button" onClick={() => changeZoom(-0.5)} disabled={view.zoom <= 1} aria-label="Perkecil peta">−</button>
                        <button type="button" className="welcome-map-reset" onClick={reset} disabled={view.zoom === 1}>Reset</button>
                    </div>
                </div>
                <figcaption className="welcome-map-caption">
                    <span><i className="welcome-map-key-land" /> Batas provinsi</span>
                    <span><i className="welcome-map-key-district" /> Batas kabupaten/kota</span>
                </figcaption>
            </figure>
            <div className="welcome-visual-footer" id="welcome-map-detail">
                <p aria-live="polite">{view.zoom > 1
                    ? 'Peta Jawa Timur diperbesar. Geser dengan mouse, atau kembalikan tampilan utuh.'
                    : 'Perbesar peta untuk melihat batas kabupaten dan kota lebih jelas.'}</p>
                <button type="button" onClick={view.zoom > 1 ? reset : () => changeZoom(0.5)}>
                    {view.zoom > 1 ? 'Lihat peta utuh' : 'Perbesar peta'} <ArrowIcon />
                </button>
            </div>
        </div>
    );
}

export default function Welcome({ auth, totalData = 0, seo }) {
    const rawCount = Number(totalData);
    const count = Number.isFinite(rawCount) && rawCount > 0 ? rawCount : 0;
    const homeUrl = seo.homeUrl;
    const imageUrl = `${homeUrl}img/hutan_indonesia.jpeg`;
    const organization = {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'Dinas Kehutanan Provinsi Jawa Timur',
        url: homeUrl,
        logo: `${homeUrl}img/logo.webp`,
    };

    return (
        <>
            <Head title="Sistem Monitoring Data Kehutanan Jawa Timur">
                <meta head-key="description" name="description" content={pageDescription} />
                <meta head-key="author" name="author" content="Firdaus Nanda Christian" />
                <link head-key="canonical" rel="canonical" href={homeUrl} />
                <meta head-key="og-type" property="og:type" content="website" />
                <meta head-key="og-locale" property="og:locale" content="id_ID" />
                <meta head-key="og-title" property="og:title" content={pageTitle} />
                <meta head-key="og-description" property="og:description" content={pageDescription} />
                <meta head-key="og-url" property="og:url" content={homeUrl} />
                <meta head-key="og-image" property="og:image" content={imageUrl} />
                <meta head-key="twitter-card" name="twitter:card" content="summary_large_image" />
                <meta head-key="twitter-title" name="twitter:title" content={pageTitle} />
                <meta head-key="twitter-description" name="twitter:description" content={pageDescription} />
                <meta head-key="twitter-image" name="twitter:image" content={imageUrl} />
                <script head-key="organization" type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(organization) }} />
            </Head>
            <main className="welcome-page">
                <section className="welcome-hero" aria-labelledby="welcome-title">
                    <header className="welcome-header">
                        <div className="welcome-agency">
                            <img src="/img/logo.webp" alt="Lambang Provinsi Jawa Timur" width="36" height="48" />
                            <span><strong>Dinas Kehutanan</strong><small>Provinsi Jawa Timur</small></span>
                        </div>
                        <img className="welcome-gerbang-logo" src="/img/logo_gerbang_nusantara.png" alt="Jawa Timur Gerbang Baru Nusantara" width="155" height="44" />
                    </header>
                    <div className="welcome-hero-content">
                        <div className={`welcome-copy${!auth?.user ? ' welcome-copy--guest' : ''}`}>
                            <div className="welcome-copy-intro">
                                <p className="welcome-overline">Dinas Kehutanan Provinsi Jawa Timur</p>
                                <h1 id="welcome-title"><span className="welcome-title-accent">SMART</span>-HUT</h1>
                                <p className="welcome-subtitle">Sistem Monitoring Analisis <em className="italic">Real Time</em> Data Kehutanan</p>
                            </div>
                            <div className="welcome-copy-main">
                                <p className="welcome-description">SMART-HUT menyatukan data kehutanan dalam satu sistem untuk mendukung monitoring dan analisis pengelolaan hutan di Jawa Timur.</p>
                                <div className="welcome-actions">
                                    {auth?.user
                                        ? <Link className="welcome-login-action" href={route('dashboard')}>Masuk Dashboard <ArrowIcon /></Link>
                                        : <Link className="welcome-login-action" href={route('login')}>Masuk Sekarang <ArrowIcon /></Link>}
                                    <Link className="welcome-primary-action" href={route('public.dashboard')}>Infografis Tahun Berjalan <ArrowIcon /></Link>
                                    <Link className="welcome-comparison-action" href={route('public.dashboard-yoy')}><span>Infografis <em className="italic">Year on Year</em></span><ArrowIcon /></Link>
                                </div>
                            </div>
                            <div className="welcome-data">
                                <span className="welcome-data-rule" />
                                <div><strong>{count.toLocaleString('id-ID')}</strong><p><b>Total data terinput</b><br />{count > 0 ? 'Catatan dalam sistem SMART-HUT' : 'Belum ada data terinput'}</p></div>
                            </div>
                        </div>
                        <EastJavaMap />
                    </div>
                    <footer className="welcome-footer">
                        <span>© 2026 Dinas Kehutanan Provinsi Jawa Timur</span>
                        <p>Peta disederhanakan untuk orientasi.</p>
                    </footer>
                </section>
            </main>
        </>
    );
}
