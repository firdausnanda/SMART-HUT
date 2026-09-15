import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const columns = [
        { header: "Tahun", key: "tahun" },
        { header: "Bulan", key: "bulan_angka_1_12" },
        { header: "Kab/Kota", key: "nama_kabupatenkota" },
        { header: "Kecamatan", key: "nama_kecamatan" },
        { header: "Desa", key: "nama_desa" },
        { header: "Pengelola Wisata", key: "nama_pengelola_wisata" },
        { header: "Fungsi Kawasan", key: "fungsi_kawasan" },
        { header: "Jumlah Kejadian", key: "jumlah_kejadian" },
        { header: "Luas (Ha)", key: "luas_kebakaran_ha" }
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import Kebakaran Hutan</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("kebakaran-hutan.commit-import", batch.id)}
                        cancelUrl={route("kebakaran-hutan.index")}
                        title={`Preview Data Kebakaran Hutan`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

