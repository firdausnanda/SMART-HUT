import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const formatNumber = (val) => {
        if (!val || isNaN(val)) return val || "-";
        return new Intl.NumberFormat("id-ID").format(val);
    };

    const formatCurrency = (val) => {
        if (!val || isNaN(val)) return val || "-";
        return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);
    };

    const columns = [
        { header: "Nama Industri", key: "nama_industri" },
        { header: "Nomor Izin", key: "nomor_izin" },
        { header: "Kabupaten/Kota", key: "nama_kabupatenkota" },
        { header: "Kecamatan", key: "nama_kecamatan" },
        { header: "Nilai Investasi", key: "nilai_investasi", format: formatCurrency },
        { header: "Tenaga Kerja", key: "jumlah_tenaga_kerja", format: formatNumber },
        { header: "Kondisi", key: "kondisi_saat_ini" },
        { header: "Jenis Produksi (Kapasitas)", key: "jenis_produksi_kapasitas" },
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import PBPHH</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("pbphh.commit-import", batch.id)}
                        cancelUrl={route("pbphh.index")}
                        statusUrl={route("import.status", batch.id)}
                        title={`Preview Data PBPHH`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


