import { Head } from '@inertiajs/react';
import {
    CalendarDays,
    Clock3,
    ReceiptText,
    ShoppingBag,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { PurchaseRequestStatusBadge } from '@/components/purchase-request-status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type RequestItem = {
    id: number;
    material_name: string;
    quantity: number;
    unit_price: string;
    line_total: string;
};

type PurchaseRequest = {
    id: number;
    store_name: string;
    total_amount: string;
    requested_at: string;
    student: { id: number; name: string } | null;
    status: { id: number; status: string } | null;
    items: RequestItem[];
};

type PageProps = {
    requests: PurchaseRequest[];
    stats: {
        pending_count: number;
        pending_total: string;
        request_count: number;
        student_count: number;
    };
};

function formatPeso(value: string | number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-PH', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function StatCard({
    icon: Icon,
    label,
    value,
    hint,
    accent,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
    hint: string;
    accent: string;
}) {
    return (
        <Card className="gap-0 p-5">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="mt-2 text-2xl font-bold tracking-tight">
                        {value}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
                </div>
                <span
                    className={cn(
                        'flex size-10 items-center justify-center rounded-xl',
                        accent,
                    )}
                >
                    <Icon className="size-5" />
                </span>
            </div>
        </Card>
    );
}

// Show every purchase request the parent is allowed to review.
export default function PurchaseRequests({ requests, stats }: PageProps) {
    return (
        <>
            <Head title="Purchase requests" />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Purchase requests</h1>
                    <p className="text-sm text-muted-foreground">
                        See what your students are asking to buy and what each
                        request will cost.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Clock3}
                        label="Waiting for review"
                        value={String(stats.pending_count)}
                        hint={
                            stats.pending_count === 1
                                ? 'Pending request'
                                : 'Pending requests'
                        }
                        accent="bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                    />
                    <StatCard
                        icon={Wallet}
                        label="Pending value"
                        value={formatPeso(stats.pending_total)}
                        hint="Total across pending requests"
                        accent="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                    />
                    <StatCard
                        icon={Users}
                        label="Students"
                        value={String(stats.student_count)}
                        hint="With at least one request"
                        accent="bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"
                    />
                    <StatCard
                        icon={ReceiptText}
                        label="All requests"
                        value={String(stats.request_count)}
                        hint="Newest first"
                        accent="bg-orange-100 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300"
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <div className="grid gap-4">
                        <div>
                            <h2 className="text-base font-semibold">
                                Request list
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Each request keeps its store, items, and total
                                together.
                            </p>
                        </div>

                        {requests.length === 0 ? (
                            <Card>
                                <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                                    <span className="flex size-12 items-center justify-center rounded-xl bg-muted">
                                        <ShoppingBag className="size-6 text-muted-foreground" />
                                    </span>
                                    <div>
                                        <p className="font-semibold">
                                            No purchase requests yet
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            When a student sends a request, it
                                            will appear here.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            requests.map((purchaseRequest) => {
                                const studentName =
                                    purchaseRequest.student?.name ?? 'Student';

                                return (
                                    <Card
                                        key={purchaseRequest.id}
                                        className="gap-0 p-5 sm:p-6"
                                    >
                                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="flex items-start gap-3">
                                                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">
                                                    {initials(studentName)}
                                                </span>
                                                <div>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <h3 className="text-base font-semibold">
                                                            {
                                                                purchaseRequest.store_name
                                                            }
                                                        </h3>
                                                        <PurchaseRequestStatusBadge
                                                            status={
                                                                purchaseRequest
                                                                    .status
                                                                    ?.status
                                                            }
                                                        />
                                                    </div>
                                                    <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                                                        <span>
                                                            {studentName}
                                                        </span>
                                                        <span aria-hidden="true">
                                                            •
                                                        </span>
                                                        <span className="inline-flex items-center gap-1">
                                                            <CalendarDays className="size-3.5" />
                                                            {formatDate(
                                                                purchaseRequest.requested_at,
                                                            )}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="sm:text-right">
                                                <p className="text-xs text-muted-foreground">
                                                    Request total
                                                </p>
                                                <p className="text-lg font-semibold">
                                                    {formatPeso(
                                                        purchaseRequest.total_amount,
                                                    )}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-5 rounded-xl border bg-muted/30 p-4">
                                            <div className="flex items-center justify-between text-[10px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                                <span>Items</span>
                                                <span>
                                                    {
                                                        purchaseRequest.items
                                                            .length
                                                    }
                                                </span>
                                            </div>

                                            {purchaseRequest.items.length ===
                                            0 ? (
                                                <p className="mt-3 text-sm text-muted-foreground">
                                                    No items were recorded.
                                                </p>
                                            ) : (
                                                <ul className="mt-2 divide-y">
                                                    {purchaseRequest.items.map(
                                                        (item) => (
                                                            <li
                                                                key={item.id}
                                                                className="flex items-start justify-between gap-4 py-2.5"
                                                            >
                                                                <div>
                                                                    <p className="text-sm font-medium">
                                                                        {
                                                                            item.material_name
                                                                        }
                                                                    </p>
                                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                                        {
                                                                            item.quantity
                                                                        }{' '}
                                                                        ×{' '}
                                                                        {formatPeso(
                                                                            item.unit_price,
                                                                        )}
                                                                    </p>
                                                                </div>
                                                                <p className="text-sm font-semibold">
                                                                    {formatPeso(
                                                                        item.line_total,
                                                                    )}
                                                                </p>
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            )}

                                            <div className="mt-3 flex items-center justify-between border-t pt-3 text-sm font-semibold">
                                                <span>Total</span>
                                                <span>
                                                    {formatPeso(
                                                        purchaseRequest.total_amount,
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </Card>
                                );
                            })
                        )}
                    </div>

                    <Card className="h-fit gap-0 border-sky-200 bg-sky-100 p-6 text-sky-950 xl:sticky xl:top-4 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-50">
                        <span className="flex size-12 items-center justify-center rounded-xl bg-white/70 text-sky-700 dark:bg-white/10 dark:text-sky-200">
                            <ShoppingBag className="size-6" />
                        </span>
                        <p className="mt-6 text-sm font-semibold">
                            Request rules
                        </p>
                        <ul className="mt-3 space-y-3 text-sm text-sky-900/75 dark:text-sky-100/75">
                            <li>
                                Pending requests are waiting for a parent
                                decision.
                            </li>
                            <li>
                                Items and totals stay attached to the request
                                for a clear record.
                            </li>
                            <li>
                                A student can cancel a request only while it is
                                still pending.
                            </li>
                        </ul>
                    </Card>
                </div>
            </div>
        </>
    );
}

PurchaseRequests.layout = {
    breadcrumbs: [
        {
            title: 'Purchase requests',
            href: '/purchase-requests',
        },
    ],
};
