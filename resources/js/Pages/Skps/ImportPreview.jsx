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
        { header: "Kabupaten/Kota", key: "nama_kabupatenkota" },
        { header: "Kecamatan", key: "nama_kecamatan" },
        { header: "Nama Kelompok", key: "nama_kelompok" },
        { header: "Skema Perhutanan Sosial", key: "nama_skema_perhutanan_sosial" },
        { header: "Potensi", key: "potensi", format: (val, row) => val || row.potensi_ha || "-" },
        { header: "Luas PS (Ha)", key: "luas_ps_ha", format: formatNumber },
        { header: "Jumlah KK", key: "jumlah_kk", format: formatNumber },
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import SK PS</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("skps.commit-import", batch.id)}
                        cancelUrl={route("skps.index")}
                        title={`Preview Data SK PS`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

