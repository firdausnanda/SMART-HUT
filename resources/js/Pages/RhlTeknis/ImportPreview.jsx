import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const columns = [
        { header: "Tahun", key: "tahun" },
        { header: "Bulan", key: "bulan_angka" },
        { header: "Kabupaten", key: "kabupaten" },
        { header: "Kecamatan", key: "kecamatan" },
        { header: "Desa", key: "desa" },
        { header: "Target (Unit)", key: "target_tahunan_unit" },
        { header: "Sumber Dana", key: "sumber_dana" },
        { header: "Jenis Bangunan", key: "jenis_bangunan" },
        { header: "Jumlah Unit", key: "jumlah_unit" }
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import RHL Teknis</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("rhl-teknis.commit-import", batch.id)}
                        cancelUrl={route("rhl-teknis.index")}
                        title={`Preview Data RHL Teknis`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

