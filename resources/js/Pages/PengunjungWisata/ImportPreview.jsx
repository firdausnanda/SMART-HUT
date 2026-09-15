import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const formatNumber = (val) => {
        if (!val || isNaN(val)) return val || "-";
        return new Intl.NumberFormat("id-ID").format(val);
    };

    const formatRupiah = (val) => {
        if (!val || isNaN(val)) return val || "-";
        return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);
    };

    const columns = [
        { header: "Tahun", key: "tahun" },
        { header: "Bulan", key: "bulan_angka_1_12" },
        { header: "Pengelola Wisata", key: "nama_pengelola_wisata" },
        { header: "Jumlah Pengunjung", key: "jumlah_pengunjung", format: formatNumber },
        { header: "Pendapatan Bruto (Rp)", key: "pendapatan_bruto_rp", format: formatRupiah }
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import Pengunjung Wisata</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("pengunjung-wisata.commit-import", batch.id)}
                        cancelUrl={route("pengunjung-wisata.index")}
                        title={`Preview Data Pengunjung Wisata`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

