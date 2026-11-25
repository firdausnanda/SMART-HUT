import { router } from '@inertiajs/react';
import { useState } from 'react';
import Swal from 'sweetalert2';

export default function useDeletePenanaman() {
    const [processing, setProcessing] = useState(false);

    const remove = async (report) => {
        if (processing || report.status !== 'draft') return;

        const result = await Swal.fire({
            title: 'Hapus draft penanaman?',
            text: `Laporan ${report.location_name || 'tanpa nama lokasi'} akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus draft',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#b91c1c',
            cancelButtonColor: '#6b7280',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl font-bold',
                cancelButton: 'rounded-xl font-bold',
            },
        });
        if (!result.isConfirmed) return;

        setProcessing(true);
        router.delete(route('penanaman.destroy', report.id), {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    return { remove, processing };
}
