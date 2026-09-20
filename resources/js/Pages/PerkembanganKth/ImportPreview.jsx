import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const formatNumber = (val) => {
        if (!val || isNaN(val)) return val || "-";
        return new Intl.NumberFormat("id-ID").format(val);
    };

    const columns = [
        { header: "Tahun", key: "tahun" },
        { header: "Bulan", key: "bulan_angka", format: (val, row) => val || row.bulan_1_12 || row.bulan || "-" },
        { header: "Kab/Kota", key: "nama_kabupaten", format: (val, row) => val || row.kabupatenkota || "-" },
        { header: "Kecamatan", key: "nama_kecamatan", format: (val, row) => val || row.kecamatan || "-" },
        { header: "Desa", key: "nama_desa", format: (val, row) => val || row.desa || "-" },
        { header: "Nama KTH", key: "nama_kth" },
        { header: "No. Register", key: "nomor_register" },
        { header: "Kelas Kelembagaan", key: "kelas_kelembagaan", format: (val, row) => val || row.kelas_kelembagaan_pemulamadyautama || "-" },
        { header: "Jumlah Anggota", key: "jumlah_anggota", format: formatNumber },
        { header: "Luas Kelola (Ha)", key: "luas_kelola_ha", format: (val, row) => formatNumber(val || row.luas_kelola || "-") },
        { header: "Potensi Kawasan", key: "potensi_kawasan" },
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import Perkembangan KTH</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("perkembangan-kth.commit-import", batch.id)}
                        cancelUrl={route("perkembangan-kth.index")}
                        statusUrl={route("import.status", batch.id)}
                        title={`Preview Data Perkembangan KTH`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}




