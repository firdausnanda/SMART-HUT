import React from 'react';

export default function StatusBadge({ status }) {
    const statusConfig = {
        draft: { color: 'bg-gray-100 text-gray-700', label: 'Draft' },
        waiting_kasi: { color: 'bg-yellow-100 text-yellow-700', label: 'Waiting Kasi' },
        waiting_cdk: { color: 'bg-blue-100 text-blue-700', label: 'Waiting CDK' },
        final: { color: 'bg-green-100 text-green-700', label: 'Final' },
        rejected: { color: 'bg-red-100 text-red-700', label: 'Rejected' },
    };

    const config = statusConfig[status] || statusConfig.draft;

    return (
        <span
            className={`px-2 py-1 font-semibold leading-tight rounded-full ${config.color} text-xs`}
        >
            {config.label}
        </span>
    );
}
