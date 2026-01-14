import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import ResponsiveTable from '@/Components/ResponsiveTable';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Index({ auth, datas }) {
    const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Rehabilitasi Lahan</h2>}
        >
            <Head title="Rehabilitasi Lahan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex justify-end mb-4">
                        <Link href={route('rehab-lahan.create')}>
                            <PrimaryButton>Input Data Baru</PrimaryButton>
                        </Link>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <ResponsiveTable>
                                <ResponsiveTable.Head>
                                    <ResponsiveTable.Heading isSticky>Bulan</ResponsiveTable.Heading>
                                    <ResponsiveTable.Heading>Tahun</ResponsiveTable.Heading>
                                    <ResponsiveTable.Heading>Lokasi (Desa/Kec)</ResponsiveTable.Heading>
                                    <ResponsiveTable.Heading>Target (Ha)</ResponsiveTable.Heading>
                                    <ResponsiveTable.Heading>Realisasi (Ha)</ResponsiveTable.Heading>
                                    <ResponsiveTable.Heading>Sumber Dana</ResponsiveTable.Heading>
                                    <ResponsiveTable.Heading>Status</ResponsiveTable.Heading>
                                    <ResponsiveTable.Heading>Action</ResponsiveTable.Heading>
                                </ResponsiveTable.Head>
                                <ResponsiveTable.Body>
                                    {datas.data.map((item) => (
                                        <ResponsiveTable.Row key={item.id}>
                                            <ResponsiveTable.Cell isSticky className="bg-white font-medium">
                                                {item.month}
                                            </ResponsiveTable.Cell>
                                            <ResponsiveTable.Cell>{item.year}</ResponsiveTable.Cell>
                                            <ResponsiveTable.Cell>
                                                {item.village}, {item.district}
                                            </ResponsiveTable.Cell>
                                            <ResponsiveTable.Cell>{formatNumber(item.target_annual)}</ResponsiveTable.Cell>
                                            <ResponsiveTable.Cell>{formatNumber(item.realization)}</ResponsiveTable.Cell>
                                            <ResponsiveTable.Cell>{item.fund_source}</ResponsiveTable.Cell>
                                            <ResponsiveTable.Cell>
                                                <StatusBadge status={item.status} />
                                            </ResponsiveTable.Cell>
                                            <ResponsiveTable.Cell>
                                                <Link href={route('rehab-lahan.edit', item.id)} className="text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </Link>
                                            </ResponsiveTable.Cell>
                                        </ResponsiveTable.Row>
                                    ))}
                                    {datas.data.length === 0 && (
                                        <ResponsiveTable.Row>
                                            <ResponsiveTable.Cell colSpan="8" className="text-center py-4 text-gray-500">
                                                Tidak ada data.
                                            </ResponsiveTable.Cell>
                                        </ResponsiveTable.Row>
                                    )}
                                </ResponsiveTable.Body>
                            </ResponsiveTable>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
