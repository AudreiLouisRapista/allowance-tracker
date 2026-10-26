import { Head, router, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    Banknote,
    CalendarClock,
    CheckCircle2,
    Clock3,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAutoDismiss } from '@/hooks/use-auto-dismiss';
import { cn } from '@/lib/utils';

type Frequency = 'weekly' | 'biweekly' | 'monthly';

type PendingCheckout = {
    id: number;
    checkout_url: string | null;
    status: string;
};

type DuePlan = {
    id: number;
    amount: string;
    frequency: Frequency;
    next_payout_date: string;
    student: { id: number; name: string } | null;
    days_overdue: number;
    pending_checkout: PendingCheckout | null;
};

type RecentPayout = {
    id: number;
    amount: string;
    cycle_date: string;
    paid_at: string;
    student: { id: number; name: string } | null;
    plan: { id: number; frequency: Frequency } | null;
};

type Balance = {
    student_id: number;
    name: string;
    balance: string;
};

type PageProps = {
    duePlans: DuePlan[];
    recentPayouts: RecentPayout[];
    balances: Balance[];
    stats: {
        due_count: number;
        due_total: string;
        paid_this_month: string;
        total_balance: string;
    };
    paymongoMode: 'mock' | 'live';
    flash: {
        payout_error?: string | null;
        payout_success?: string | null;
    };
};

const FREQUENCY_HINTS: Record<Frequency, string> = {
    weekly: 'Every week',
    biweekly: 'Every 2 weeks',
    monthly: 'Every month',
};

// Format a number as Philippine pesos with two decimal places.
function formatPeso(value: string | number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));
}

// Format an ISO date string for display in the payout cards.
function formatDate(value: string): string {
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString(
        'en-PH',
        {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        },
    );
}

// Build up to two initials for the student avatar placeholders.
function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

