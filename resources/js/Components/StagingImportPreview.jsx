import React, { useState, useEffect, useRef } from "react";
import { router } from "@inertiajs/react";
import Swal from "sweetalert2";
import Pagination from "@/Components/Pagination";
import { AlertCircle, CheckCircle2, XCircle, ArrowLeft, Save, FileSpreadsheet, Loader2 } from "lucide-react";
import LoadingOverlay from "@/Components/LoadingOverlay";

export default function StagingImportPreview({
    batch,
    rows,
    columns,
    commitUrl,
    cancelUrl,
    statusUrl = null,
    title = "Preview Import Data",
    description = "Silakan tinjau kembali data di bawah ini sebelum menyimpannya ke database utama. Data yang memiliki status tidak valid akan diabaikan dan tidak disimpan."
}) {
    const [isLoading, setIsLoading] = useState(false);
    const [isPolling, setIsPolling] = useState(false);
    const [pollStatus, setPollStatus] = useState(null); // null | 'processing' | 'completed' | 'failed'
    const [importedCount, setImportedCount] = useState(0);
    const pollRef = useRef(null);

    const stopPolling = () => {
        if (pollRef.current) {
            clearInterval(pollRef.current);
            pollRef.current = null;
        }
        setIsPolling(false);
    };

    const startPolling = (url) => {
        setIsPolling(true);
        setPollStatus('processing');

        pollRef.current = setInterval(async () => {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) { stopPolling(); return; }
                const data = await res.json();

                setImportedCount(data.imported_count || 0);
                setPollStatus(data.status);

                if (data.status === 'completed') {
                    stopPolling();
                    setIsLoading(false);
                    Swal.fire({
                        title: 'Berhasil!',
                        text: `${data.imported_count || 0} baris data berhasil diimport.`,
                        icon: 'success',
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = cancelUrl;
                    });
                } else if (data.status === 'failed') {
                    stopPolling();
                    setIsLoading(false);
                    Swal.fire({
                        title: 'Import Gagal',
                        text: data.error_message || 'Terjadi kesalahan saat memproses import.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444',
                    });
                }
            } catch (e) {
                // Network error — keep polling
            }
        }, 2000);
    };

    // Cleanup on unmount
    useEffect(() => () => stopPolling(), []);

    const handleCommit = () => {
        Swal.fire({
            title: "Proses Data Valid?",
            text: "Hanya baris dengan status valid yang akan dimasukkan ke database utama.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#10b981",
            confirmButtonText: "Ya, Proses Sekarang",
            cancelButtonText: "Batal",
            borderRadius: "1rem",
            customClass: { confirmButton: "font-bold", cancelButton: "font-bold" }
        }).then((result) => {
            if (result.isConfirmed) {
                setIsLoading(true);
                router.post(commitUrl, {}, {
                    onSuccess: () => {
                        // Server dispatched job, now start polling if statusUrl provided
                        if (statusUrl) {
                            startPolling(statusUrl);
                        } else {
                            setIsLoading(false);
                        }
                    },
                    onError: () => setIsLoading(false),
                    onFinish: () => {
                        if (!statusUrl) setIsLoading(false);
                    },
                });
            }
        });
    };

    const handleCancel = () => {
        Swal.fire({
            title: "Batalkan Import?",
            text: "Seluruh data preview ini akan dihapus dan diabaikan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            confirmButtonText: "Ya, Batalkan",
            cancelButtonText: "Kembali",
            borderRadius: "1rem",
            customClass: { confirmButton: "font-bold", cancelButton: "font-bold" }
        }).then((result) => {
            if (result.isConfirmed) router.get(cancelUrl);
        });
    };

    const totalValid = rows.total || 0;
    const progressPct = totalValid > 0 ? Math.round((importedCount / totalValid) * 100) : 0;

    return (
        <div className="space-y-6 relative">
            <LoadingOverlay isLoading={isLoading && !isPolling} text="Mengirim permintaan..." />

            {/* Polling Progress Overlay */}
            {isPolling && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                    <div className="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
                        <div className="flex flex-col items-center text-center gap-4">
                            {pollStatus === 'completed' ? (
                                <CheckCircle2 size={48} className="text-emerald-500" />
                            ) : (
                                <Loader2 size={48} className="text-emerald-500 animate-spin" />
                            )}
                            <div>
                                <h3 className="text-xl font-black text-gray-900">
                                    {pollStatus === 'completed' ? 'Import Selesai!' : 'Memproses Import...'}
                                </h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    {pollStatus === 'completed'
                                        ? `${importedCount} data berhasil diimport. Mengalihkan...`
                                        : 'Import berjalan di background. Mohon tunggu sebentar.'}
                                </p>
                            </div>
                            {/* Progress bar */}
                            <div className="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div
                                    className="h-full bg-emerald-500 rounded-full transition-all duration-500"
                                    style={{ width: pollStatus === 'completed' ? '100%' : `${Math.max(progressPct, 5)}%` }}
                                />
                            </div>
                            <p className="text-xs font-bold text-gray-400 tabular-nums">
                                {importedCount} / {totalValid} baris diproses
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Header Card */}
            <div className="bg-white rounded-xl border border-gray-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div className="flex items-start gap-4">
                    <div className="p-3 bg-emerald-50 text-emerald-600 rounded-xl hidden sm:flex items-center justify-center">
                        <FileSpreadsheet size={32} strokeWidth={1.5} />
                    </div>
                    <div>
                        <h2 className="text-xl md:text-2xl font-black text-gray-900 tracking-tight">{title}</h2>
                        <p className="text-sm text-gray-500 mt-2 max-w-2xl leading-relaxed">{description}</p>
                        <div className="mt-4 flex flex-wrap items-center gap-4 text-xs font-bold text-gray-400 uppercase tracking-wide">
                            <span className="flex items-center gap-1.5"><CheckCircle2 size={16} className="text-emerald-500"/>Data Valid Disimpan</span>
                            <span className="flex items-center gap-1.5"><XCircle size={16} className="text-red-500"/>Data Invalid Dilewati</span>
                        </div>
                    </div>
                </div>
                <div className="flex flex-col sm:flex-row items-center gap-3 shrink-0">
                    <button onClick={handleCancel} className="w-full sm:w-auto px-5 py-2.5 flex items-center justify-center gap-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-300 font-bold text-sm transition-all shadow-sm">
                        <ArrowLeft size={18} /> Batal
                    </button>
                    <button onClick={handleCommit} disabled={isLoading || isPolling} className="w-full sm:w-auto px-6 py-2.5 flex items-center justify-center gap-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 font-bold text-sm transition-all shadow-[0_4px_12px_rgba(16,185,129,0.3)] hover:shadow-[0_6px_16px_rgba(16,185,129,0.4)] hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none">
                        <Save size={18} /> Proses Data Valid
                    </button>
                </div>
            </div>

            {/* Table Card */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden w-full">
                <div className="overflow-x-auto w-full">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-widest text-[11px] font-black">
                            <tr>
                                <th className="px-6 py-4 w-16 text-center sticky left-0 z-20 bg-gray-50 shadow-[4px_0_10px_rgba(0,0,0,0.03)]">Baris</th>
                                {columns.map((col, idx) => (
                                    <th key={idx} className="px-6 py-4">{col.header}</th>
                                ))}
                                <th className="px-6 py-4 text-center sticky right-0 z-20 bg-gray-50 shadow-[-4px_0_10px_rgba(0,0,0,0.03)] w-48 border-l border-gray-100">Status Validasi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {rows.data.map((row) => {
                                const data = row.data_payload ? (typeof row.data_payload === 'string' ? JSON.parse(row.data_payload) : row.data_payload) : {};
                                const isError = row.status === "invalid";
                                const errors = row.validation_errors ? (typeof row.validation_errors === 'string' ? JSON.parse(row.validation_errors) : row.validation_errors) : [];
                                return (
                                    <tr key={row.id} className={`transition-colors group ${isError ? "bg-red-50/40 hover:bg-red-50/80" : "hover:bg-gray-50"}`}>
                                        <td className={`px-6 py-4 font-black text-center sticky left-0 z-10 transition-colors shadow-[4px_0_10px_rgba(0,0,0,0.03)] ${isError ? "bg-[#fef2f2] group-hover:bg-[#fee2e2] text-red-400" : "bg-white group-hover:bg-gray-50 text-gray-400"}`}>
                                            {row.row_number}
                                        </td>
                                        {columns.map((col, idx) => (
                                            <td key={idx} className="px-6 py-4">
                                                <div className={`font-semibold truncate max-w-[200px] xl:max-w-xs ${isError ? "text-red-900" : "text-gray-700"}`} title={data[col.key] || "-"}>
                                                    {col.format ? col.format(data[col.key], data) : (data[col.key] || "-")}
                                                </div>
                                            </td>
                                        ))}
                                        <td className={`px-6 py-4 text-center sticky right-0 z-10 border-l border-gray-100 transition-colors shadow-[-4px_0_10px_rgba(0,0,0,0.03)] align-top ${isError ? "bg-[#fef2f2] group-hover:bg-[#fee2e2]" : "bg-white group-hover:bg-gray-50"}`}>
                                            {isError ? (
                                                <div className="flex flex-col items-center gap-2">
                                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-red-100 text-red-700 text-[11px] font-black border border-red-200 shadow-sm">
                                                        <AlertCircle size={14} /> INVALID
                                                    </span>
                                                    <div className="text-[10px] text-red-600 font-bold max-w-[180px] whitespace-normal leading-relaxed text-center bg-red-50 p-2 rounded-lg border border-red-100">
                                                        {errors.join(", ")}
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="flex h-full items-center justify-center pt-1">
                                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[11px] font-black border border-emerald-200 shadow-sm">
                                                        <CheckCircle2 size={14} /> VALID
                                                    </span>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                            {rows.data.length === 0 && (
                                <tr>
                                    <td colSpan={columns.length + 2} className="text-center py-20">
                                        <div className="flex flex-col items-center justify-center text-gray-400">
                                            <FileSpreadsheet size={56} strokeWidth={1} className="mb-4 text-gray-300" />
                                            <p className="font-bold text-gray-600 text-lg">Tidak ada data untuk ditampilkan</p>
                                            <p className="text-sm font-medium mt-1">Silakan upload ulang file Excel Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="px-6 py-4 border-t border-gray-100 bg-white flex flex-col sm:flex-row flex-wrap justify-between items-center gap-4">
                    <Pagination links={rows.links} />
                    {rows.total > 0 && (
                        <div className="text-sm text-gray-500 font-medium">
                            Menampilkan <span className="font-bold text-gray-900">{rows.from}</span> - <span className="font-bold text-gray-900">{rows.to}</span> dari <span className="font-bold text-gray-900">{rows.total}</span> baris
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

