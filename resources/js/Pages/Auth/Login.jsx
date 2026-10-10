import { useEffect, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import './login.css';

function GoogleIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
        </svg>
    );
}

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        username: '',
        password: '',
        remember: false,
    });
    const [showPassword, setShowPassword] = useState(false);

    useEffect(() => () => reset('password'), []);

    const submit = (event) => {
        event.preventDefault();
        post(route('login'));
    };

    return (
        <>
            <Head title="Masuk" />
            <main className="login-page">
                <aside className="login-side-panel" aria-labelledby="login-brand-title">
                    <div className="login-side-background" aria-hidden="true">
                        <img src="/img/login/bg.avif" alt="" />
                        <div className="login-side-overlay" />
                        <div className="login-side-shape login-side-shape-top" />
                        <div className="login-side-shape login-side-shape-bottom" />
                    </div>
                    <div className="login-side-content">
                        <Link className="login-side-logo" href="/" aria-label="Kembali ke beranda SMART-HUT">
                            <img src="/img/logo.webp" alt="" width="80" height="80" />
                        </Link>
                        <h1 id="login-brand-title"><span className="login-brand-smart">SMART</span><span className="login-brand-dash">-</span>HUT</h1>
                        <p>Sistem Monitoring Analisis <em>Real Time</em> Data Kehutanan</p>
                        <small>© 2026 Dinas Kehutanan Provinsi Jawa Timur</small>
                    </div>
                </aside>

                <section className="login-form-panel" aria-labelledby="login-form-title">
                    <div className="login-form-section">
                        <Link className="login-mobile-logo" href="/" aria-label="Kembali ke beranda SMART-HUT">
                            <img src="/img/logo.webp" alt="" width="48" height="48" />
                        </Link>
                        <div className="login-form-heading">
                            <p className="login-form-overline">AKSES SMART-HUT</p>
                            <h2 id="login-form-title">Masuk ke akun Anda</h2>
                            <p>Gunakan username dan password yang terdaftar.</p>
                        </div>

                        {status && <p className="login-status" role="status">{status}</p>}

                        <form onSubmit={submit} className="login-form">
                            <div className="login-field">
                                <label htmlFor="username">Username</label>
                                <input
                                    id="username"
                                    type="text"
                                    name="username"
                                    value={data.username}
                                    autoComplete="username"
                                    autoFocus
                                    placeholder="Masukkan username"
                                    aria-invalid={Boolean(errors.username)}
                                    aria-describedby={errors.username ? 'username-error' : undefined}
                                    onChange={(event) => setData('username', event.target.value)}
                                />
                                {errors.username && <p className="login-error" id="username-error">{errors.username}</p>}
                            </div>

                            <div className="login-field">
                                <div className="login-field-heading">
                                    <label htmlFor="password">Password</label>
                                    {canResetPassword && <Link href={route('password.request')}>Lupa password?</Link>}
                                </div>
                                <div className="login-password-wrap">
                                    <input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        name="password"
                                        value={data.password}
                                        autoComplete="current-password"
                                        placeholder="Masukkan password"
                                        aria-invalid={Boolean(errors.password)}
                                        aria-describedby={errors.password ? 'password-error' : undefined}
                                        onChange={(event) => setData('password', event.target.value)}
                                    />
                                    <button
                                        type="button"
                                        className="login-password-toggle"
                                        aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                                        aria-pressed={showPassword}
                                        onClick={() => setShowPassword((current) => !current)}
                                    >
                                        {showPassword ? (
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M3 3l18 18M10.6 5.1A10.9 10.9 0 0112 5c4.6 0 8.5 2.9 10 7a11 11 0 01-3.2 4.6M6.4 6.4A11 11 0 002 12c1.5 4.1 5.4 7 10 7 1.4 0 2.8-.3 4-.8M9.9 9.9a3 3 0 004.2 4.2" /></svg>
                                        ) : (
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M2 12c1.5-4.1 5.4-7 10-7s8.5 2.9 10 7c-1.5 4.1-5.4 7-10 7S3.5 16.1 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                        )}
                                    </button>
                                </div>
                                {errors.password && <p className="login-error" id="password-error">{errors.password}</p>}
                            </div>

                            <label className="login-remember">
                                <input type="checkbox" name="remember" checked={data.remember} onChange={(event) => setData('remember', event.target.checked)} />
                                <span>Ingat saya di perangkat ini</span>
                            </label>

                            <button className="login-submit" type="submit" disabled={processing} aria-busy={processing}>
                                {processing && <span className="login-submit-spinner" aria-hidden="true" />}
                                {processing ? 'Memproses...' : 'Masuk Dashboard'}
                                {!processing && <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" /></svg>}
                            </button>

                            <div className="login-divider"><span>atau</span></div>

                            <a className="login-google" href={route('socialite.redirect', 'google')}>
                                <GoogleIcon />
                                Masuk dengan Google
                            </a>
                        </form>
                    </div>
                </section>
            </main>
        </>
    );
}
