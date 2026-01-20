import { Link, Head } from '@inertiajs/react';

export default function NotFound() {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-6 relative overflow-hidden">
      <Head title="404 - Halaman Tidak Ditemukan" />

      {/* Background Decoration */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-primary-100 rounded-full blur-3xl opacity-60"></div>
        <div className="absolute top-[20%] -right-[5%] w-[30%] h-[30%] bg-emerald-100 rounded-full blur-3xl opacity-60"></div>
      </div>

      <div className="max-w-xl w-full relative z-10">
        <div className="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-8 md:p-12 text-center transform transition-all hover:scale-[1.01] duration-500">

          {/* Illustration Icon */}
          <div className="flex justify-center mb-8">
            <div className="relative">
              <div className="absolute inset-0 bg-gradient-to-tr from-primary-100 to-emerald-100 rounded-full blur-xl transform scale-150"></div>
              <div className="relative bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <svg className="w-16 h-16 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
            </div>
          </div>
          <h1 className="text-5xl md:text-6xl font-black text-gray-900 tracking-tight mb-2 font-display">
            404
          </h1>

          <h2 className="text-xl md:text-2xl font-bold text-gray-800 mb-6">
            Halaman Tidak Ditemukan
          </h2>

          <p className="text-gray-500 mb-14 leading-relaxed text-base md:text-lg max-w-lg mx-auto">
            Mohon maaf, halaman yang Anda tuju tidak dapat ditemukan. Mungkin halaman telah dipindahkan atau dihapus.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center items-stretch w-full max-w-md mx-auto">
            <Link
              href="/"
              className="flex-1 px-6 py-4 bg-primary-700 text-white font-bold rounded-xl hover:bg-primary-800 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 group text-base"
            >
              <svg className="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Ke Beranda
            </Link>

            <button
              onClick={() => window.history.back()}
              className="flex-1 px-6 py-4 bg-white text-gray-700 font-bold rounded-xl border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all duration-300 flex items-center justify-center gap-2 text-base"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
              </svg>
              Kembali
            </button>
          </div>

          <div className="mt-12 pt-8 border-t border-gray-100">
            <p className="text-xs text-gray-400 font-medium tracking-wide uppercase">
              Sistem Informasi Kehutanan Dalam Angka
            </p>
          </div>
        </div>
      </div>

      {/* Footer decoration */}
      <div className="absolute bottom-0 w-full h-1 bg-gradient-to-r from-primary-400 via-emerald-500 to-primary-600"></div>
    </div>
  );
}
