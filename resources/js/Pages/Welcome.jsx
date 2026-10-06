import { Head, Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { eastJavaDistrictPaths, eastJavaPath, eastJavaProjection } from './jawaTimurGeometry';
import './welcome.css';

const pageTitle = 'SMART-HUT | Sistem Monitoring Data Kehutanan Jawa Timur';
const pageDescription = 'SMART-HUT adalah platform Dinas Kehutanan Provinsi Jawa Timur untuk monitoring dan analisis data kehutanan.';

const initialView = { zoom: 1, x: 0, y: 0 };

function constrain(view) {
    if (view.zoom === 1) return initialView;
    const limit = 8;
    return {
        ...view,
        x: Math.max(100 - view.zoom * 100 - limit, Math.min(limit, view.x)),
        y: Math.max(100 - view.zoom * 100 - limit, Math.min(limit, view.y)),
    };
}

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

    return (
        <div className="welcome-visual">
            <div className="welcome-visual-header">
                <span className="welcome-visual-index">JAWA TIMUR</span>
            </div>
            <figure className="welcome-map-figure">
                <div className="welcome-map-viewport" data-dragging={dragging} data-zoomed={view.zoom > 1}
                    onPointerDown={startDrag} onPointerMove={moveDrag} onPointerUp={stopDrag} onPointerCancel={stopDrag}
                    aria-label="Peta interaktif Jawa Timur">
                    <div className="welcome-map-art" style={{ transform: `translate(${view.x}%, ${view.y}%) scale(${view.zoom})` }}>
                        <svg viewBox={`0 0 ${eastJavaProjection.width} ${eastJavaProjection.height}`} role="img" aria-label="Peta Jawa Timur dengan batas kabupaten dan kota" preserveAspectRatio="xMidYMid meet">
                            <defs><clipPath id="welcome-east-java-clip"><path d={eastJavaPath} /></clipPath></defs>
                            <path className="welcome-map-depth" d={eastJavaPath} transform="translate(0 11)" />
                            <path className="welcome-map-land" d={eastJavaPath} />
                            <g clipPath="url(#welcome-east-java-clip)">
                                {eastJavaDistrictPaths.map((district, index) => <path key={district.name} className="welcome-map-district" d={district.path} pathLength="1" style={{ '--district-order': index }} />)}
                            </g>
                            <path className="welcome-map-coast" d={eastJavaPath} pathLength="1" />
                        </svg>
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
