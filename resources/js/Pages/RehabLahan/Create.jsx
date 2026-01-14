import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        year: new Date().getFullYear(),
        month: new Date().getMonth() + 1,
        target_annual: '',
        realization: '',
        fund_source: '',
        village: '',
        district: '',
        coordinates: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('rehab-lahan.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Input Data Rehabilitasi Lahan</h2>}
        >
            <Head title="Input Rehabilitasi Lahan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Year */}
                                    <div>
                                        <InputLabel htmlFor="year" value="Tahun" />
                                        <TextInput
                                            id="year"
                                            type="number"
                                            className="mt-1 block w-full"
                                            value={data.year}
                                            onChange={(e) => setData('year', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.year} className="mt-2" />
                                    </div>

                                    {/* Month */}
                                    <div>
                                        <InputLabel htmlFor="month" value="Bulan" />
                                        <select
                                            id="month"
                                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            value={data.month}
                                            onChange={(e) => setData('month', e.target.value)}
                                        >
                                            {[...Array(12)].map((_, i) => (
                                                <option key={i + 1} value={i + 1}>{new Date(0, i).toLocaleString('id-ID', { month: 'long' })}</option>
                                            ))}
                                        </select>
                                        <InputError message={errors.month} className="mt-2" />
                                    </div>

                                    {/* Target */}
                                    <div>
                                        <InputLabel htmlFor="target_annual" value="Target Tahunan (Ha)" />
                                        <TextInput
                                            id="target_annual"
                                            type="number"
                                            step="0.01"
                                            className="mt-1 block w-full"
                                            value={data.target_annual}
                                            onChange={(e) => setData('target_annual', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.target_annual} className="mt-2" />
                                    </div>

                                    {/* Realization */}
                                    <div>
                                        <InputLabel htmlFor="realization" value="Realisasi Bulan Ini (Ha)" />
                                        <TextInput
                                            id="realization"
                                            type="number"
                                            step="0.01"
                                            className="mt-1 block w-full"
                                            value={data.realization}
                                            onChange={(e) => setData('realization', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.realization} className="mt-2" />
                                    </div>

                                    {/* Fund Source */}
                                    <div>
                                        <InputLabel htmlFor="fund_source" value="Sumber Dana" />
                                        <TextInput
                                            id="fund_source"
                                            className="mt-1 block w-full"
                                            value={data.fund_source}
                                            onChange={(e) => setData('fund_source', e.target.value)}
                                            placeholder="APBD / APBN / DAK"
                                            required
                                        />
                                        <InputError message={errors.fund_source} className="mt-2" />
                                    </div>

                                    {/* Village */}
                                    <div>
                                        <InputLabel htmlFor="village" value="Desa" />
                                        <TextInput
                                            id="village"
                                            className="mt-1 block w-full"
                                            value={data.village}
                                            onChange={(e) => setData('village', e.target.value)}
                                        />
                                        <InputError message={errors.village} className="mt-2" />
                                    </div>

                                    {/* District */}
                                    <div>
                                        <InputLabel htmlFor="district" value="Kecamatan" />
                                        <TextInput
                                            id="district"
                                            className="mt-1 block w-full"
                                            value={data.district}
                                            onChange={(e) => setData('district', e.target.value)}
                                        />
                                        <InputError message={errors.district} className="mt-2" />
                                    </div>
                                    
                                     {/* Coordinates */}
                                     <div>
                                        <InputLabel htmlFor="coordinates" value="Koordinat" />
                                        <TextInput
                                            id="coordinates"
                                            className="mt-1 block w-full"
                                            value={data.coordinates}
                                            onChange={(e) => setData('coordinates', e.target.value)}
                                            placeholder="-7.12345, 110.12345"
                                        />
                                        <InputError message={errors.coordinates} className="mt-2" />
                                    </div>
                                </div>

                                <div className="flex justify-end">
                                    <PrimaryButton className="ml-4" disabled={processing}>
                                        Simpan Data
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
