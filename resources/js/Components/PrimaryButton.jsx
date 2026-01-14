export default function PrimaryButton({ className = '', disabled, loading, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `relative inline-flex items-center justify-center px-6 py-3 border border-transparent font-bold text-sm tracking-wide uppercase transition-all duration-300 ease-out rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 overflow-hidden shadow-md group ${
                    loading ? 'cursor-wait' : 'hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]'
                } ${
                    disabled || loading ? 'opacity-80 grayscale-[0.5] cursor-not-allowed' : ''
                } ` + className
            }
            disabled={disabled || loading}
        >
            {/* Glossy overlay effect */}
            <div className="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
            
            {/* Loading Spinner */}
            {loading && (
                <div className="absolute inset-0 flex items-center justify-center z-10 bg-inherit">
                    <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span className="ml-2 normal-case font-medium text-white/90">Memproses...</span>
                </div>
            )}
            
            <span className={`flex items-center transition-all duration-300 ${loading ? 'opacity-0 scale-90' : 'opacity-100 scale-100'}`}>
                {children}
            </span>
        </button>
    );
}
