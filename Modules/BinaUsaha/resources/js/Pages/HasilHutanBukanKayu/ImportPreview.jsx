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
        columns.push({ header: "Pengelola Hutan", key: "nama_pengelola" });
    }

    columns.push({ header: "Total Target", key: "total_target", format: formatNumber });

    const orderedNames = [
        "Bambu", "Getah Pinus", "Daun Kayu Putih", "Porang", "Kopi",
        "Madu", "Durian", "Alpukat", "Jahe", "Kunyit"
    ];

    orderedNames.forEach(name => {
        const slug = name.toLowerCase().replace(/ /g, "_");
        const realKey = slug + "_realisasi";
        const unitKey = slug + "_satuan";
        
        // Custom format to combine number and unit
        const formatWithUnit = (val, rowData) => {
            if (!val || isNaN(val)) return "-";
            const num = new Intl.NumberFormat("id-ID").format(val);
            const unit = rowData[unitKey] || "kg";
            return `${num} ${unit}`;
        };

        columns.push({ header: `${name}`, key: realKey, format: formatWithUnit });
    });

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Preview Import Hasil Hutan Bukan Kayu ({forestType})</h2>}
        >
            <Head title={`Preview Import ${batch.filename}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <StagingImportPreview
                        batch={batch}
                        rows={rows}
                        columns={columns}
                        commitUrl={route("hasil-hutan-bukan-kayu.commit-import", batch.id)}
                        cancelUrl={route("hasil-hutan-bukan-kayu.index", { forest_type: forestType })}
                        statusUrl={route("import.status", batch.id)}
                        title={`Preview Data Hasil Hutan Bukan Kayu`}
                        description={`Menampilkan data dari file "${batch.filename}" untuk jenis ${forestType}. Silakan tinjau kembali data di bawah ini sebelum menyimpannya secara permanen ke database utama.`}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


