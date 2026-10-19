import { Link } from '@inertiajs/react';
import { Check, Wallet } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { home } from '@/routes';

const stats = [
    { value: '₱4,250', label: 'Saved together' },
    { value: '12', label: 'Goals reached' },
    { value: '100%', label: 'Family focused' },
];

export default function AuthLoginLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-svh bg-white text-[#0b1220] lg:grid lg:h-svh lg:grid-cols-[59%_41%] lg:overflow-hidden">
            <aside className="relative hidden overflow-hidden bg-[#d9f1ff] px-16 py-10 lg:flex lg:h-full lg:flex-col">
                <div className="pointer-events-none absolute -right-40 -bottom-56 size-[760px] rounded-full border-[90px] border-white/15" />
                <div className="pointer-events-none absolute -right-10 -bottom-20 size-[420px] rounded-full bg-white/10" />

                <Link
                    href={home()}
                    className="relative z-10 flex w-fit items-center gap-4"
                >
                    <span className="flex size-12 items-center justify-center rounded-2xl bg-[#0b1220] shadow-lg">
                        <Wallet className="size-6 text-white" strokeWidth={1.8} />
                    </span>
                    <span className="text-2xl font-semibold tracking-[-0.03em]">
                        Allowee
                    </span>
                </Link>

                <div className="relative z-10 mt-8 flex w-fit items-center gap-2 rounded-full bg-white/60 px-4 py-1.5 text-[10px] font-semibold tracking-[0.18em] text-[#334155] uppercase">
                    <Check className="size-3.5" strokeWidth={3} />
                    Money lessons made simple
                </div>

                <div className="relative z-10 mt-7 max-w-[900px]">
                    <h1 className="text-[clamp(2.5rem,4.4vw,4.75rem)] leading-[1.02] font-extrabold tracking-[-0.055em] text-[#0b1220]">
                        <span className="block">Growing good money</span>
                        <span className="block">habits, together.</span>
                    </h1>

                    <div className="absolute -top-14 right-0 hidden size-24 rotate-12 items-center justify-center rounded-3xl bg-[#0b1220] shadow-2xl xl:flex xl:size-28">
                        <Wallet
                            className="size-10 text-white xl:size-12"
                            strokeWidth={1.8}
                        />
                    </div>
                </div>

                <p className="relative z-10 mt-6 max-w-[34rem] text-base leading-7 text-[#475569] xl:text-lg xl:leading-8">
                    One calm place for allowances, purchase requests, and honest
                    family conversations about money.
                </p>

                <div className="relative z-10 mt-8 grid max-w-[700px] grid-cols-3 gap-3">
                    {stats.map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-2xl bg-white/60 px-4 py-4 xl:px-5"
                        >
                            <p className="text-[22px] leading-none font-bold text-[#0b1220]">
                                {stat.value}
                            </p>
                            <p className="mt-2 text-[12px] leading-5 text-[#64748b]">
                                {stat.label}
                            </p>
                        </div>
                    ))}
                </div>

                <p className="relative z-10 mt-auto pt-8 text-[13px] text-[#64748b]">
                    Privacy by design <span className="px-1">•</span> One secure
                    space per family
                </p>
            </aside>

            <main className="flex min-h-svh items-center px-6 py-8 sm:px-10 lg:h-full lg:min-h-0 lg:overflow-hidden lg:px-14">
                <div className="w-full max-w-[560px]">
                    <Link
                        href={home()}
                        className="mb-12 flex w-fit items-center gap-3 lg:hidden"
                    >
                        <span className="flex size-12 items-center justify-center rounded-2xl bg-[#0b1220]">
                            <Wallet
                                className="size-6 text-white"
                                strokeWidth={1.8}
                            />
                        </span>
                        <span className="text-2xl font-semibold tracking-[-0.03em]">
                            Allowee
                        </span>
                    </Link>
                    {children}
                </div>
            </main>
        </div>
    );
}
