import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows }) {
    const columns = [
        { header: "Nama Kabupaten", key: "nama_kabupatenkota" },
        { header: "Nama Kecamatan", key: "nama_kecamatan" },
        { header: "Nama KUPS", key: "nama_kups" },
        { header: "Kategori", key: "kategori" },
        { header: "Komoditas", key: "komoditas" },
    ];

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import KUPS</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("kups.commit-import", batch.id)}
                        cancelUrl={route("kups.index")}
                        title={`Preview Data KUPS`}
                        description={`Menampilkan data dari file "${batch.filename}". Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama KUPS.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

