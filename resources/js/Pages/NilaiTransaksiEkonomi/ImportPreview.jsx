import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const columns = [
        { header: "Tahun", key: "tahun" },
        { header: "Bulan", key: "bulan_1_12", format: (val, row) => val || row.bulan || "-" },
        { header: "Nama KTH", key: "nama_kth" },
        { header: "Kabupaten/Kota", key: "nama_kabupaten", format: (val, row) => val || row.kabupatenkota || "-" },
        { header: "Kecamatan", key: "nama_kecamatan", format: (val, row) => val || row.kecamatan || "-" },
        { header: "Desa", key: "nama_desa", format: (val, row) => val || row.desa || "-" },
        { header: "Komoditas", key: "komoditas" },
        { header: "Volume Produksi", key: "volume_produksi" },
        { header: "Satuan", key: "satuan" },
        { header: "Nilai Transaksi (Rp)", key: "nilai_transaksi_rp" },
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import Nilai Transaksi Ekonomi</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("nilai-transaksi-ekonomi.commit-import", batch.id)}
                        cancelUrl={route("nilai-transaksi-ekonomi.index")}
                        title={`Preview Data Nilai Transaksi Ekonomi`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

