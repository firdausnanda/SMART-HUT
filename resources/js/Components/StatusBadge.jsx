import React from 'react';
import { 
    FileText, 
    UserCheck, 
    CheckCircle2, 
    XCircle, 
    Clock 
} from 'lucide-react';

export default function StatusBadge({ status }) {
    const statusConfig = {
        draft: { 
            color: 'bg-slate-100 text-slate-700 ring-slate-200/60', 
            label: 'Draft',
            icon: FileText,
            dot: 'bg-slate-400'
        },
        waiting_kasi: { 
            color: 'bg-amber-50 text-amber-700 ring-amber-200/60', 
            label: 'Menunggu Kasi',
            icon: Clock,
            dot: 'bg-amber-500 animate-pulse'
        },
        waiting_cdk: { 
            color: 'bg-indigo-50 text-indigo-700 ring-indigo-200/60', 
            label: 'Menunggu KaCDK',
            icon: UserCheck,
            dot: 'bg-indigo-500 animate-pulse'
        },
        final: { 
            color: 'bg-emerald-50 text-emerald-700 ring-emerald-200/60', 
            label: 'Selesai',
            icon: CheckCircle2,
            dot: 'bg-emerald-500'
        },
        rejected: { 
            color: 'bg-rose-50 text-rose-700 ring-rose-200/60', 
            label: 'Ditolak',
            icon: XCircle,
            dot: 'bg-rose-500'
        },
    };

    const config = statusConfig[status] || statusConfig.draft;
    const Icon = config.icon;

    return (
        <span
            className={`inline-flex items-center gap-2 px-3 py-1 rounded-full ring-1 ring-inset transition-all duration-300 ${config.color}`}
            style={{ minWidth: '125px', justifyContent: 'center' }}
        >
            <div className={`w-1.5 h-1.5 rounded-full shrink-0 ${config.dot}`} />
            <Icon className="w-3.5 h-3.5 opacity-70" />
            <span className="text-[10px] font-bold uppercase tracking-widest whitespace-nowrap">
                {config.label}
            </span>
        </span>
    );
}
