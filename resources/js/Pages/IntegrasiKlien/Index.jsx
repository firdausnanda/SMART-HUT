import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { Plug, KeyRound, Pencil, Trash2, Copy, Check, MapPin, Activity, ShieldAlert, BookOpen } from 'lucide-react';
import ClientFormModal from './ClientFormModal';
import TokenFormModal from './TokenFormModal';

const formatDateTime = (value) =>
  value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : null;

const formatDate = (value) =>
  value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value)) : null;

function TokenStatus({ token }) {
  if (token.is_expired) {
    return <span className="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Kedaluwarsa</span>;
  }
  if (!token.expires_at) {
    return <span className="text-xs text-gray-500">Tanpa batas</span>;
  }
  return <span className="text-xs text-gray-600">s.d. {formatDate(token.expires_at)}</span>;
}

function ClientCard({ client, abilityLabels, onEdit, onIssue, onDelete, onRevoke }) {
  return (
    <article className="bg-white shadow-xl sm:rounded-2xl ring-1 ring-gray-900/5 overflow-hidden">
      <div className="p-5 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="text-lg font-bold text-gray-900">{client.nama}</h3>
            <code className="px-2 py-0.5 rounded-md bg-gray-100 text-xs text-gray-700">{client.kode}</code>
            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
              client.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`}>
              {client.is_active ? 'Aktif' : 'Nonaktif'}
            </span>
          </div>
          {client.deskripsi && <p className="mt-1 text-sm text-gray-600">{client.deskripsi}</p>}
          <dl className="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <div className="flex items-center gap-1.5 text-gray-700">
              <MapPin className="h-4 w-4 text-primary-600" aria-hidden="true" />
              <dt className="sr-only">Cakupan</dt>
              <dd>{client.cdk_nama ?? 'Seluruh provinsi'}</dd>
            </div>
            <div className="flex items-center gap-1.5 text-gray-700">
              <Activity className="h-4 w-4 text-primary-600" aria-hidden="true" />
              <dt className="sr-only">Pemakaian</dt>
              <dd>
                {client.requests_24h.toLocaleString('id-ID')} permintaan / 24 jam
                <span className="text-gray-500">
                  {' · '}terakhir {formatDateTime(client.last_request_at) ?? 'belum pernah'}
                </span>
              </dd>
            </div>
          </dl>
        </div>
        <div className="flex flex-wrap gap-2 shrink-0">
          <button
            type="button"
            onClick={onIssue}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700 transition-colors"
          >
            <KeyRound className="h-4 w-4" aria-hidden="true" /> Terbitkan Token
          </button>
          <button
            type="button"
            onClick={onEdit}
            className="p-2 text-primary-600 hover:bg-primary-100 rounded-lg transition-colors bg-primary-50"
            title="Edit klien"
            aria-label={`Edit ${client.nama}`}
          >
            <Pencil className="h-4 w-4" />
          </button>
          <button
            type="button"
            onClick={onDelete}
            className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors bg-red-50"
            title="Hapus klien"
            aria-label={`Hapus ${client.nama}`}
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      </div>

      {client.tokens.length > 0 ? (
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                {['Token', 'Akses Data', 'Dibuat', 'Terakhir Dipakai', 'Berlaku', ''].map((label) => (
                  <th key={label} scope="col" className="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    {label}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {client.tokens.map((token) => (
                <tr key={token.id} className={token.is_expired ? 'bg-gray-50/60' : ''}>
                  <td className="px-5 py-3 whitespace-nowrap text-sm">
                    <span className="font-semibold text-gray-900">{token.name}</span>
                    <span className="ml-1 text-gray-400">#{token.id}</span>
                  </td>
                  <td className="px-5 py-3 text-sm">
                    <div className="flex flex-wrap gap-1 max-w-md">
                      {token.abilities.map((ability) => (
                        <span
                          key={ability}
                          title={abilityLabels[ability] ?? ability}
                          className="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-primary-50 text-primary-700 border border-primary-100"
                        >
                          {ability}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="px-5 py-3 whitespace-nowrap text-sm text-gray-600">{formatDate(token.created_at)}</td>
                  <td className="px-5 py-3 whitespace-nowrap text-sm text-gray-600">{formatDateTime(token.last_used_at) ?? 'Belum pernah'}</td>
                  <td className="px-5 py-3 whitespace-nowrap"><TokenStatus token={token} /></td>
                  <td className="px-5 py-3 whitespace-nowrap text-right">
                    <button
                      type="button"
                      onClick={() => onRevoke(token)}
                      className="text-sm font-semibold text-red-600 hover:text-red-800"
                    >
                      Cabut
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : (
        <p className="px-6 py-5 text-sm text-gray-500">
          Belum ada token. Terbitkan token agar sistem ini bisa mengakses API.
        </p>
      )}
    </article>
  );
}

function NewTokenModal({ newToken, onClose }) {
  const [copied, setCopied] = useState(false);
  const apiUrl = `${window.location.origin}/api/v1/klien`;

  useEffect(() => setCopied(false), [newToken]);

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(newToken.token);
      setCopied(true);
    } catch {
      setCopied(false);
    }
  };

  return (
    <Modal show={Boolean(newToken)} onClose={onClose} closeable={false}>
      {newToken && (
        <div className="p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="flex items-center justify-center h-10 w-10 rounded-full bg-amber-100">
              <ShieldAlert className="h-5 w-5 text-amber-600" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900">Simpan Token Ini Sekarang</h2>
              <p className="text-sm text-gray-500">
                Token <span className="font-medium text-gray-700">{newToken.name}</span> untuk {newToken.client}
              </p>
            </div>
          </div>

          <p className="text-sm text-gray-700 mb-3">
            Token hanya ditampilkan sekali dan tidak bisa dilihat lagi. Kirim ke pengelola sistem klien lewat kanal yang aman.
            Jika hilang, cabut lalu terbitkan token baru.
          </p>

          <div className="flex items-stretch gap-2">
            <code className="flex-1 min-w-0 break-all rounded-xl bg-gray-900 text-green-300 text-sm px-4 py-3 select-all">
              {newToken.token}
            </code>
            <button
              type="button"
              onClick={copy}
              className="inline-flex items-center gap-1.5 px-4 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
              {copied ? <Check className="h-4 w-4 text-green-600" /> : <Copy className="h-4 w-4" />}
              {copied ? 'Tersalin' : 'Salin'}
            </button>
          </div>

          <p className="mt-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Uji koneksi</p>
          <pre className="mt-1 overflow-x-auto rounded-xl bg-gray-50 border border-gray-200 text-xs text-gray-700 px-4 py-3">
{`curl ${apiUrl} \\
  -H "Authorization: Bearer <token>"`}
          </pre>
          <p className="mt-2 text-xs text-gray-500">
            Bagikan juga tautan{' '}
            <a href={route('docs.api')} target="_blank" rel="noopener" className="font-semibold text-primary-700 hover:underline">
              Dokumentasi API
            </a>{' '}
            ke pengembang sistem klien.
          </p>

          <div className="flex justify-end mt-6">
            <PrimaryButton onClick={onClose} className="bg-primary-600 hover:bg-primary-700 text-white h-[48px]">
              Sudah Saya Simpan
            </PrimaryButton>
          </div>
        </div>
      )}
    </Modal>
  );
}

function ConfirmModal({ confirm, onClose }) {
  const [processing, setProcessing] = useState(false);

  const run = () => {
    setProcessing(true);
    router.delete(confirm.url, {
      preserveScroll: true,
      onSuccess: () => onClose(),
      onFinish: () => setProcessing(false),
    });
  };

  return (
    <Modal show={Boolean(confirm)} onClose={onClose} maxWidth="md">
      {confirm && (
        <div className="p-6 text-center">
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <Trash2 className="h-6 w-6 text-red-600" aria-hidden="true" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">{confirm.title}</h2>
          <p className="text-gray-500 mb-6">{confirm.message}</p>
          <div className="flex justify-center gap-3">
            <SecondaryButton type="button" onClick={onClose} className="rounded-xl px-6 py-3 h-[48px] shadow-sm font-medium">
              Batal
            </SecondaryButton>
            <DangerButton onClick={run} disabled={processing}>{confirm.action}</DangerButton>
          </div>
        </div>
      )}
    </Modal>
  );
}

export default function Index({ auth, clients, cdks, abilities, rateLimitPerMinute }) {
  const { flash } = usePage().props;
  const [formClient, setFormClient] = useState(null);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [tokenClient, setTokenClient] = useState(null);
  const [newToken, setNewToken] = useState(null);
  const [confirm, setConfirm] = useState(null);

  useEffect(() => {
    if (flash.new_token) setNewToken(flash.new_token);
  }, [flash.new_token]);

  const abilityLabels = Object.fromEntries(abilities.map((a) => [a.value, a.label]));

  const openCreate = () => {
    setFormClient(null);
    setIsFormOpen(true);
  };

  const openEdit = (client) => {
    setFormClient(client);
    setIsFormOpen(true);
  };

  const confirmDeleteClient = (client) =>
    setConfirm({
      title: 'Hapus Klien Integrasi?',
      message: `Klien "${client.nama}" dan ${client.tokens.length} tokennya akan dihapus. Sistem tersebut langsung kehilangan akses API.`,
      action: 'Ya, Hapus',
      url: route('integrasi-klien.destroy', client.id),
    });

  const confirmRevokeToken = (client, token) =>
    setConfirm({
      title: 'Cabut Token?',
      message: `Token "${token.name}" milik ${client.nama} tidak bisa dipakai lagi setelah dicabut.`,
      action: 'Ya, Cabut',
      url: route('integrasi-klien.tokens.destroy', [client.id, token.id]),
    });

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Klien Integrasi API</h2>}
    >
      <Head title="Klien Integrasi API" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <section className="bg-white shadow-xl sm:rounded-2xl ring-1 ring-gray-900/5 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex items-start gap-4">
              <div className="hidden sm:flex items-center justify-center h-12 w-12 rounded-xl bg-primary-50 shrink-0">
                <Plug className="h-6 w-6 text-primary-600" aria-hidden="true" />
              </div>
              <div>
                <h3 className="text-lg font-bold text-gray-900">Sistem yang terhubung ke SMART-HUT</h3>
                <p className="text-sm text-gray-500 mt-1 max-w-2xl">
                  Klien membaca data berstatus final lewat <code className="text-gray-700">/api/v1</code> memakai token.
                  Batas {rateLimitPerMinute} permintaan per menit per klien. Setiap akses tercatat di Log Aktivitas.
                </p>
                <a
                  href={route('docs.api')}
                  target="_blank"
                  rel="noopener"
                  className="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:text-primary-900"
                >
                  <BookOpen className="h-4 w-4" aria-hidden="true" />
                  Dokumentasi API untuk pengembang klien
                </a>
              </div>
            </div>
            <PrimaryButton onClick={openCreate} className="justify-center whitespace-nowrap bg-primary-600 hover:bg-primary-700 text-white">
              <svg className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              Tambah Klien
            </PrimaryButton>
          </section>

          {clients.length > 0 ? (
            clients.map((client) => (
              <ClientCard
                key={client.id}
                client={client}
                abilityLabels={abilityLabels}
                onEdit={() => openEdit(client)}
                onIssue={() => setTokenClient(client)}
                onDelete={() => confirmDeleteClient(client)}
                onRevoke={(token) => confirmRevokeToken(client, token)}
              />
            ))
          ) : (
            <section className="bg-white shadow-xl sm:rounded-2xl ring-1 ring-gray-900/5 px-6 py-14 text-center">
              <Plug className="mx-auto h-12 w-12 text-gray-300 mb-3" aria-hidden="true" />
              <p className="text-gray-700 text-lg font-medium">Belum ada klien integrasi</p>
              <p className="text-gray-500 text-sm mt-1">Tambahkan sistem seperti SIPETAK atau CDK Trenggalek, lalu terbitkan tokennya.</p>
            </section>
          )}
        </div>
      </div>

      <ClientFormModal show={isFormOpen} onClose={() => setIsFormOpen(false)} client={formClient} cdks={cdks} />
      <TokenFormModal show={Boolean(tokenClient)} onClose={() => setTokenClient(null)} client={tokenClient} abilities={abilities} />
      <NewTokenModal newToken={newToken} onClose={() => setNewToken(null)} />
      <ConfirmModal confirm={confirm} onClose={() => setConfirm(null)} />
    </AuthenticatedLayout>
  );
}
