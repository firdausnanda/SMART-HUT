import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StagingImportPreview from '@/Components/StagingImportPreview';
import { Head } from '@inertiajs/react';

const columns = [
    { header: 'Tahun', key: 'tahun' },
    { header: 'Bulan', key: 'bulan_angka' },
    { header: 'Kabupaten/Kota', key: 'nama_kabupaten' },
    { header: 'Kecamatan', key: 'nama_kecamatan' },
    { header: 'Desa/Kelurahan', key: 'nama_desa' },
    { header: 'Nama lokasi', key: 'nama_lokasi' },
    { header: 'Koordinat', key: 'koordinat' },
    { header: 'Realisasi (Ha)', key: 'realisasi_ha' },
    { header: 'Sumber dana', key: 'sumber_dana' },
];

export default function ImportPreview({ auth, batch, rows, validCount }) {
    return <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-gray-800">Pratinjau Import Penanaman</h2>}>
        <Head title="Pratinjau Import Penanaman" />
        <div className="mx-auto max-w-7xl">
            <StagingImportPreview
                batch={batch}
                rows={rows}
                validCount={validCount}
                columns={columns}
                commitUrl={route('penanaman.commit-import', batch.id)}
                cancelUrl={route('penanaman.index')}
                statusUrl={route('import.status', batch.id)}
                title="Pratinjau Data Penanaman"
                description="Periksa hasil validasi sebelum menyimpan. Hanya baris valid yang akan dibuat sebagai draft; target tahunan dikelola terpisah."
            />
        </div>
    </AuthenticatedLayout>;
}
