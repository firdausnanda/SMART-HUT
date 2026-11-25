import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import { BackLink, FieldError, fieldClass, FlashMessage, PageHero, primaryButton, SectionCard } from '../../Components/PenanamanUi';
import RegencySelect from '../../Components/RegencySelect';

export default function Accounts({ auth, regions }) {
    const { flash } = usePage().props;
    const missing = regions.filter((region) => region.operators.length === 0);
    const { data, setData, post, processing, errors, reset } = useForm({
        regency_id: '', name: '', email: '', username: '', password: '', password_confirmation: '',
    });
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    const editForm = useForm({
        regency_id: '', name: '', email: '', username: '', password: '', password_confirmation: '',
    });
    const openEdit = (operator, region) => {
        editForm.clearErrors();
        editForm.setData({
            regency_id: region.id, name: operator.name, email: operator.email,
            username: operator.username, password: '', password_confirmation: '',
        });
        setEditing(operator);
    };
    const closeEdit = () => {
        if (editForm.processing) return;
        setEditing(null);
        editForm.reset();
        editForm.clearErrors();
    };
    const updateAccount = (event) => {
        event.preventDefault();
        editForm.put(route('penanaman.accounts.update', editing.id), {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(null);
                editForm.reset();
                editForm.clearErrors();
            },
            onError: () => editForm.reset('password', 'password_confirmation'),
        });
    };
    const deleteAccount = () => {
        if (!deleting || deleteProcessing) return;
        setDeleteProcessing(true);
        router.delete(route('penanaman.accounts.destroy', deleting.id), {
            preserveScroll: true,
            onSuccess: () => setDeleting(null),
            onFinish: () => setDeleteProcessing(false),
        });
    };
    const submit = (event) => {
        event.preventDefault();
        post(route('penanaman.accounts.store'), {
            onSuccess: () => reset(),
            onError: () => reset('password', 'password_confirmation'),
        });
    };

    return <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-gray-800">Akun Penanaman Wilayah</h2>}>
        <Head title="Akun Penanaman Wilayah" />
        <div className="mx-auto max-w-6xl space-y-5">
            <BackLink href={route('penanaman.index')} children="Kembali ke laporan" />
            <PageHero eyebrow="Data Penanaman" title="Akun operator wilayah" description="Buat akun operator untuk kabupaten/kota dan kelola aksesnya." aside={<div className="rounded-xl border border-white/30 bg-white/10 px-4 py-3 text-center"><span className="block font-display text-2xl font-bold">{missing.length}</span><span className="text-xs font-medium text-primary-100">wilayah belum memiliki akun</span></div>} />
            <FlashMessage success={flash?.success} error={flash?.error} />

            {missing.length > 0 && <SectionCard title="Buat akun wilayah" description="Isi identitas dan kredensial awal operator. Password tidak ditampilkan lagi setelah akun dibuat.">
                <form onSubmit={submit} className="grid gap-5 p-5 sm:grid-cols-2 sm:p-7">
                    <div className="text-sm font-semibold text-gray-700">
                        <label htmlFor="account-regency">Kabupaten/Kota</label>
                        <RegencySelect id="account-regency" regencies={missing} value={data.regency_id} onChange={(value) => setData('regency_id', value)} required />
                        <FieldError message={errors.regency_id} />
                    </div>
                    <label className="text-sm font-semibold text-gray-700">Nama penanggung jawab<input value={data.name} onChange={(e) => setData('name', e.target.value)} required className={fieldClass} /><FieldError message={errors.name} /></label>
                    <label className="text-sm font-semibold text-gray-700">Email resmi<input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required className={fieldClass} /><FieldError message={errors.email} /></label>
                    <label className="text-sm font-semibold text-gray-700">Username<input value={data.username} onChange={(e) => setData('username', e.target.value)} autoComplete="username" required className={fieldClass} /><FieldError message={errors.username} /></label>
                    <label className="text-sm font-semibold text-gray-700">Password<input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} autoComplete="new-password" minLength="8" required className={fieldClass} /><FieldError message={errors.password} /><span className="mt-1 block text-xs font-normal text-gray-500">Minimal 8 karakter, huruf besar dan kecil, angka, serta simbol.</span></label>
                    <label className="text-sm font-semibold text-gray-700">Konfirmasi password<input type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} autoComplete="new-password" minLength="8" required className={fieldClass} /><FieldError message={errors.password_confirmation} /></label>
                    <div className="sm:col-span-2"><button disabled={processing} className={primaryButton}>{processing ? 'Membuat akun...' : 'Buat akun operator'}</button></div>
                </form>
            </SectionCard>}
            {missing.length === 0 && <p role="status" className="rounded-xl border border-primary-200 bg-primary-50 px-5 py-4 text-sm font-semibold text-primary-900">Semua kabupaten/kota sudah memiliki akun operator awal.</p>}

            <SectionCard title="Status akun wilayah" description="Lihat dan kelola operator yang terdaftar pada setiap kabupaten/kota.">
                <div className="overflow-x-auto"><table className="min-w-full divide-y divide-gray-100 text-sm">
                    <thead className="bg-gray-50/70 text-left text-xs font-bold uppercase tracking-wide text-gray-600"><tr><th scope="col" className="px-5 py-4 sm:px-7">Kabupaten/Kota</th><th scope="col" className="px-5 py-4">Operator</th><th scope="col" className="px-5 py-4">Username</th><th scope="col" className="px-5 py-4">Email</th><th scope="col" className="px-5 py-4">Status</th><th scope="col" className="px-5 py-4">Aksi</th></tr></thead>
                    <tbody className="divide-y divide-gray-100">{regions.map((region) => <tr key={region.id} className="hover:bg-primary-50/30">
                        <td className="px-5 py-4 font-bold text-gray-900 sm:px-7">{region.name}</td>
                        <td className="min-w-48 px-5 py-4 text-gray-700">{region.operators.map((operator) => <div key={operator.id}>{operator.name}</div>)}{region.operators.length === 0 && '-'}</td>
                        <td className="px-5 py-4 text-gray-700">{region.operators.map((operator) => <div key={operator.id}>{operator.username}</div>)}{region.operators.length === 0 && '-'}</td>
                        <td className="px-5 py-4 text-gray-700">{region.operators.map((operator) => <div key={operator.id}>{operator.email}</div>)}{region.operators.length === 0 && '-'}</td>
                        <td className="px-5 py-4"><span className={`inline-flex rounded-lg px-3 py-1 text-xs font-bold ${region.operators.length ? 'bg-primary-50 text-primary-800' : 'bg-amber-100 text-amber-900'}`}>{region.operators.length ? 'Ada akun' : 'Belum ada akun'}</span></td>
                        <td className="px-5 py-4">{region.operators.map((operator) => <div key={operator.id} className="flex items-center gap-3 whitespace-nowrap">
                            <button type="button" onClick={() => openEdit(operator, region)} className="min-h-9 font-bold text-primary-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-700">Ubah</button>
                            <button type="button" onClick={() => setDeleting({ ...operator, regionName: region.name })} className="min-h-9 font-bold text-red-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700">Hapus</button>
                        </div>)}</td>
                    </tr>)}</tbody>
                </table></div>
            </SectionCard>
        </div>
        <Modal show={Boolean(editing)} onClose={closeEdit} closeable={!editForm.processing} maxWidth="lg">
            {editing && <form onSubmit={updateAccount} className="max-h-[calc(100vh-3rem)] overflow-y-auto p-6">
                <h2 className="font-display text-xl font-bold text-gray-900">Ubah akun operator</h2>
                <p className="mt-1 text-sm text-gray-600">Perbarui identitas dan akses akun. Kosongkan password jika tidak ingin mengubahnya.</p>
                <div className="mt-5 grid gap-4 sm:grid-cols-2">
                    <div className="text-sm font-semibold text-gray-700 sm:col-span-2">
                        <label htmlFor="edit-account-regency">Kabupaten/Kota</label>
                        <RegencySelect id="edit-account-regency" regencies={regions.filter((region) => region.id === editForm.data.regency_id || region.operators.length === 0)}
                            value={editForm.data.regency_id} onChange={(value) => editForm.setData('regency_id', value)} required />
                        <FieldError message={editForm.errors.regency_id} />
                    </div>
                    <label className="text-sm font-semibold text-gray-700 sm:col-span-2">Nama penanggung jawab<input value={editForm.data.name} onChange={(e) => editForm.setData('name', e.target.value)} required className={fieldClass} /><FieldError message={editForm.errors.name} /></label>
                    <label className="text-sm font-semibold text-gray-700">Username<input value={editForm.data.username} onChange={(e) => editForm.setData('username', e.target.value)} autoComplete="username" required className={fieldClass} /><FieldError message={editForm.errors.username} /></label>
                    <label className="text-sm font-semibold text-gray-700">Email resmi<input type="email" value={editForm.data.email} onChange={(e) => editForm.setData('email', e.target.value)} required className={fieldClass} /><FieldError message={editForm.errors.email} /></label>
                    <label className="text-sm font-semibold text-gray-700">Password baru<input type="password" value={editForm.data.password} onChange={(e) => editForm.setData('password', e.target.value)} autoComplete="new-password" minLength="8" className={fieldClass} /><FieldError message={editForm.errors.password} /></label>
                    <label className="text-sm font-semibold text-gray-700">Konfirmasi password<input type="password" value={editForm.data.password_confirmation} onChange={(e) => editForm.setData('password_confirmation', e.target.value)} autoComplete="new-password" minLength="8" className={fieldClass} /><FieldError message={editForm.errors.password_confirmation} /></label>
                </div>
                <div className="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button type="button" onClick={closeEdit} disabled={editForm.processing} className="min-h-11 rounded-xl border border-gray-200 px-5 text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:opacity-60">Batal</button>
                    <button type="submit" disabled={editForm.processing} className={primaryButton}>{editForm.processing ? 'Menyimpan...' : 'Simpan perubahan'}</button>
                </div>
            </form>}
        </Modal>
        <Modal show={Boolean(deleting)} onClose={() => setDeleting(null)} closeable={!deleteProcessing} maxWidth="md">
            {deleting && <div className="p-6">
                <h2 className="font-display text-xl font-bold text-gray-900">Hapus akun operator?</h2>
                <p className="mt-3 text-sm leading-6 text-gray-700">Akun <strong>{deleting.name}</strong> untuk {deleting.regionName} tidak dapat digunakan lagi. Laporan wilayah tetap tersimpan.</p>
                <div className="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button type="button" onClick={() => setDeleting(null)} disabled={deleteProcessing} className="min-h-11 rounded-xl border border-gray-200 px-5 text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:opacity-60">Batal</button>
                    <button type="button" onClick={deleteAccount} disabled={deleteProcessing} className="min-h-11 rounded-xl bg-red-700 px-5 text-sm font-bold text-white hover:bg-red-800 disabled:opacity-60">{deleteProcessing ? 'Menghapus...' : 'Hapus akun'}</button>
                </div>
            </div>}
        </Modal>
    </AuthenticatedLayout>;
}
