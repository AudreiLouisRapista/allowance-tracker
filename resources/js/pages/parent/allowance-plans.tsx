import { Form, Head, router } from '@inertiajs/react';
import { CalendarClock, Check, Pause, Pencil, Play, Plus } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type Frequency = 'weekly' | 'biweekly' | 'monthly';

type Payout = {
    plan_id: number;
    amount: string;
    cycle_date: string;
    paid_at: string;
};

type AllowancePlan = {
    id: number;
    student_id: number;
    amount: string;
    frequency: Frequency;
    start_date: string;
    next_payout_date: string;
    student: { id: number; name: string } | null;
    status: { id: number; status: 'active' | 'inactive' } | null;
    recent_payouts: Payout[];
};

type StudentOption = {
    id: number;
    name: string;
};

type PageProps = {
    plans: AllowancePlan[];
    students: StudentOption[];
    activeStudentIds: number[];
    nextPayout: AllowancePlan | null;
};

const FREQUENCY_HINTS: Record<Frequency, string> = {
    weekly: 'Every week',
    biweekly: 'Every 2 weeks',
    monthly: 'Every month',
};

function localToday(): string {
    const now = new Date();
    // Shift by the timezone offset so the date matches the user's calendar day.
    const local = new Date(now.getTime() - now.getTimezoneOffset() * 60_000);

    return local.toISOString().slice(0, 10);
}

function formatPeso(value: string | number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(value));
}

