import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    Plus,
    ReceiptText,
    ShoppingBag,
    Trash2,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { PurchaseRequestStatusBadge } from '@/components/purchase-request-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { useAutoDismiss } from '@/hooks/use-auto-dismiss';
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
    status: { id: number; status: string } | null;
    items: RequestItem[];
};

type ItemDraft = {
    material_name: string;
    quantity: string;
    unit_price: string;
};

type PageProps = {
    requests: PurchaseRequest[];
    balance: string;
    stats: {
        pending_count: number;
        pending_total: string;
        request_count: number;
    };
    flash: {
        purchase_request_error?: string | null;
        purchase_request_success?: string | null;
    };
};

const MAX_ITEMS = 50;

// Format a number as Philippine pesos with two decimal places.
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

// Turn an input value into integer cents so totals never drift.
function toCents(value: string): number {
    const amount = Number(value);

    return Number.isFinite(amount) && amount > 0 ? Math.round(amount * 100) : 0;
}

function emptyDraft(): ItemDraft {
    return { material_name: '', quantity: '1', unit_price: '' };
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

// Students create requests, watch their status, and cancel only pending ones.
export default function PurchaseRequests({
    requests,
    balance,
    stats,
}: PageProps) {
    const { flash } = usePage<PageProps>().props;
    const success = useAutoDismiss(flash.purchase_request_success);
    const error = useAutoDismiss(flash.purchase_request_error);

    const form = useForm({
        store_name: '',
        items: [emptyDraft()],
    });

    const errors = form.errors as Record<string, string>;

    const [cancelling, setCancelling] = useState<PurchaseRequest | null>(null);
    const [cancelError, setCancelError] = useState<string | null>(null);
    const [busyCancelId, setBusyCancelId] = useState<number | null>(null);

    const totalCents = form.data.items.reduce((total, item) => {
        const quantity = Number.parseInt(item.quantity, 10);
        const quantityIsValid = Number.isFinite(quantity) && quantity > 0;

        return (
            total + (quantityIsValid ? quantity * toCents(item.unit_price) : 0)
        );
    }, 0);

    const updateItem = (
        index: number,
        field: keyof ItemDraft,
        value: string,
    ) => {
        form.setData(
            'items',
            form.data.items.map((item, itemIndex) =>
                itemIndex === index ? { ...item, [field]: value } : item,
            ),
        );
    };

    const addItem = () => {
        if (form.data.items.length >= MAX_ITEMS) {
            toast.error(`A request can contain at most ${MAX_ITEMS} items.`);

            return;
        }

        form.setData('items', [...form.data.items, emptyDraft()]);
    };

    const removeItem = (index: number) => {
        if (form.data.items.length === 1) {
            return;
        }

        form.setData(
            'items',
            form.data.items.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const submit = () => {
        if (form.processing) {
            return;
        }

        form.post('/student/purchase-requests', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onError: (submitErrors) => {
                const message = String(submitErrors.request ?? '');

                if (message !== '') {
                    toast.error(message);
                }
            },
        });
    };

    const openCancel = (purchaseRequest: PurchaseRequest) => {
        setCancelError(null);
        setCancelling(purchaseRequest);
    };

    const cancelRequest = () => {
        if (cancelling === null || busyCancelId !== null) {
            return;
        }

        router.patch(
            `/student/purchase-requests/${cancelling.id}/cancel`,
            {},
            {
                preserveScroll: true,
                onStart: () => {
                    setCancelError(null);
                    setBusyCancelId(cancelling.id);
                },
                onFinish: () => setBusyCancelId(null),
                onSuccess: () => setCancelling(null),
                onError: (cancelErrors) =>
                    setCancelError(
                        String(
                            cancelErrors.request ??
                                'Could not cancel this purchase request. Please try again.',
                        ),
                    ),
            },
        );
    };

    return (
        <>
            <Head title="Purchase requests" />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Purchase requests</h1>
                    <p className="text-sm text-muted-foreground">
                        Ask your parents to fund school supplies and keep every
                        request in one place.
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
                        icon={Wallet}
                        label="Available balance"
                        value={formatPeso(balance)}
                        hint="Allowance ready to spend"
                        accent="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                    />
                    <StatCard
                        icon={Clock3}
                        label="Pending requests"
                        value={String(stats.pending_count)}
                        hint="Waiting for a parent"
                        accent="bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                    />
                    <StatCard
                        icon={ReceiptText}
                        label="Pending value"
                        value={formatPeso(stats.pending_total)}
                        hint={`${stats.request_count} total ${stats.request_count === 1 ? 'request' : 'requests'}`}
                        accent="bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div className="grid gap-6">
                        <Card className="gap-0 p-5 sm:p-6">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h2 className="text-base font-semibold">
                                        New request
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Add the store and every item you need.
                                    </p>
                                </div>
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">
                                    <ShoppingBag className="size-5" />
                                </span>
                            </div>

                            <form
                                className="mt-5 space-y-5"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    submit();
                                }}
                            >
                                {errors.request && (
                                    <div
                                        role="alert"
                                        className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300"
                                    >
                                        {errors.request}
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="store_name">Store</Label>
                                    <Input
                                        id="store_name"
                                        value={form.data.store_name}
                                        onChange={(event) =>
                                            form.setData(
                                                'store_name',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="e.g. National Book Store"
                                        maxLength={150}
                                        autoComplete="off"
                                        aria-invalid={Boolean(
                                            errors.store_name,
                                        )}
                                        disabled={form.processing}
                                    />
                                    <InputError message={errors.store_name} />
                                </div>

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between gap-3">
                                        <Label>Items</Label>
                                        <span className="text-xs text-muted-foreground">
                                            {form.data.items.length} /{' '}
                                            {MAX_ITEMS}
                                        </span>
                                    </div>

                                    {form.data.items.map((item, index) => {
                                        const lineTotalCents =
                                            (Number.parseInt(
                                                item.quantity,
                                                10,
                                            ) || 0) * toCents(item.unit_price);

                                        return (
                                            <div
                                                key={index}
                                                className="rounded-xl border bg-muted/20 p-4"
                                            >
                                                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_86px_124px_36px] sm:items-start">
                                                    <div className="grid gap-2">
                                                        <Label
                                                            htmlFor={`item-name-${index}`}
                                                            className="text-xs text-muted-foreground"
                                                        >
                                                            Item
                                                        </Label>
                                                        <Input
                                                            id={`item-name-${index}`}
                                                            value={
                                                                item.material_name
                                                            }
                                                            onChange={(event) =>
                                                                updateItem(
                                                                    index,
                                                                    'material_name',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            placeholder="e.g. Notebook"
                                                            maxLength={150}
                                                            autoComplete="off"
                                                            aria-invalid={Boolean(
                                                                errors[
                                                                    `items.${index}.material_name`
                                                                ],
                                                            )}
                                                            disabled={
                                                                form.processing
                                                            }
                                                        />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label
                                                            htmlFor={`item-quantity-${index}`}
                                                            className="text-xs text-muted-foreground"
                                                        >
                                                            Qty
                                                        </Label>
                                                        <Input
                                                            id={`item-quantity-${index}`}
                                                            type="number"
                                                            inputMode="numeric"
                                                            min="1"
                                                            max="9999"
                                                            step="1"
                                                            value={
                                                                item.quantity
                                                            }
                                                            onChange={(event) =>
                                                                updateItem(
                                                                    index,
                                                                    'quantity',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            aria-invalid={Boolean(
                                                                errors[
                                                                    `items.${index}.quantity`
                                                                ],
                                                            )}
                                                            disabled={
                                                                form.processing
                                                            }
                                                        />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label
                                                            htmlFor={`item-price-${index}`}
                                                            className="text-xs text-muted-foreground"
                                                        >
                                                            Unit price
                                                        </Label>
                                                        <div className="relative">
                                                            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                                                                ₱
                                                            </span>
                                                            <Input
                                                                id={`item-price-${index}`}
                                                                type="number"
                                                                inputMode="decimal"
                                                                min="0.01"
                                                                max="99999999.99"
                                                                step="0.01"
                                                                value={
                                                                    item.unit_price
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateItem(
                                                                        index,
                                                                        'unit_price',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                                placeholder="0.00"
                                                                className="pl-7"
                                                                aria-invalid={Boolean(
                                                                    errors[
                                                                        `items.${index}.unit_price`
                                                                    ],
                                                                )}
                                                                disabled={
                                                                    form.processing
                                                                }
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="flex items-end sm:pt-5">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                removeItem(
                                                                    index,
                                                                )
                                                            }
                                                            disabled={
                                                                form.processing ||
                                                                form.data.items
                                                                    .length ===
                                                                    1
                                                            }
                                                            aria-label={`Remove item ${index + 1}`}
                                                        >
                                                            <Trash2 className="size-4 text-muted-foreground" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                <div className="mt-3 flex items-center justify-between gap-3 border-t pt-3 text-xs">
                                                    <span className="text-muted-foreground">
                                                        Line total
                                                    </span>
                                                    <span className="font-semibold">
                                                        {formatPeso(
                                                            lineTotalCents /
                                                                100,
                                                        )}
                                                    </span>
                                                </div>

                                                <InputError
                                                    className="mt-2"
                                                    message={
                                                        errors[
                                                            `items.${index}.material_name`
                                                        ]
                                                    }
                                                />
                                                <InputError
                                                    className="mt-2"
                                                    message={
                                                        errors[
                                                            `items.${index}.quantity`
                                                        ]
                                                    }
                                                />
                                                <InputError
                                                    className="mt-2"
                                                    message={
                                                        errors[
                                                            `items.${index}.unit_price`
                                                        ]
                                                    }
                                                />
                                            </div>
                                        );
                                    })}

                                    <InputError message={errors.items} />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addItem}
                                        disabled={
                                            form.processing ||
                                            form.data.items.length >= MAX_ITEMS
                                        }
                                    >
                                        <Plus className="size-4" />
                                        Add item
                                    </Button>
                                </div>

                                <div className="flex flex-col gap-3 rounded-xl bg-orange-50 p-4 text-orange-950 sm:flex-row sm:items-center sm:justify-between dark:bg-orange-950/30 dark:text-orange-50">
                                    <div>
                                        <p className="text-xs text-orange-700/75 dark:text-orange-200/75">
                                            Request total
                                        </p>
                                        <p className="text-2xl font-bold tracking-tight">
                                            {formatPeso(totalCents / 100)}
                                        </p>
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                        className="w-full sm:w-auto"
                                    >
                                        {form.processing
                                            ? 'Sending request...'
                                            : 'Send request'}
                                    </Button>
                                </div>
                            </form>
                        </Card>

                        <div className="grid gap-4">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Your requests
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Follow each request from pending to
                                    approved.
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
                                                No requests yet
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Your first purchase request will
                                                show up here.
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            ) : (
                                requests.map((purchaseRequest) => (
                                    <Card
                                        key={purchaseRequest.id}
                                        className="gap-0 p-5 sm:p-6"
                                    >
                                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
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
                                                                .status?.status
                                                        }
                                                    />
                                                </div>
                                                <p className="mt-1 inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                                                    <CalendarDays className="size-3.5" />
                                                    {formatDate(
                                                        purchaseRequest.requested_at,
                                                    )}
                                                </p>
                                            </div>

                                            <div className="flex items-center gap-3 sm:flex-col sm:items-end">
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

                                                {purchaseRequest.status
                                                    ?.status === 'pending' && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openCancel(
                                                                purchaseRequest,
                                                            )
                                                        }
                                                    >
                                                        Cancel
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        <div className="mt-5 rounded-xl border bg-muted/30 p-4">
                                            <ul className="divide-y">
                                                {purchaseRequest.items.map(
                                                    (item) => (
                                                        <li
                                                            key={item.id}
                                                            className="flex items-start justify-between gap-4 py-2.5 first:pt-0 last:pb-0"
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
                                        </div>
                                    </Card>
                                ))
                            )}
                        </div>
                    </div>

                    <div className="grid h-fit gap-4 xl:sticky xl:top-4">
                        <Card className="gap-0 border-emerald-200 bg-emerald-100 p-6 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-50">
                            <span className="flex size-12 items-center justify-center rounded-xl bg-white/70 text-emerald-700 dark:bg-white/10 dark:text-emerald-200">
                                <Wallet className="size-6" />
                            </span>
                            <p className="mt-6 text-sm font-medium text-emerald-900/70 dark:text-emerald-100/70">
                                Allowance balance
                            </p>
                            <p className="mt-1 text-3xl font-bold tracking-tight">
                                {formatPeso(balance)}
                            </p>
                            <p className="mt-1 text-sm text-emerald-900/70 dark:text-emerald-100/70">
                                Add this request total and compare it with your
                                balance before you send.
                            </p>
                            <div className="mt-6 flex items-center justify-between border-t border-emerald-200 pt-4 text-sm dark:border-emerald-900">
                                <span>New request</span>
                                <span className="font-semibold">
                                    {formatPeso(totalCents / 100)}
                                </span>
                            </div>
                        </Card>

                        <Card className="gap-0 p-6">
                            <p className="text-sm font-semibold">
                                How requests work
                            </p>
                            <ol className="mt-4 space-y-4 text-sm text-muted-foreground">
                                <li className="flex gap-3">
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-semibold text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">
                                        1
                                    </span>
                                    Add the store and each item with its
                                    quantity and price.
                                </li>
                                <li className="flex gap-3">
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                                        2
                                    </span>
                                    Send it and wait for a parent to review it.
                                </li>
                                <li className="flex gap-3">
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                        <CheckCircle2 className="size-3.5" />
                                    </span>
                                    You can cancel only while it is still
                                    pending.
                                </li>
                            </ol>
                        </Card>
                    </div>
                </div>
            </div>

            <Dialog
                open={cancelling !== null}
                onOpenChange={(open) => {
                    if (!open && busyCancelId === null) {
                        setCancelling(null);
                    }
                }}
            >
                <DialogContent>
                    {cancelling && (
                        <>
                            <DialogHeader>
                                <DialogTitle>Cancel this request?</DialogTitle>
                                <DialogDescription>
                                    This will withdraw the{' '}
                                    {formatPeso(cancelling.total_amount)}{' '}
                                    request from {cancelling.store_name}. This
                                    cannot be undone.
                                </DialogDescription>
                            </DialogHeader>

                            {cancelError && (
                                <p
                                    role="alert"
                                    className="text-sm text-destructive"
                                >
                                    {cancelError}
                                </p>
                            )}

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={busyCancelId !== null}
                                    onClick={() => setCancelling(null)}
                                >
                                    Keep request
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    disabled={busyCancelId !== null}
                                    onClick={cancelRequest}
                                >
                                    {busyCancelId === cancelling.id
                                        ? 'Cancelling...'
                                        : 'Cancel request'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

PurchaseRequests.layout = {
    breadcrumbs: [
        {
            title: 'Purchase requests',
            href: '/student/purchase-requests',
        },
    ],
};
