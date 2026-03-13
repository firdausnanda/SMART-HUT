import React, { useState, useEffect, useMemo } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler } from 'chart.js';

// Imported YoY Slide Components
import YoYPembinaanSlide from './Components/YoYPembinaanSlide';
import YoYKebakaranSlide from './Components/YoYKebakaranSlide';
import YoYJasaLingkunganSlide from './Components/YoYJasaLingkunganSlide';
import YoYProductionSlide from './Components/YoYProductionSlide';
import YoYPnbpSlide from './Components/YoYPnbpSlide';
import YoYKelembagaanSlide from './Components/YoYKelembagaanSlide';

// Utils
import { truncateName } from './Components/utils';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler);

export default function PublicYoYDashboard({ years, stats }) {
  const { auth } = usePage().props;
  const [currentSlide, setCurrentSlide] = useState(0);
  const [isProfileOpen, setIsProfileOpen] = useState(false);

  const modules = useMemo(() => [
    { id: 0, title: 'Yoy: Rehabilitasi Lahan', color: 'bg-emerald-600', text: 'text-emerald-600' },
    { id: 1, title: 'Yoy: Penghijauan Lingkungan', color: 'bg-teal-600', text: 'text-teal-600' },
    { id: 2, title: 'Yoy: Rehabilitasi Mangrove', color: 'bg-cyan-600', text: 'text-cyan-600' },
    { id: 3, title: 'Yoy: Bangunan Konservasi Tanah dan Air', color: 'bg-orange-600', text: 'text-orange-600' },
    { id: 4, title: 'Yoy: Reboisasi Area Perhutanan Sosial', color: 'bg-pink-600', text: 'text-pink-600' },
    { id: 5, title: 'Yoy: Kebakaran Hutan', color: 'bg-red-600', text: 'text-red-600' },
    { id: 6, title: 'Yoy: Jasa Lingkungan', color: 'bg-indigo-600', text: 'text-indigo-600' },
    { id: 7, title: 'Yoy: Produksi Hutan Negara', color: 'bg-blue-600', text: 'text-blue-600' },
    { id: 8, title: 'Yoy: Produksi Perhutanan Sosial', color: 'bg-sky-600', text: 'text-sky-600' },
    { id: 9, title: 'Yoy: Produksi Hutan Rakyat', color: 'bg-cyan-600', text: 'text-cyan-600' },
    { id: 10, title: 'Yoy: PNBP', color: 'bg-amber-600', text: 'text-amber-600' },
    { id: 11, title: 'Yoy: Kelembagaan Perhutanan Sosial', color: 'bg-emerald-600', text: 'text-emerald-600' },
    { id: 12, title: 'Yoy: Kelembagaan Hutan Rakyat', color: 'bg-lime-600', text: 'text-lime-600' },
  ], []);

  const pembinaanSections = useMemo(() => [
    { label: 'Rehabilitasi Lahan', key: 'rehab', color: '#10b981', unit: 'Ha' },
    { label: 'Penghijauan Lingkungan', key: 'penghijauan', color: '#14b8a6', unit: 'Ha' },
    { label: 'Rehabilitasi Mangrove', key: 'manggrove', color: '#06b6d4', unit: 'Ha' },
    { label: 'Bangunan KTA', key: 'rhl_teknis', color: '#f97316', unit: 'Unit' },
    { label: 'Reboisasi Area Perhutanan Sosial', key: 'reboisasi', color: '#ec4899', unit: 'Ha' },
  ], []);

  const productionSources = useMemo(() => [
    { key: 'hutan_negara', id: 6, title: 'Hutan Negara', color: '#2563eb' },
    { key: 'perhutanan_sosial', id: 7, title: 'Perhutanan Sosial', color: '#0ea5e9' },
    { key: 'hutan_rakyat', id: 8, title: 'Hutan Rakyat', color: '#0891b2' }
  ], []);

  const nextSlide = () => setCurrentSlide((prev) => (prev + 1) % modules.length);
  const prevSlide = () => setCurrentSlide((prev) => (prev - 1 + modules.length) % modules.length);

  // Auto-slide effect
  useEffect(() => {
    const slideInterval = setInterval(() => {
      if (document.visibilityState === 'visible') {
        nextSlide();
      }
    }, 10000); // 10 seconds per slide

    return () => clearInterval(slideInterval);
  }, [modules.length]);

  // Data reload effect
  useEffect(() => {
    const reloadInterval = setInterval(() => {
      if (document.visibilityState === 'visible') {
        router.reload({
          only: ['stats'],
          preserveScroll: true,
          preserveState: true,
        });
      }
    }, 1 * 60 * 1000); // 1 minute

    return () => clearInterval(reloadInterval);
  }, []);

  const commonOptions = useMemo(() => ({
    responsive: true,
    plugins: { legend: { position: 'bottom', labels: { font: { family: "'Inter', sans-serif" } } } },
    scales: { y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } }, x: { grid: { display: false } } }
  }), []);

  return (
    <div className="min-h-screen bg-gray-50 text-gray-800 font-sans flex flex-col relative">
      <Head title="Tren Infografis Multi-Year" />

      {/* Navbar */}
      <nav className="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-20">
            <div className="flex items-center gap-4">
              <Link href="/" className="flex items-center gap-3 group">
                <div className="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-gray-100 group-hover:scale-105 transition-transform">
                  <img src="/img/logo.webp" alt="Logo" className="w-8 h-8 object-contain" />
                </div>
                <div className="hidden md:flex flex-col">
                  <span className="font-display font-bold text-lg text-gray-900 leading-tight">Dashboard Multi-Year</span>
                  <span className={`text-[10px] uppercase tracking-wider font-bold ${modules[currentSlide]?.text}`}>
                    CDK Wilayah Trenggalek
                  </span>
                </div>
              </Link>
            </div>

            <div className="flex items-center gap-6">
              <div className="flex items-center gap-2 px-4 py-2 bg-emerald-50 rounded-full border border-emerald-100">
                <span className="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Periode:</span>
                <span className="text-xs font-bold text-emerald-700">{years[years.length - 1]} - {years[0]}</span>
              </div>

              {/* Profile Dropdown */}
              <div className="relative">
                <button
                  onClick={() => setIsProfileOpen(!isProfileOpen)}
                  className="flex items-center gap-2 sm:gap-3 pl-1 sm:pl-2 pr-1 sm:pr-3 py-1 sm:py-1.5 rounded-full hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100 focus:outline-none"
                >
                  <img
                    src={auth.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.name || 'User')}&background=random`}
                    alt="Profile"
                    className="w-10 h-10 rounded-full object-cover shadow-sm border border-gray-100"
                  />
                  <div className="flex flex-col items-start hidden sm:flex">
                    <span className="text-sm font-bold text-gray-700 leading-none">{truncateName(auth.user?.name)}</span>
                    <span className="text-[10px] text-gray-500 font-medium uppercase tracking-wider">{auth.user?.roles?.[0] || 'User'}</span>
                  </div>
                  <svg className={`w-4 h-4 text-gray-400 transition-transform duration-200 ${isProfileOpen ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                {isProfileOpen && (
                  <>
                    <div className="fixed inset-0 z-40" onClick={() => setIsProfileOpen(false)}></div>
                    <div className="absolute right-0 mt-3 w-60 bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-gray-100 py-3 z-50 animate-in fade-in zoom-in-95 duration-200 origin-top-right">
                      <div className="px-5 py-3 border-b border-gray-50 mb-2">
                        <p className="text-sm font-bold text-gray-900 truncate">{auth.user?.name}</p>
                        <p className="text-xs text-gray-500 truncate">{auth.user?.email}</p>
                      </div>

                      {auth.user?.roles?.length > 0 && (
                        <Link
                          href={route('dashboard')}
                          className="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors hover:text-emerald-600"
                        >
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                          Kelola Data
                        </Link>
                      )}

                      <Link
                        href={route('public.dashboard')}
                        className="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors hover:text-emerald-600"
                      >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        Infografis Tren Tahunan
                      </Link>

                      <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="w-full flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                      >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        Sign Out
                      </Link>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </nav>

      {/* Main Content (Carousel Area) */}
      <main className="flex-1 flex flex-col justify-center py-8 relative overflow-hidden">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full z-10">

          {/* Carousel Navigation & Header */}
          <div className="flex items-center justify-between mb-8">
            <button onClick={prevSlide} className="p-2 rounded-full hover:bg-gray-200 transition-colors text-gray-400 hover:text-gray-900">
              <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
            </button>

            <div className="text-center">
              <h2 className={`text-4xl font-black mb-2 transition-colors duration-500 tracking-tight ${modules[currentSlide]?.text}`}>
                {modules[currentSlide]?.title}
              </h2>
              <div className="flex justify-center gap-2 mt-4">
                {modules.map((m, i) => (
                  <button
                    key={m.id}
                    onClick={() => setCurrentSlide(i)}
                    className={`h-1.5 rounded-full transition-all duration-300 ${currentSlide === i ? `w-8 ${m.color}` : 'w-2 bg-gray-300'}`}
                  />
                ))}
              </div>
            </div>

            <button onClick={nextSlide} className="p-2 rounded-full hover:bg-gray-200 transition-colors text-gray-400 hover:text-gray-900">
              <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" /></svg>
            </button>
          </div>

          {/* Slides Content (Sliding Track) */}
          <div className="relative overflow-hidden min-h-[600px]">
            <div
              className="flex transition-transform duration-700 ease-in-out will-change-transform"
              style={{ transform: `translateX(-${currentSlide * 100}%)` }}
            >
              {/* Slides 0-4: Pembinaan Sections Multi-Year */}
              {pembinaanSections.map((sec, idx) => (
                <YoYPembinaanSlide
                  key={`pembinaan-${idx}`}
                  label={sec.label}
                  secKey={sec.key}
                  years={years}
                  stats={stats}
                  color={sec.color}
                  unit={sec.unit}
                  commonOptions={commonOptions}
                />
              ))}

              {/* Slide 5: Kebakaran Hutan Multi-Year */}
              <YoYKebakaranSlide
                years={years}
                stats={stats}
                commonOptions={commonOptions}
              />

              {/* Slide 6: Jasa Lingkungan Multi-Year */}
              <YoYJasaLingkunganSlide
                years={years}
                stats={stats}
                commonOptions={commonOptions}
              />

              {/* Slides 7-9: Production Multi-Year */}
              {productionSources.map((source) => (
                <YoYProductionSlide
                  key={source.key}
                  source={source}
                  years={years}
                  stats={stats}
                  commonOptions={commonOptions}
                />
              ))}

              {/* Slide 9: PNBP Multi-Year */}
              <YoYPnbpSlide
                years={years}
                stats={stats}
                commonOptions={commonOptions}
              />

              {/* Slide 10: Kelembagaan PS Multi-Year */}
              <YoYKelembagaanSlide
                type="ps"
                years={years}
                stats={stats}
                commonOptions={commonOptions}
              />

              {/* Slide 11: Kelembagaan HR Multi-Year */}
              <YoYKelembagaanSlide
                type="hr"
                years={years}
                stats={stats}
                commonOptions={commonOptions}
              />

            </div>
          </div>

        </div>
      </main>
    </div>
  );
}
