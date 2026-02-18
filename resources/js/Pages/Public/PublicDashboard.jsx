import React, { useState, useEffect, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Select from 'react-select';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler } from 'chart.js';
import LoadingOverlay from '@/Components/LoadingOverlay';

// Imported Slide Components
import PembinaanSlide from './Components/PembinaanSlide';
import KebakaranSlide from './Components/KebakaranSlide';
import JasaLingkunganSlide from './Components/JasaLingkunganSlide';
import ProductionSlide from './Components/ProductionSlide';
import PbphhSlide from './Components/PbphhSlide';
import PnbpSlide from './Components/PnbpSlide';
import KelembagaanPsSlide from './Components/KelembagaanPsSlide';
import KelembagaanHrSlide from './Components/KelembagaanHrSlide';

// Utils
import { truncateName } from './Components/utils';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, PointElement, LineElement, Filler);

export default function PublicDashboard({ currentYear, availableYears, stats }) {
  const { auth } = usePage().props;
  const [currentSlide, setCurrentSlide] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [loadingText, setLoadingText] = useState('Memuat Data...');
  const [isProfileOpen, setIsProfileOpen] = useState(false);

  const modules = useMemo(() => [
    { id: 0, title: 'Rehabilitasi Lahan', color: 'bg-emerald-600', text: 'text-emerald-600' },
    { id: 1, title: 'Penghijauan Lingkungan', color: 'bg-teal-600', text: 'text-teal-600' },
    { id: 2, title: 'Rehabilitasi Mangrove', color: 'bg-cyan-600', text: 'text-cyan-600' },
    { id: 3, title: 'Bangunan Konservasi Tanah dan Air', color: 'bg-orange-600', text: 'text-orange-600' },
    { id: 4, title: 'Reboisasi Area Perhutanan Sosial', color: 'bg-pink-600', text: 'text-pink-600' },
    { id: 5, title: 'Kebakaran Hutan', color: 'bg-red-600', text: 'text-red-600' },
    { id: 6, title: 'Jasa Lingkungan', color: 'bg-indigo-600', text: 'text-indigo-600' },
    { id: 7, title: 'Produksi Hutan Negara', color: 'bg-blue-600', text: 'text-blue-600' },
    { id: 8, title: 'Produksi Perhutanan Sosial', color: 'bg-sky-600', text: 'text-sky-600' },
    { id: 9, title: 'Produksi Hutan Rakyat', color: 'bg-cyan-600', text: 'text-cyan-600' },
    { id: 10, title: 'PBPHH', color: 'bg-slate-600', text: 'text-slate-600' },
    { id: 11, title: 'Penerimaan Negara (PNBP)', color: 'bg-amber-600', text: 'text-amber-600' },
    { id: 12, title: 'Kelembagaan Perhutanan Sosial', color: 'bg-emerald-600', text: 'text-emerald-600' },
    { id: 13, title: 'Kelembagaan Hutan Rakyat', color: 'bg-lime-600', text: 'text-lime-600' },
  ], []);

  const pembinaanSections = useMemo(() => [
    { label: 'Rehabilitasi Lahan', total: stats?.pembinaan?.rehab_total, targetTotal: stats?.pembinaan?.rehab_target_total, chart: stats?.pembinaan?.rehab_chart, targetChart: stats?.pembinaan?.rehab_target_chart, fund: stats?.pembinaan?.rehab_fund, regency: stats?.pembinaan?.rehab_regency, color: '#10b981', unit: 'Ha' },
    { label: 'Penghijauan Lingkungan', total: stats?.pembinaan?.penghijauan_total, targetTotal: stats?.pembinaan?.penghijauan_target_total, chart: stats?.pembinaan?.penghijauan_chart, targetChart: stats?.pembinaan?.penghijauan_target_chart, fund: stats?.pembinaan?.penghijauan_fund, regency: stats?.pembinaan?.penghijauan_regency, color: '#14b8a6', unit: 'Ha' },
    { label: 'Rehabilitasi Mangrove', total: stats?.pembinaan?.manggrove_total, targetTotal: stats?.pembinaan?.manggrove_target_total, chart: stats?.pembinaan?.manggrove_chart, targetChart: stats?.pembinaan?.manggrove_target_chart, fund: stats?.pembinaan?.manggrove_fund, regency: stats?.pembinaan?.manggrove_regency, color: '#06b6d4', unit: 'Ha' },
    { label: 'Bangunan Konservasi Tanah dan Air', total: stats?.pembinaan?.rhl_teknis_total, targetTotal: stats?.pembinaan?.rhl_teknis_target_total, chart: stats?.pembinaan?.rhl_teknis_chart, targetChart: stats?.pembinaan?.rhl_teknis_target_chart, fund: stats?.pembinaan?.rhl_teknis_fund, regency: null, types: stats?.pembinaan?.rhl_teknis_type, color: '#f97316', unit: 'Unit' },
    { label: 'Reboisasi Area PS', total: stats?.pembinaan?.reboisasi_total, targetTotal: stats?.pembinaan?.reboisasi_target_total, chart: stats?.pembinaan?.reboisasi_chart, targetChart: stats?.pembinaan?.reboisasi_target_chart, fund: stats?.pembinaan?.reboisasi_fund, regency: stats?.pembinaan?.reboisasi_regency, pengelola: stats?.pembinaan?.reboisasi_pengelola, color: '#ec4899', unit: 'Ha' },
  ], [stats?.pembinaan]);

  const productionSources = useMemo(() => [
    { key: 'hutan_negara', id: 7, title: 'Hutan Negara', color: '#2563eb', bg: 'bg-blue-600' },
    { key: 'perhutanan_sosial', id: 8, title: 'Perhutanan Sosial', color: '#0ea5e9', bg: 'bg-sky-600' },
    { key: 'hutan_rakyat', id: 9, title: 'Hutan Rakyat', color: '#0891b2', bg: 'bg-cyan-600' }
  ], []);

  const nextSlide = () => setCurrentSlide((prev) => (prev + 1) % modules.length);
  const prevSlide = () => setCurrentSlide((prev) => (prev - 1 + modules.length) % modules.length);

  // Auto-slide effect
  // useEffect(() => {
  //   const interval = setInterval(nextSlide, 25000);
  //   return () => clearInterval(interval);
  // }, []);

  // Auto-reload data every 5 minutes
  useEffect(() => {
    const reloadInterval = setInterval(() => {
      if (document.visibilityState === 'visible') {
        router.reload({
          only: ['stats'],
          preserveScroll: true,
          preserveState: true,
        });
      }
    }, 1 * 60 * 1000); // 1 minutes

    return () => clearInterval(reloadInterval);
  }, []);

  const handleYearChange = (selectedOption) => {
    router.get(route('public.dashboard'), { year: selectedOption.value }, {
      preserveScroll: true,
      onStart: () => {
        setLoadingText('Mengambil Data Tahun ' + selectedOption.value + '...');
        setIsLoading(true);
      },
      onFinish: () => setIsLoading(false),
    });
  };

  const commonOptions = useMemo(() => ({
    responsive: true,
    plugins: { legend: { position: 'bottom', labels: { font: { family: "'Inter', sans-serif" } } } },
    scales: { y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } }, x: { grid: { display: false } } }
  }), []);

  return (
    <div className="min-h-screen bg-gray-50 text-gray-800 font-sans flex flex-col relative">
      <Head title="Dashboard Monitoring Program Kehutanan" />

      {/* Custom Loading Overlay */}
      <LoadingOverlay isLoading={isLoading} text={loadingText} />

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
                  <span className="font-display font-bold text-lg text-gray-900 leading-tight">Dashboard Monitoring</span>
                  <span className={`text-[10px] uppercase tracking-wider font-bold ${modules[currentSlide]?.text}`}>
                    CDK Wilayah Trenggalek
                  </span>
                </div>
              </Link>
            </div>
            <div className="flex items-center gap-4">
              <div className="w-32 sm:w-48">
                <Select
                  value={{ value: currentYear, label: `Tahun ${currentYear}` }}
                  onChange={handleYearChange}
                  options={availableYears ? availableYears.map(y => ({ value: y, label: `Tahun ${y}` })) : [{ value: currentYear, label: `Tahun ${currentYear}` }]}
                  className="text-sm font-bold text-gray-700"
                  placeholder="Pilih Tahun"
                  styles={{
                    control: (base, state) => ({
                      ...base,
                      borderRadius: '0.75rem', // rounded-xl
                      padding: '0.125rem',
                      borderColor: state.isFocused ? '#10b981' : '#e5e7eb', // emerald-500 or gray-200
                      boxShadow: state.isFocused ? '0 0 0 2px rgba(16, 185, 129, 0.2)' : '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                      '&:hover': {
                        borderColor: '#d1d5db' // gray-300
                      }
                    }),
                    option: (base, state) => ({
                      ...base,
                      backgroundColor: state.isSelected ? '#10b981' : state.isFocused ? '#d1fae5' : null, // emerald-500 : emerald-50
                      color: state.isSelected ? 'white' : '#374151',
                      cursor: 'pointer'
                    }),
                    singleValue: (base) => ({
                      ...base,
                      color: '#374151'
                    })
                  }}
                />
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
                          Admin Dashboard
                        </Link>
                      )}

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
              <h2 className={`text-4xl font-bold mb-2 transition-colors duration-500 ${modules[currentSlide]?.text}`}>
                {modules[currentSlide]?.title}
              </h2>
              <div className="flex justify-center gap-2">
                {modules.map((m, i) => (
                  <button
                    key={m.id}
                    onClick={() => setCurrentSlide(i)}
                    className={`h-1.5 rounded-full transition-all duration-300 ${currentSlide === i ? `w-8 ${m.color}` : 'w-2 bg-gray-300'}`}
                    aria-label={`Go to slide ${i + 1}`}
                  />
                ))}
              </div>
            </div>

            <button onClick={nextSlide} className="p-2 rounded-full hover:bg-gray-200 transition-colors text-gray-400 hover:text-gray-900">
              <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" /></svg>
            </button>
          </div>

          {/* Slides Content (Sliding Track) */}
          <div className="relative overflow-hidden min-h-[550px]">
            <div
              className="flex transition-transform duration-700 ease-in-out will-change-transform"
              style={{ transform: `translateX(-${currentSlide * 100}%)` }}
            >

              {/* Slides 0-4: Specific Pembinaan Sections */}
              {pembinaanSections.map((section, idx) => (
                <PembinaanSlide
                  key={`pembinaan-${idx}`}
                  section={section}
                  currentYear={currentYear}
                  commonOptions={commonOptions}
                />
              ))}

              {/* Slide 5: Kebakaran Hutan */}
              <KebakaranSlide
                stats={stats}
                currentYear={currentYear}
                commonOptions={commonOptions}
              />

              {/* Slide 6: Jasa Lingkungan */}
              <JasaLingkunganSlide
                stats={stats}
                currentYear={currentYear}
                commonOptions={commonOptions}
              />

              {/* Slides 7-9: Production Source Breakdown */}
              {productionSources.map((source) => (
                <ProductionSlide
                  key={source.key}
                  source={source}
                  stats={stats}
                  currentYear={currentYear}
                  commonOptions={commonOptions}
                />
              ))}

              {/* Slide 10: PBPHH */}
              <PbphhSlide
                stats={stats}
                commonOptions={commonOptions}
              />

              {/* Slide 11: PNBP */}
              <PnbpSlide
                stats={stats}
                currentYear={currentYear}
                commonOptions={commonOptions}
              />

              {/* Slide 12: Kelembagaan Perhutanan Sosial */}
              <KelembagaanPsSlide
                stats={stats}
                commonOptions={commonOptions}
              />

              {/* Slide 13: Kelembagaan Hutan Rakyat */}
              <KelembagaanHrSlide
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