// Render one summary card with an icon, value, and supporting hint.
function StatCard({
    icon: Icon,
    label,
    value,
    hint,
    accent,
}: {
    icon: typeof Banknote;
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

// Show due allowances, payout actions, recent payouts, and student balances.
export default function Payouts({
    duePlans,
    recentPayouts,
    balances,
    stats,
    paymongoMode,
}: PageProps) {
    const { flash } = usePage<PageProps>().props;
    const success = useAutoDismiss(flash.payout_success);
    const error = useAutoDismiss(flash.payout_error);

    const [confirming, setConfirming] = useState<DuePlan | null>(null);
    const [busyPlanId, setBusyPlanId] = useState<number | null>(null);
    const isBusy = busyPlanId !== null;

    // Send the confirmed plan to the backend, which starts or mocks the checkout.
    const startPayout = () => {
        if (confirming === null || isBusy) {
            return;
        }

        router.post(
            '/payouts',
            { plan_id: confirming.id },
            {
                preserveScroll: true,
                onStart: () => setBusyPlanId(confirming.id),
                onFinish: () => setBusyPlanId(null),
                onSuccess: () => setConfirming(null),
                onError: (errors) =>
                    toast.error(
                        String(
                            errors.plan_id ??
                                'Could not start the payout. Please try again.',
                        ),
                    ),
            },
        );
    };

    return (
        <>
            <Head title="Payouts" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-2">
                    <h1 className="text-xl font-semibold">Payouts</h1>
                    <p className="text-sm text-muted-foreground">
                        Send due allowances and keep an eye on every
                        student&apos;s balance.
                    </p>
                </div>

                {success && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {success}
                    </div>
                )}

                {error && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                        {error}
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={Clock3}
                        label="Due now"
                        value={formatPeso(stats.due_total)}
                        hint={`${stats.due_count} ${stats.due_count === 1 ? 'allowance' : 'allowances'} waiting`}
                        accent="bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                    />
                    <StatCard
                        icon={Banknote}
                        label="Paid this month"
                        value={formatPeso(stats.paid_this_month)}
                        hint="Recorded allowances"
                        accent="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                    />
                    <StatCard
                        icon={Wallet}
                        label="Student balances"
                        value={formatPeso(stats.total_balance)}
                        hint={`Across ${balances.length} ${balances.length === 1 ? 'student' : 'students'}`}
                        accent="bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div className="grid gap-4">
                        <div>
                            <h2 className="text-base font-semibold">
                                Due allowances
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Pay a due cycle to add it to the student&apos;s
                                balance.
                            </p>
                        </div>

                        {duePlans.length === 0 ? (
                            <Card>
                                <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                                    <span className="flex size-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                        <CheckCircle2 className="size-6" />
                                    </span>
                                    <div>
                                        <p className="font-semibold">
                                            You&apos;re all caught up
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            No allowance plan is due right now.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            duePlans.map((plan) => {
                                const studentName =
                                    plan.student?.name ?? 'Student';
                                const pending = plan.pending_checkout;
                                const pendingUrl = pending?.checkout_url;

                                return (
                                    <Card key={plan.id} className="gap-0 p-6">
                                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">
                                                    {initials(studentName)}
                                                </span>
                                                <div>
                                                    <h3 className="text-base font-semibold">
                                                        {studentName}
                                                    </h3>
                                                    <p className="text-sm text-muted-foreground">
                                                        {
                                                            FREQUENCY_HINTS[
                                                                plan.frequency
                                                            ]
                                                        }{' '}
                                                        allowance
                                                    </p>
                                                </div>
                                            </div>

                                            <Badge
                                                variant="outline"
                                                className={cn(
                                                    plan.days_overdue > 0
                                                        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-300'
                                                        : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/50 dark:text-sky-300',
                                                )}
                                            >
                                                {plan.days_overdue > 0
                                                    ? `${plan.days_overdue} ${plan.days_overdue === 1 ? 'day' : 'days'} overdue`
                                                    : 'Due today'}
                                            </Badge>
                                        </div>

                                        <div className="mt-5 grid gap-3 sm:grid-cols-3">
                                            <div className="rounded-xl bg-muted/60 p-4">
                                                <p className="text-xs text-muted-foreground">
                                                    Amount
                                                </p>
                                                <p className="mt-1 text-lg font-semibold">
                                                    {formatPeso(plan.amount)}
                                                </p>
                                            </div>
                                            <div className="rounded-xl bg-muted/60 p-4">
                                                <p className="text-xs text-muted-foreground">
                                                    Cycle date
                                                </p>
                                                <p className="mt-1 text-sm font-semibold">
                                                    {formatDate(
                                                        plan.next_payout_date,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="rounded-xl bg-muted/60 p-4">
                                                <p className="text-xs text-muted-foreground">
                                                    Schedule
                                                </p>
                                                <p className="mt-1 text-sm font-semibold">
                                                    {
                                                        FREQUENCY_HINTS[
                                                            plan.frequency
                                                        ]
                                                    }
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-5 flex flex-wrap items-center gap-2">
                                            {pending && pendingUrl ? (
                                                <>
                                                    <Badge variant="secondary">
                                                        Checkout pending
                                                    </Badge>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                    >
                                                        <a href={pendingUrl}>
                                                            Resume checkout
                                                            <ArrowUpRight className="size-4" />
                                                        </a>
                                                    </Button>
                                                </>
                                            ) : (
                                                <Button
                                                    disabled={isBusy}
                                                    onClick={() =>
                                                        setConfirming(plan)
                                                    }
                                                >
                                                    <Banknote className="size-4" />
                                                    {paymongoMode === 'mock'
                                                        ? 'Send allowance'
                                                        : 'Pay with PayMongo'}
                                                </Button>
                                            )}
                                        </div>
                                    </Card>
                                );
                            })
                        )}

                        <div className="mt-2">
                            <h2 className="text-base font-semibold">
                                Recent payouts
                            </h2>
                            <Card className="mt-3 gap-0 p-0">
                                {recentPayouts.length === 0 ? (
                                    <p className="p-6 text-sm text-muted-foreground">
                                        No payouts recorded yet.
                                    </p>
                                ) : (
                                    <ul className="divide-y">
                                        {recentPayouts.map((payout) => (
                                            <li
                                                key={payout.id}
                                                className="flex items-center justify-between gap-4 px-6 py-4"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <span className="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                        <CheckCircle2 className="size-4" />
                                                    </span>
                                                    <div>
                                                        <p className="text-sm font-medium">
                                                            {payout.student
                                                                ?.name ??
                                                                'Student'}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            Cycle{' '}
                                                            {formatDate(
                                                                payout.cycle_date,
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                                <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                                    +{formatPeso(payout.amount)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </Card>
                        </div>
                    </div>

                    <div className="grid h-fit gap-4 xl:sticky xl:top-4">
                        <Card className="gap-0 p-6">
                            <div className="flex items-center gap-3">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">
                                    <Wallet className="size-5" />
                                </span>
                                <div>
                                    <CardTitle className="text-base">
                                        Student balances
                                    </CardTitle>
                                    <p className="text-xs text-muted-foreground">
                                        Money received from payouts
                                    </p>
                                </div>
                            </div>

                            <ul className="mt-5 divide-y">
                                {balances.length === 0 ? (
                                    <li className="py-4 text-sm text-muted-foreground">
                                        Add a student to see their balance.
                                    </li>
                                ) : (
                                    balances.map((balance) => (
                                        <li
                                            key={balance.student_id}
                                            className="flex items-center justify-between gap-3 py-3"
                                        >
                                            <span className="flex items-center gap-3">
                                                <span className="flex size-8 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                                    {initials(balance.name)}
                                                </span>
                                                <span className="text-sm">
                                                    {balance.name}
                                                </span>
                                            </span>
                                            <span className="text-sm font-semibold">
                                                {formatPeso(balance.balance)}
                                            </span>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </Card>

                        <Card className="gap-0 border-sky-200 bg-sky-50 p-6 text-sky-950 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-50">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-white/70 text-sky-700 dark:bg-white/10 dark:text-sky-200">
                                <CalendarClock className="size-5" />
                            </span>
                            <p className="mt-4 text-sm font-semibold">
                                PayMongo
                            </p>
                            <p className="mt-1 text-sm text-sky-900/70 dark:text-sky-100/70">
                                {paymongoMode === 'mock'
                                    ? 'PayMongo is in test mode, so payouts are recorded as test payments.'
                                    : 'You will be redirected to PayMongo to complete the allowance payment.'}
                            </p>
                            <Badge
                                variant="outline"
                                className="mt-4 border-sky-300 bg-white/60 text-sky-800 dark:border-sky-800 dark:bg-white/10 dark:text-sky-200"
                            >
                                {paymongoMode === 'mock'
                                    ? 'Test mode'
                                    : 'Live checkout'}
                            </Badge>
                        </Card>
                    </div>
                </div>

                <Dialog
                    open={confirming !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setConfirming(null);
                        }
                    }}
                >
                    <DialogContent>
                        {confirming && (
                            <>
                                <DialogHeader>
                                    <DialogTitle>Send allowance</DialogTitle>
                                    <DialogDescription>
                                        You are about to send{' '}
                                        <span className="font-semibold text-foreground">
                                            {formatPeso(confirming.amount)}
                                        </span>{' '}
                                        to{' '}
                                        {confirming.student?.name ??
                                            'this student'}{' '}
                                        for the cycle on{' '}
                                        {formatDate(
                                            confirming.next_payout_date,
                                        )}
                                        .
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="rounded-xl bg-muted/60 p-4 text-sm">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="text-muted-foreground">
                                            Student
                                        </span>
                                        <span className="font-medium">
                                            {confirming.student?.name ??
                                                'Student'}
                                        </span>
                                    </div>
                                    <div className="mt-2 flex items-center justify-between gap-3">
                                        <span className="text-muted-foreground">
                                            Amount
                                        </span>
                                        <span className="font-semibold">
                                            {formatPeso(confirming.amount)}
                                        </span>
                                    </div>
                                    <div className="mt-2 flex items-center justify-between gap-3">
                                        <span className="text-muted-foreground">
                                            Cycle
                                        </span>
                                        <span className="font-medium">
                                            {formatDate(
                                                confirming.next_payout_date,
                                            )}
                                        </span>
                                    </div>
                                </div>

                                <p className="text-sm text-muted-foreground">
                                    {paymongoMode === 'mock'
                                        ? 'PayMongo is not configured. This will be recorded immediately as a test payout.'
                                        : 'You will be redirected to PayMongo to complete the payment. The balance updates after PayMongo confirms it.'}
                                </p>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={isBusy}
                                        onClick={() => setConfirming(null)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="button"
                                        disabled={isBusy}
                                        onClick={startPayout}
                                    >
                                        {busyPlanId === confirming.id
                                            ? 'Starting...'
                                            : paymongoMode === 'mock'
                                              ? 'Record payout'
                                              : 'Continue to PayMongo'}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

Payouts.layout = {
    breadcrumbs: [{ title: 'Payouts', href: '/payouts' }],
};
