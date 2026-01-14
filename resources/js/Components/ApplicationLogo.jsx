export default function ApplicationLogo({ className = '', collapsed = false, ...props }) {
    return (
        <div className={`relative group flex items-center ${collapsed ? 'justify-center' : 'gap-3'} ${className}`} {...props}>
            <div className="relative w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/20 shadow-sm overflow-hidden p-0.5 shrink-0">
                <img src="/img/logo.webp" alt="Logo CDK" className="w-full h-full object-contain" />
            </div>
            {!collapsed && (
                <div className="flex flex-col">
                    <span className="font-bold text-md text-white tracking-snug leading-snug whitespace-nowrap">
                        CDK Trenggalek
                    </span>
                    <span className="text-[10px] uppercase tracking-wider text-primary-200 font-bold leading-none mt-0.5">
                        Dinas Kehutanan
                    </span>
                </div>
            )}
        </div>
    );
}
