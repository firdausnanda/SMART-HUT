import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import StagingImportPreview from "@/Components/StagingImportPreview";

export default function ImportPreview({ auth, batch, rows, forestType }) {
    const formatNumber = (val) => {
        if (!val || isNaN(val)) return val || "-";
        return new Intl.NumberFormat("id-ID").format(val);
    };

    const columns = [
        { header: "Tahun", key: "tahun" },
        { header: "Bulan", key: "bulan_angka" },
        { header: "Kabupaten", key: "nama_kabupaten" },
    ];

    if (forestType === "Hutan Rakyat") {
        columns.push({ header: "Kecamatan", key: "nama_kecamatan" });
    } else if (forestType === "Perhutanan Sosial") {
        columns.push({ header: "Pengelola Wisata", key: "nama_pengelola_wisata" });
    } else if (forestType === "Hutan Negara") {
        columns.push({ header: "Pengelola Hutan", key: "nama_pengelola_hutan" });
    }

    columns.push({ header: "Total Target (m3)", key: "total_target_m3", format: formatNumber });

    const orderedNames = [
        "Jati", "Sengon", "Mahoni", "Gmelina", "Sonokeling", "Pinus", 
        "Akasia", "Mindi", "Balsa", "Jabon", "Kayu Lainnya"
    ];

    orderedNames.forEach(name => {
        const key = name.toLowerCase().replace(/ /g, "_") + "_realisasi";
        columns.push({ header: `${name} (m3)`, key: key, format: formatNumber });
    });

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import Hasil Hutan Kayu ({forestType})</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("hasil-hutan-kayu.commit-import", batch.id)}
                        cancelUrl={route("hasil-hutan-kayu.index", { forest_type: forestType })}
                        statusUrl={route("import.status", batch.id)}
                        title={`Preview Data Hasil Hutan Kayu`}
                        description={`Menampilkan data dari file "${batch.filename}" untuk jenis ${forestType}. Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}






