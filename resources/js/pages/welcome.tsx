import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    CalendarDays,
    Check,
    CreditCard,
    LockKeyhole,
    ShieldCheck,
    ShoppingBag,
    Sparkles,
    TrendingUp,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { dashboardPathForRole } from '@/lib/dashboard-path';
import { home, login } from '@/routes';

type Feature = {
    icon: LucideIcon;
    title: string;
    description: string;
    accent: string;
};

const features: Feature[] = [
    {
        icon: CalendarDays,
        title: 'Allowance plans that run themselves',
        description:
            'Set an amount and a weekly, biweekly, or monthly rhythm. Adjust or pause a plan whenever family life changes.',
        accent: 'bg-[#e8f5ff] text-[#0b1220]',
    },
    {
        icon: Banknote,
        title: 'Payouts without the guesswork',
        description:
            'Send allowance through a familiar checkout flow, with every payout recorded in one clear history.',
        accent: 'bg-[#ecfdf5] text-[#047857]',
    },
    {
        icon: Wallet,
        title: 'A balance students can see',
        description:
            'No more waiting for a text or asking twice. Students always know what is available for the week.',
        accent: 'bg-[#fff7ed] text-[#c2410c]',
    },
    {
        icon: ShoppingBag,
        title: 'Purchase requests in context',
        description:
            'Keep spending conversations in one place, with the reason, the amount, and the decision together.',
        accent: 'bg-[#fdf2f8] text-[#be185d]',
    },
    {
        icon: Users,
        title: 'A view for every family role',
        description:
            'Parents manage plans and payouts. Students get a focused view of their own allowance and balance.',
        accent: 'bg-[#eef2ff] text-[#4338ca]',
    },
    {
        icon: ShieldCheck,
        title: 'Private by design',
        description:
            'Each family gets its own secure space. Google sign-in means no shared passwords and no extra clutter.',
        accent: 'bg-[#e0f2fe] text-[#0369a1]',
    },
];

const trustItems = [
    {
        icon: LockKeyhole,
        title: 'Google sign-in',
        detail: 'No passwords to remember',
    },
    {
        icon: ShieldCheck,
        title: 'Private family space',
        detail: 'One secure space per household',
    },
    {
        icon: CreditCard,
        title: 'Secure payouts',
        detail: 'Powered by PayMongo',
    },
];

const steps = [
    {
        title: 'Create your family space',
        description:
            'Sign up with Google and bring the people who share your household into one calm place.',
    },
    {
        title: 'Set the rhythm',
        description:
            'Choose a student, an allowance amount, and a schedule. Weekly, biweekly, or monthly — your call.',
    },
    {
        title: 'Send and track',
        description:
            'Payout when it is due, then let balances and payout history update without another spreadsheet.',
    },
];

function Brand() {
    return (
        <Link
            href={home()}
            aria-label="Allowee home"
            className="flex w-fit items-center gap-3"
        >
            <span className="flex size-11 items-center justify-center rounded-2xl bg-[#0b1220] shadow-lg shadow-[#0b1220]/10">
                <Wallet
                    className="size-5 text-white"
                    strokeWidth={1.8}
                    aria-hidden="true"
                />
            </span>
            <span className="text-xl font-semibold tracking-[-0.03em]">
                Allowee
            </span>
        </Link>
    );
}

function PreviewMini({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
    tone: string;
}) {
    return (
        <div className="rounded-2xl border border-[#e2e8f0] bg-white p-4">
            <span
                className={`flex size-8 items-center justify-center rounded-xl ${tone}`}
            >
                <Icon className="size-4" strokeWidth={2} aria-hidden="true" />
            </span>
            <p className="mt-4 text-[10px] font-semibold tracking-[0.14em] text-[#94a3b8] uppercase">
                {label}
            </p>
            <p className="mt-1 text-sm font-semibold">{value}</p>
        </div>
    );
}

