import { router } from '@inertiajs/react';
import { useState } from 'react';
import Swal from 'sweetalert2';

export default function useSubmitPenanaman() {
    const [processing, setProcessing] = useState(false);

    const submit = async (id) => {
        if (processing) return;

        const result = await Swal.fire({
            title: 'Ajukan laporan?',
            text: 'Laporan yang lengkap akan langsung berstatus final.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, ajukan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#1f6e46',
            cancelButtonColor: '#6b7280',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl font-bold',
                cancelButton: 'rounded-xl font-bold',
            },
        });
        if (!result.isConfirmed) return;

        setProcessing(true);
        router.post(route('penanaman.submit', id), {}, {
            preserveScroll: true,
            onError: (errors) => {
                Swal.fire({
                    title: 'Laporan belum dapat diajukan',
                    text: Object.values(errors).join(' ') || 'Periksa kembali data laporan.',
                    icon: 'error',
                    confirmButtonColor: '#1f6e46',
                });
            },
            onFinish: () => setProcessing(false),
        });
    };

    return { submit, processing };
}
