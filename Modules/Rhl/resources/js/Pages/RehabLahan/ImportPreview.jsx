import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const columns = [
        { header: "Tahun", key: "tahun" },
        { header: "Bulan", key: "bulan_angka" },
        { header: "Kabupaten", key: "nama_kabupaten" },
        { header: "Kecamatan", key: "nama_kecamatan" },
        { header: "Target (Ha)", key: "target_tahunan_ha" },
        { header: "Realisasi (Ha)", key: "realisasi_ha" },
        { header: "Sumber Dana", key: "sumber_dana" }
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import Rehab Lahan</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("rehab-lahan.commit-import", batch.id)}
                        cancelUrl={route("rehab-lahan.index")}
                        statusUrl={route("import.status", batch.id)}
                        title={`Preview Data Rehab Lahan`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}




