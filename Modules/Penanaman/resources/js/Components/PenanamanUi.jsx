import { Link } from '@inertiajs/react';

export const fieldClass = 'mt-2 block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 shadow-sm transition-colors focus:border-primary-600 focus:bg-white focus:ring-primary-600 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500';
export const primaryButton = 'inline-flex min-h-11 items-center justify-center rounded-xl bg-primary-700 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-primary-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-60';
export const secondaryButton = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700';
export const heroButton = 'inline-flex min-h-11 items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-primary-800 transition-colors hover:bg-primary-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';
export const heroSecondaryButton = 'inline-flex min-h-11 items-center justify-center rounded-xl border border-white/40 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';

export function PageHero({ eyebrow, title, description, actions, aside }) {
    return <header className="rounded-2xl bg-gradient-to-r from-primary-800 to-primary-600 p-6 text-white shadow-sm sm:p-8">
        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div className="max-w-2xl">
                {eyebrow && <p className="mb-2 text-xs font-bold uppercase tracking-widest text-primary-100">{eyebrow}</p>}
                <h1 className="font-display text-2xl font-bold leading-tight sm:text-3xl">{title}</h1>
                {description && <p className="mt-2 text-sm leading-relaxed text-primary-100">{description}</p>}
            </div>
            {(actions || aside) && <div className="flex shrink-0 flex-wrap items-center gap-2">{aside}{actions}</div>}
        </div>
    </header>;
}

export function BackLink({ href, children = 'Kembali ke daftar' }) {
    return <Link href={href} className="inline-flex min-h-10 items-center gap-2 text-sm font-bold text-primary-700 hover:text-primary-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-700">
        <span aria-hidden="true">←</span>{children}
    </Link>;
}

export function SectionCard({ title, description, children, className = '' }) {
    return <section className={`overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ${className}`}>
        {(title || description) && <div className="border-b border-gray-100 bg-gray-50/60 px-5 py-4 sm:px-7">
            {title && <h2 className="font-display text-lg font-bold text-gray-900">{title}</h2>}
            {description && <p className="mt-1 text-sm text-gray-600">{description}</p>}
        </div>}
        {children}
    </section>;
}

export function StatusBadge({ status }) {
    return <span className={`inline-flex items-center rounded-lg px-3 py-1 text-xs font-bold ${status === 'final' ? 'bg-primary-50 text-primary-800' : 'bg-amber-100 text-amber-900'}`}>
        {status === 'final' ? 'Final' : 'Draft'}
    </span>;
}

export function FieldError({ message }) {
    return message ? <p role="alert" className="mt-1.5 text-sm font-medium text-red-700">{message}</p> : null;
}

export function FlashMessage({ success, error }) {
    return <>
        {success && <p role="status" className="rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-900">{success}</p>}
        {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{error}</p>}
    </>;
}