function formatDate(value: string): string {
    // Parse as local midnight so the calendar day does not shift by timezone.
    return new Date(`${value}T00:00:00`).toLocaleDateString('en-PH', {
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

function payoutDay(plan: AllowancePlan): string {
    const date = new Date(`${plan.next_payout_date}T00:00:00`);

    if (plan.frequency !== 'monthly') {
        return date.toLocaleDateString('en-PH', {
            weekday: 'long',
        });
    }

    const day = date.getDate();
    const suffix =
        day % 10 === 1 && day !== 11
            ? 'st'
            : day % 10 === 2 && day !== 12
              ? 'nd'
              : day % 10 === 3 && day !== 13
                ? 'rd'
                : 'th';

    return `${day}${suffix}`;
}

function Stat({ value, hint }: { value: string; hint: string }) {
    return (
        <div className="rounded-xl bg-muted/60 p-4">
            <p className="truncate text-sm font-semibold">{value}</p>
            <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
        </div>
    );
}

function PlanStatGrid({ plan }: { plan: AllowancePlan }) {
    return (
        <div className="grid gap-3 sm:grid-cols-3">
            <Stat value={formatPeso(plan.amount)} hint={FREQUENCY_HINTS[plan.frequency]} />
            <Stat value={payoutDay(plan)} hint="Payout day" />
            <Stat value={formatDate(plan.next_payout_date)} hint="Next payout" />
        </div>
    );
}

export default function AllowancePlans({
    plans,
    students,
    activeStudentIds,
    nextPayout,
}: PageProps) {
    // The dialog is open while it holds a plan or the create flag is on.
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState<AllowancePlan | null>(null);

    // The plan whose status request is running, or null when none is.
    const [busyId, setBusyId] = useState<number | null>(null);
    const isBusy = busyId !== null;

    const today = localToday();

    // A student with an active plan cannot start a second one.
    const eligibleStudents = students.filter(
        (student) => !activeStudentIds.includes(student.id),
    );

    const activeCount = plans.filter(
        (plan) => plan.status?.status === 'active',
    ).length;

    const toggleStatus = (plan: AllowancePlan) => {
        if (isBusy) {
            return;
        }

        const nextStatus =
            plan.status?.status === 'active' ? 'inactive' : 'active';

        router.patch(
            `/allowance-plans/${plan.id}/status`,
            { status: nextStatus },
            {
                preserveScroll: true,
                onStart: () => setBusyId(plan.id),
                onFinish: () => setBusyId(null),
                onSuccess: () =>
                    toast.success(
                        nextStatus === 'active'
                            ? 'Allowance plan activated.'
                            : 'Allowance plan deactivated.',
                    ),
                onError: (errors) =>
                    toast.error(
                        String(
                            errors.status ??
                                'Could not update the plan status.',
                        ),
                    ),
            },
        );
    };

    return (
        <>
            <Head title="Allowance plans" />
            <div className="flex flex-col gap-6 p-4">
                {/* Header: title on the left, action on the right. */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Allowance plans</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage recurring payouts for each student.
                        </p>
                    </div>
                    <Button
                        className="w-fit transition-all hover:-translate-y-0.5 hover:shadow-md"
                        onClick={() => setCreating(true)}
                    >
                        <Plus className="size-4" />
                        Add plan
                    </Button>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div className="grid gap-4">
                        {plans.length === 0 ? (
                            <Card>
                                <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                                    <span className="flex size-12 items-center justify-center rounded-xl bg-muted">
                                        <CalendarClock className="size-6 text-muted-foreground" />
                                    </span>
                                    <div>
                                        <p className="font-semibold">No allowance plans yet</p>
                                        <p className="text-sm text-muted-foreground">
                                            Create a plan to schedule a student&apos;s recurring allowance.
                                        </p>
                                    </div>
                                    <Button onClick={() => setCreating(true)}>
                                        <Plus className="size-4" />
                                        Add plan
                                    </Button>
                                </CardContent>
                            </Card>
                        ) : (
                            plans.map((plan) => {
                                const isActive = plan.status?.status === 'active';
                                const studentName = plan.student?.name ?? 'Student';

                                return (
                                    <Card key={plan.id} className="gap-0 p-6">
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">
                                                    {initials(studentName)}
                                                </span>
                                                <div>
                                                    <h2 className="text-base font-semibold">
                                                        {studentName}&apos;s {plan.frequency} allowance
                                                    </h2>
                                                    <p className="text-sm text-muted-foreground">
                                                        {isActive
                                                            ? `Active since ${formatDate(plan.start_date)}`
                                                            : 'Inactive plan'}
                                                    </p>
                                                </div>
                                            </div>
                                            <Badge
                                                variant="outline"
                                                className={cn(
                                                    isActive &&
                                                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-400',
                                                    !isActive && 'text-muted-foreground',
                                                )}
                                            >
                                                {isActive ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </div>

                                        <div className="mt-6">
                                            <PlanStatGrid plan={plan} />
                                        </div>

                                        <div className="mt-6">
                                            <h3 className="text-sm font-semibold">Recent payouts</h3>
                                            {plan.recent_payouts.length === 0 ? (
                                                <p className="mt-2 text-sm text-muted-foreground">
                                                    No payouts recorded yet.
                                                </p>
                                            ) : (
                                                <ul className="mt-2 divide-y">
                                                    {plan.recent_payouts.map((payout) => (
                                                        <li
                                                            key={`${payout.cycle_date}-${payout.paid_at}`}
                                                            className="flex items-center justify-between py-3"
                                                        >
                                                            <span className="flex items-center gap-3">
                                                                <span className="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                                    <Check className="size-4" />
                                                                </span>
                                                                <span className="text-sm">
                                                                    {formatDate(payout.cycle_date)}
                                                                </span>
                                                            </span>
                                                            <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                                                +{formatPeso(payout.amount)}
                                                            </span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            )}
                                        </div>

                                        <div className="mt-6 flex flex-wrap gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={isBusy}
                                                onClick={() => setEditing(plan)}
                                            >
                                                <Pencil className="size-4" />
                                                Edit
                                            </Button>
                                            <Button
                                                variant={isActive ? 'destructive' : 'default'}
                                                size="sm"
                                                disabled={isBusy}
                                                onClick={() => toggleStatus(plan)}
                                            >
                                                {busyId === plan.id ? (
                                                    'Saving...'
                                                ) : isActive ? (
                                                    <>
                                                        <Pause className="size-4" />
                                                        Deactivate
                                                    </>
                                                ) : (
                                                    <>
                                                        <Play className="size-4" />
                                                        Activate
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    </Card>
                                );
                            })
                        )}
                    </div>

                    {/* Summary: the soonest payout across the active plans. */}
                    <Card className="h-fit gap-0 border-sky-200 bg-sky-100 p-6 text-sky-950 xl:sticky xl:top-4 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-50">
                        <span className="flex size-12 items-center justify-center rounded-xl bg-white/70 text-sky-700 dark:bg-white/10 dark:text-sky-200">
                            <CalendarClock className="size-6" />
                        </span>

                        {nextPayout ? (
                            <>
                                <p className="mt-6 text-sm font-medium text-sky-900/70 dark:text-sky-100/70">
                                    Next scheduled payout
                                </p>
                                <p className="mt-1 text-3xl font-bold tracking-tight">
                                    {formatPeso(nextPayout.amount)}
                                </p>
                                <p className="mt-1 text-sm text-sky-900/70 dark:text-sky-100/70">
                                    {nextPayout.student?.name ?? 'Student'} ·{' '}
                                    {formatDate(nextPayout.next_payout_date)}
                                </p>
                                <p className="mt-6 text-xs font-medium text-sky-900/60 dark:text-sky-100/60">
                                    {activeCount} active {activeCount === 1 ? 'plan' : 'plans'}
                                </p>
                            </>
                        ) : (
                            <>
                                <p className="mt-6 text-sm font-medium text-sky-900/70 dark:text-sky-100/70">
                                    No active plans
                                </p>
                                <p className="mt-1 text-sm text-sky-900/70 dark:text-sky-100/70">
                                    Create or activate a plan to see the next payout here.
                                </p>
                            </>
                        )}
                    </Card>
                </div>

                {/* CREATE */}
                <Dialog open={creating} onOpenChange={setCreating}>
                    <DialogContent>
                        <Form
                            action="/allowance-plans"
                            method="post"
                            resetOnSuccess
                            onSuccess={() => {
                                setCreating(false);
                                toast.success('Allowance plan created.');
                            }}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>New allowance plan</DialogTitle>
                                        <DialogDescription>
                                            Set a recurring allowance for one student.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div className="grid gap-2">
                                        <Label htmlFor="create-student">Student</Label>
                                        <Select name="student_id" required>
                                            <SelectTrigger id="create-student">
                                                <SelectValue placeholder="Choose a student" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {eligibleStudents.map((student) => (
                                                    <SelectItem key={student.id} value={String(student.id)}>
                                                        {student.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {eligibleStudents.length === 0 && (
                                            <p className="text-xs text-muted-foreground">
                                                Every eligible student already has an active plan.
                                            </p>
                                        )}
                                        <InputError message={errors.student_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="create-amount">Allowance amount</Label>
                                        <div className="relative">
                                            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                                                ₱
                                            </span>
                                            <Input
                                                id="create-amount"
                                                name="amount"
                                                type="number"
                                                inputMode="decimal"
                                                step="0.01"
                                                min="0.01"
                                                max="99999999.99"
                                                placeholder="750.00"
                                                className="pl-7"
                                                required
                                            />
                                        </div>
                                        <InputError message={errors.amount} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="create-frequency">Frequency</Label>
                                        <Select name="frequency" defaultValue="weekly">
                                            <SelectTrigger id="create-frequency">
                                                <SelectValue placeholder="Choose a frequency" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="weekly">Weekly</SelectItem>
                                                <SelectItem value="biweekly">Biweekly</SelectItem>
                                                <SelectItem value="monthly">Monthly</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.frequency} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="create-start-date">Start date</Label>
                                        <Input
                                            id="create-start-date"
                                            name="start_date"
                                            type="date"
                                            defaultValue={today}
                                            min={today}
                                            required
                                        />
                                        <InputError message={errors.start_date} />
                                    </div>

                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={processing}
                                            onClick={() => setCreating(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing || eligibleStudents.length === 0}
                                        >
                                            {processing ? 'Saving...' : 'Create plan'}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>

                {/* EDIT */}
                <Dialog
                    open={editing !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setEditing(null);
                        }
                    }}
                >
                    <DialogContent>
                        {editing && (
                            <Form
                                key={editing.id}
                                action={`/allowance-plans/${editing.id}`}
                                method="patch"
                                onSuccess={() => {
                                    setEditing(null);
                                    toast.success('Allowance plan updated.');
                                }}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <DialogHeader>
                                            <DialogTitle>Edit allowance plan</DialogTitle>
                                            <DialogDescription>
                                                Changes apply starting with the next payout.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-amount">Allowance amount</Label>
                                            <div className="relative">
                                                <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                                                    ₱
                                                </span>
                                                <Input
                                                    id="edit-amount"
                                                    name="amount"
                                                    type="number"
                                                    inputMode="decimal"
                                                    step="0.01"
                                                    min="0.01"
                                                    max="99999999.99"
                                                    defaultValue={editing.amount}
                                                    className="pl-7"
                                                    required
                                                />
                                            </div>
                                            <InputError message={errors.amount} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-frequency">Frequency</Label>
                                            <Select name="frequency" defaultValue={editing.frequency}>
                                                <SelectTrigger id="edit-frequency">
                                                    <SelectValue placeholder="Choose a frequency" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="weekly">Weekly</SelectItem>
                                                    <SelectItem value="biweekly">Biweekly</SelectItem>
                                                    <SelectItem value="monthly">Monthly</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.frequency} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-start-date">Start date</Label>
                                            <Input
                                                id="edit-start-date"
                                                name="start_date"
                                                type="date"
                                                defaultValue={editing.start_date}
                                                required
                                            />
                                            <InputError message={errors.start_date} />
                                        </div>

                                        <DialogFooter>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={processing}
                                                onClick={() => setEditing(null)}
                                            >
                                                Cancel
                                            </Button>
                                            <Button type="submit" disabled={processing}>
                                                {processing ? 'Saving...' : 'Save changes'}
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

// Breadcrumb shown above the page.
AllowancePlans.layout = {
    breadcrumbs: [{ title: 'Allowance plans', href: '/allowance-plans' }],
};