export default function Welcome() {
    const { auth } = usePage().props;
    const userRole = auth.user?.role;
    const dashboardHref = userRole ? dashboardPathForRole(userRole) : null;
    const year = new Date().getFullYear();

    return (
        <>
            <Head title="Growing good money habits" />

            <div className="min-h-svh bg-white text-[#0b1220] selection:bg-[#d9f1ff] selection:text-[#0b1220]">
                <header className="sticky top-0 z-50 border-b border-[#e2e8f0]/80 bg-white/85 backdrop-blur-xl">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-6 lg:px-10">
                        <Brand />

                        <nav
                            aria-label="Main"
                            className="hidden items-center gap-8 text-sm font-medium text-[#475569] lg:flex"
                        >
                            <a
                                href="#features"
                                className="transition hover:text-[#0b1220]"
                            >
                                Features
                            </a>
                            <a
                                href="#how-it-works"
                                className="transition hover:text-[#0b1220]"
                            >
                                How it works
                            </a>
                        </nav>

                        <div className="flex items-center gap-2 sm:gap-3">
                            {dashboardHref ? (
                                <Link
                                    href={dashboardHref}
                                    className="inline-flex h-11 items-center gap-2 rounded-xl bg-[#0b1220] px-4 text-sm font-semibold text-white transition hover:bg-[#0b1220]/90 sm:px-5"
                                >
                                    Dashboard
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="hidden rounded-xl px-3 py-2 text-sm font-semibold text-[#475569] transition hover:bg-[#f1f5f9] hover:text-[#0b1220] sm:inline-flex"
                                    >
                                        Sign in
                                    </Link>
                                    <a
                                        href="/auth/google/signup"
                                        className="inline-flex h-11 items-center gap-2 rounded-xl bg-[#0b1220] px-4 text-sm font-semibold text-white transition hover:bg-[#0b1220]/90 sm:px-5"
                                    >
                                        Get started
                                        <ArrowRight
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </a>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main>
                    <section className="relative isolate overflow-hidden">
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-0 -z-10"
                        >
                            <div className="absolute -top-32 right-[-10%] size-[560px] rounded-full bg-[#d9f1ff]/60 blur-3xl" />
                            <div className="absolute top-36 left-[-16%] size-[430px] rounded-full bg-[#f1f7ff] blur-3xl" />
                            <div className="absolute -right-56 -bottom-72 size-[790px] rounded-full border-[110px] border-[#eaf7ff]/70" />
                        </div>

                        <div className="mx-auto grid max-w-7xl items-center gap-14 px-6 py-16 sm:py-20 lg:grid-cols-[1.06fr_0.94fr] lg:gap-10 lg:px-10 lg:py-24 xl:py-28">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full border border-[#d9f1ff] bg-[#f4fbff] px-4 py-2 text-[10px] font-semibold tracking-[0.18em] text-[#475569] uppercase">
                                    <Sparkles
                                        className="size-3.5 text-[#0b1220]"
                                        strokeWidth={2.4}
                                        aria-hidden="true"
                                    />
                                    Built for families, not spreadsheets
                                </div>

                                <h1 className="mt-7 text-[clamp(2rem,6.4vw,5.4rem)] leading-[0.98] font-extrabold tracking-[-0.06em] [text-wrap:balance]">
                                    <span className="block">
                                        Growing good money
                                    </span>
                                    <span className="block">
                                        habits,{' '}
                                        <span className="bg-[linear-gradient(transparent_64%,#d9f1ff_64%)] px-1">
                                            together.
                                        </span>
                                    </span>
                                </h1>

                                <p className="mt-6 max-w-[34rem] text-base leading-7 text-[#475569] sm:text-lg sm:leading-8">
                                    One calm place for allowances, payouts, and
                                    honest family conversations about money.
                                    Allowee keeps parents and students on the
                                    same page without the spreadsheets.
                                </p>

                                <div className="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    {dashboardHref ? (
                                        <Link
                                            href={dashboardHref}
                                            className="inline-flex h-14 items-center justify-center gap-2 rounded-2xl bg-[#0b1220] px-7 text-base font-semibold text-white shadow-lg shadow-[#0b1220]/15 transition hover:-translate-y-0.5 hover:bg-[#0b1220]/90 hover:shadow-xl"
                                        >
                                            Open dashboard
                                            <ArrowRight
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    ) : (
                                        <>
                                            <a
                                                href="/auth/google/signup"
                                                className="inline-flex h-14 items-center justify-center gap-2 rounded-2xl bg-[#0b1220] px-7 text-base font-semibold text-white shadow-lg shadow-[#0b1220]/15 transition hover:-translate-y-0.5 hover:bg-[#0b1220]/90 hover:shadow-xl"
                                            >
                                                Start with Google
                                                <ArrowRight
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            </a>
                                            <Link
                                                href={login()}
                                                className="inline-flex h-14 items-center justify-center rounded-2xl border border-[#cbd5e1] bg-white px-7 text-base font-semibold text-[#0b1220] transition hover:-translate-y-0.5 hover:bg-[#f8fafc]"
                                            >
                                                Sign in
                                            </Link>
                                        </>
                                    )}
                                </div>

                                <div className="mt-8 flex flex-wrap gap-x-5 gap-y-2 text-[13px] text-[#64748b]">
                                    {[
                                        'Google sign-in',
                                        'One space per family',
                                        'No passwords to remember',
                                    ].map((item) => (
                                        <span
                                            key={item}
                                            className="inline-flex items-center gap-1.5"
                                        >
                                            <Check
                                                className="size-4 text-[#0b1220]"
                                                strokeWidth={3}
                                                aria-hidden="true"
                                            />
                                            {item}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <div
                                aria-hidden="true"
                                className="relative isolate mx-auto w-full max-w-[540px]"
                            >
                                <div className="absolute -inset-6 -z-10 rounded-[3rem] bg-[#d9f1ff]/70 blur-2xl" />

                                <div className="relative rounded-[2.25rem] border border-[#dbeafe] bg-white/80 p-3 shadow-[0_32px_80px_-32px_rgba(11,18,32,0.35)] backdrop-blur">
                                    <div className="overflow-hidden rounded-[1.65rem] border border-[#e2e8f0] bg-[#f8fbff]">
                                        <div className="flex items-center justify-between gap-3 border-b border-[#e2e8f0] bg-white px-4 py-4 sm:px-5">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-9 items-center justify-center rounded-xl bg-[#0b1220]">
                                                    <Wallet
                                                        className="size-4 text-white"
                                                        strokeWidth={1.8}
                                                    />
                                                </span>
                                                <div>
                                                    <p className="text-sm font-semibold">
                                                        Family overview
                                                    </p>
                                                    <p className="text-[11px] text-[#94a3b8]">
                                                        Allowee preview
                                                    </p>
                                                </div>
                                            </div>
                                            <span className="rounded-full bg-[#e8f5ff] px-3 py-1 text-[10px] font-semibold tracking-[0.12em] text-[#475569] uppercase">
                                                October
                                            </span>
                                        </div>

                                        <div className="space-y-4 p-4 sm:p-5">
                                            <div className="rounded-2xl bg-[#0b1220] p-5 text-white">
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p className="text-[10px] font-semibold tracking-[0.16em] text-white/50 uppercase">
                                                            Available balance
                                                        </p>
                                                        <p className="mt-2 text-3xl font-bold tracking-[-0.04em]">
                                                            ₱4,250.00
                                                        </p>
                                                    </div>
                                                    <span className="flex size-10 items-center justify-center rounded-xl bg-white/10 text-[#d9f1ff]">
                                                        <TrendingUp className="size-5" />
                                                    </span>
                                                </div>
                                                <div className="mt-5 flex items-center justify-between rounded-xl bg-white/10 px-3 py-3 text-xs">
                                                    <span className="text-white/60">
                                                        Next weekly allowance
                                                    </span>
                                                    <span className="font-semibold">
                                                        + ₱1,500.00
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-3">
                                                <PreviewMini
                                                    icon={CalendarDays}
                                                    label="Next payout"
                                                    value="Fri, Oct 30"
                                                    tone="bg-[#e8f5ff] text-[#0b1220]"
                                                />
                                                <PreviewMini
                                                    icon={ShoppingBag}
                                                    label="Requests"
                                                    value="2 waiting"
                                                    tone="bg-[#fff7ed] text-[#c2410c]"
                                                />
                                            </div>

                                            <div className="rounded-2xl border border-[#e2e8f0] bg-white p-4">
                                                <div className="flex items-center justify-between">
                                                    <p className="text-sm font-semibold">
                                                        Recent activity
                                                    </p>
                                                    <span className="text-[10px] font-medium tracking-[0.12em] text-[#94a3b8] uppercase">
                                                        Sample
                                                    </span>
                                                </div>

                                                <div className="mt-3 space-y-3">
                                                    <div className="flex items-center gap-3">
                                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#ecfdf5] text-[#047857]">
                                                            <Banknote className="size-4" />
                                                        </span>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="truncate text-xs font-semibold">
                                                                Weekly allowance
                                                            </p>
                                                            <p className="text-[11px] text-[#94a3b8]">
                                                                Sent to Jamie
                                                            </p>
                                                        </div>
                                                        <p className="text-xs font-semibold text-[#047857]">
                                                            + ₱1,500.00
                                                        </p>
                                                    </div>

                                                    <div className="flex items-center gap-3">
                                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#fdf2f8] text-[#be185d]">
                                                            <ShoppingBag className="size-4" />
                                                        </span>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="truncate text-xs font-semibold">
                                                                School supplies
                                                            </p>
                                                            <p className="text-[11px] text-[#94a3b8]">
                                                                Request approved
                                                            </p>
                                                        </div>
                                                        <p className="text-xs font-semibold text-[#be185d]">
                                                            - ₱450.00
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="absolute top-24 -right-3 hidden w-[190px] rounded-2xl border border-[#e2e8f0] bg-white p-4 shadow-[0_20px_50px_-20px_rgba(11,18,32,0.35)] sm:block lg:-right-8">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-10 items-center justify-center rounded-xl bg-[#dcfce7] text-[#16a34a]">
                                            <Check
                                                className="size-5"
                                                strokeWidth={3}
                                            />
                                        </span>
                                        <div>
                                            <p className="text-xs font-semibold">
                                                Payout complete
                                            </p>
                                            <p className="mt-1 text-[11px] text-[#94a3b8]">
                                                Balance updated
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        aria-label="Built on trust"
                        className="border-y border-[#e2e8f0] bg-[#fbfdff]"
                    >
                        <div className="mx-auto grid max-w-7xl gap-6 px-6 py-6 sm:grid-cols-3 lg:px-10">
                            {trustItems.map(({ icon: Icon, title, detail }) => (
                                <div
                                    key={title}
                                    className="flex items-center gap-3"
                                >
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#0b1220] ring-1 ring-[#e2e8f0]">
                                        <Icon
                                            className="size-4"
                                            strokeWidth={2}
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {title}
                                        </p>
                                        <p className="text-xs text-[#94a3b8]">
                                            {detail}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section
                        id="features"
                        className="scroll-mt-24 bg-white py-20 sm:py-28"
                    >
                        <div className="mx-auto max-w-7xl px-6 lg:px-10">
                            <div className="max-w-2xl">
                                <p className="text-[11px] font-semibold tracking-[0.2em] text-[#3b82f6] uppercase">
                                    Everything in one calm place
                                </p>
                                <h2 className="mt-4 text-3xl font-extrabold tracking-[-0.045em] [text-wrap:balance] sm:text-4xl lg:text-[3.25rem] lg:leading-[1.05]">
                                    A clearer view of family money.
                                </h2>
                                <p className="mt-5 max-w-xl text-base leading-7 text-[#64748b] sm:text-lg">
                                    Less chasing, less guessing, and fewer
                                    spreadsheets. Allowee gives every member of
                                    the family the view they need.
                                </p>
                            </div>

                            <div className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {features.map(
                                    ({
                                        icon: Icon,
                                        title,
                                        description,
                                        accent,
                                    }) => (
                                        <article
                                            key={title}
                                            className="rounded-3xl border border-[#e2e8f0] bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-[#bfe3f7] hover:shadow-[0_24px_60px_-30px_rgba(11,18,32,0.3)] sm:p-7"
                                        >
                                            <span
                                                className={`flex size-12 items-center justify-center rounded-2xl ${accent}`}
                                            >
                                                <Icon
                                                    className="size-5"
                                                    strokeWidth={1.9}
                                                    aria-hidden="true"
                                                />
                                            </span>
                                            <h3 className="mt-5 text-lg font-bold tracking-[-0.02em]">
                                                {title}
                                            </h3>
                                            <p className="mt-2.5 text-sm leading-6 text-[#64748b]">
                                                {description}
                                            </p>
                                        </article>
                                    ),
                                )}
                            </div>
                        </div>
                    </section>

                    <section
                        id="how-it-works"
                        className="scroll-mt-24 border-y border-[#e2e8f0] bg-[#f7fbff] py-20 sm:py-24"
                    >
                        <div className="mx-auto max-w-7xl px-6 lg:px-10">
                            <div className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-end">
                                <div>
                                    <p className="text-[11px] font-semibold tracking-[0.2em] text-[#3b82f6] uppercase">
                                        How it works
                                    </p>
                                    <h2 className="mt-4 text-3xl font-extrabold tracking-[-0.045em] [text-wrap:balance] sm:text-4xl lg:text-[3.25rem] lg:leading-[1.05]">
                                        From setup to first payout in three
                                        steps.
                                    </h2>
                                </div>
                                <p className="max-w-xl text-base leading-7 text-[#64748b] sm:text-lg lg:justify-self-end">
                                    No setup marathon. Create the space, set a
                                    rhythm, and let the routine take care of
                                    itself.
                                </p>
                            </div>

                            <div className="mt-12 grid gap-4 lg:grid-cols-3">
                                {steps.map((step, index) => (
                                    <article
                                        key={step.title}
                                        className="rounded-3xl border border-[#dbeafe] bg-white p-7 shadow-[0_18px_50px_-35px_rgba(11,18,32,0.4)]"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="flex size-11 items-center justify-center rounded-2xl bg-[#0b1220] text-sm font-bold text-white">
                                                {String(index + 1).padStart(
                                                    2,
                                                    '0',
                                                )}
                                            </span>
                                            <span className="text-[10px] font-semibold tracking-[0.18em] text-[#94a3b8] uppercase">
                                                Step
                                            </span>
                                        </div>
                                        <h3 className="mt-6 text-xl font-bold tracking-[-0.025em]">
                                            {step.title}
                                        </h3>
                                        <p className="mt-3 text-sm leading-6 text-[#64748b]">
                                            {step.description}
                                        </p>
                                    </article>
                                ))}
                            </div>

                            <div className="mt-10 flex items-start gap-3 rounded-2xl border border-[#dbeafe] bg-white/70 px-5 py-4 text-sm leading-6 text-[#64748b]">
                                <ShieldCheck
                                    className="mt-0.5 size-5 shrink-0 text-[#0b1220]"
                                    aria-hidden="true"
                                />
                                <p>
                                    <span className="font-semibold text-[#0b1220]">
                                        Built for trust.
                                    </span>{' '}
                                    Google sign-in, a private family space, and
                                    payment handling through PayMongo.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="bg-white px-6 py-16 sm:py-24 lg:px-10">
                        <div className="relative isolate mx-auto max-w-7xl overflow-hidden rounded-[2.5rem] bg-[#0b1220] px-6 py-14 text-white sm:px-12 sm:py-16 lg:px-16">
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute -top-24 -right-24 size-[360px] rounded-full border-[70px] border-white/5"
                            />
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute -bottom-32 left-1/3 size-[300px] rounded-full bg-[#d9f1ff]/10 blur-2xl"
                            />

                            <div className="relative z-10 grid gap-10 lg:grid-cols-[1fr_auto] lg:items-end">
                                <div className="max-w-2xl">
                                    <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-[10px] font-semibold tracking-[0.18em] text-white/70 uppercase">
                                        Start the habit
                                    </span>
                                    <h2 className="mt-5 text-3xl font-extrabold tracking-[-0.045em] [text-wrap:balance] sm:text-4xl lg:text-5xl">
                                        Make allowance day feel lighter.
                                    </h2>
                                    <p className="mt-5 text-base leading-7 text-white/65">
                                        Bring your family into one calm space
                                        for plans, payouts, and the money
                                        conversations in between.
                                    </p>
                                </div>

                                <div className="flex flex-col gap-3 sm:flex-row lg:flex-col lg:items-stretch">
                                    {dashboardHref ? (
                                        <Link
                                            href={dashboardHref}
                                            className="inline-flex h-13 items-center justify-center gap-2 rounded-2xl bg-white px-7 text-base font-semibold text-[#0b1220] transition hover:-translate-y-0.5 hover:bg-[#f8fafc]"
                                        >
                                            Open dashboard
                                            <ArrowRight
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    ) : (
                                        <>
                                            <a
                                                href="/auth/google/signup"
                                                className="inline-flex h-13 items-center justify-center gap-2 rounded-2xl bg-white px-7 text-base font-semibold text-[#0b1220] transition hover:-translate-y-0.5 hover:bg-[#f8fafc]"
                                            >
                                                Start with Google
                                                <ArrowRight
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            </a>
                                            <Link
                                                href={login()}
                                                className="inline-flex h-13 items-center justify-center rounded-2xl border border-white/20 bg-white/5 px-7 text-base font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/10"
                                            >
                                                Sign in
                                            </Link>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-[#e2e8f0] bg-white">
                    <div className="mx-auto flex max-w-7xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-center sm:justify-between lg:px-10">
                        <Brand />

                        <nav
                            aria-label="Footer"
                            className="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-[#64748b]"
                        >
                            <a
                                href="#features"
                                className="transition hover:text-[#0b1220]"
                            >
                                Features
                            </a>
                            <a
                                href="#how-it-works"
                                className="transition hover:text-[#0b1220]"
                            >
                                How it works
                            </a>
                            {dashboardHref ? (
                                <Link
                                    href={dashboardHref}
                                    className="transition hover:text-[#0b1220]"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="transition hover:text-[#0b1220]"
                                >
                                    Sign in
                                </Link>
                            )}
                        </nav>

                        <p className="text-xs text-[#94a3b8]">
                            © {year} Allowee. Built for calmer money
                            conversations.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
