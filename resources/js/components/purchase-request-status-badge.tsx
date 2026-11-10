import {
    Ban,
    CheckCircle2,
    Clock3,
    XCircle,
    type LucideIcon,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type PurchaseRequestStatus =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'cancelled';

const STATUS_META: Record<
    PurchaseRequestStatus,
    { label: string; icon: LucideIcon; className: string }
> = {
    pending: {
        label: 'Pending',
        icon: Clock3,
        className:
            'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-300',
    },
    approved: {
        label: 'Approved',
        icon: CheckCircle2,
        className:
            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300',
    },
    rejected: {
        label: 'Rejected',
        icon: XCircle,
        className:
            'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300',
    },
    cancelled: {
        label: 'Cancelled',
        icon: Ban,
        className: 'border-muted bg-muted text-muted-foreground',
    },
};

// Show the lifecycle state with a consistent colour and icon on both request pages.
export function PurchaseRequestStatusBadge({
    status,
}: {
    status?: string | null;
}) {
    const meta =
        STATUS_META[status as PurchaseRequestStatus] ?? STATUS_META.pending;
    const Icon = meta.icon;

    return (
        <Badge variant="outline" className={cn('gap-1', meta.className)}>
            <Icon className="size-3.5" />
            {meta.label}
        </Badge>
    );
}
