import { Form, Head, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import NewAccountDialog from '@/components/new-account-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAutoDismiss } from '@/hooks/use-auto-dismiss';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';

type PageProps = {
    flash: {
        google_error?: string | null;
    };
};

function GoogleIcon({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={className}
            aria-hidden="true"
        >
            <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"
            />
            <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"
            />
            <path
                fill="#FBBC05"
                d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"
            />
            <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"
            />
        </svg>
    );
}

export default function Login() {
    const { flash } = usePage<PageProps>().props;
    const googleError = useAutoDismiss(flash.google_error);

    return (
        <>
            <Head title="Log in" />

            <div className="flex flex-col">
                <h1 className="text-3xl leading-[1.05] font-extrabold tracking-[-0.045em] text-[#0b1220] xl:text-[46px]">
                    Welcome to Allowee
                </h1>

                <p className="mt-4 max-w-[540px] text-base leading-7 text-[#64748b] xl:text-[17px]">
                    Sign in with your family email, or register a new account
                    with Google.
                </p>

                {googleError && (
                    <Alert variant="destructive" className="mt-5 rounded-2xl">
                        <AlertTitle>Sign-in failed</AlertTitle>
                        <AlertDescription>{googleError}</AlertDescription>
                    </Alert>
                )}

                {/* Existing parents and students sign in with their email. */}
                <Form action="/auth/email/login" method="post" className="mt-8">
                    {({ processing, errors }) => (
                        <div className="flex flex-col gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="email"
                                    required
                                    placeholder="email@example.com"
                                    aria-invalid={Boolean(errors.email)}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <Button
                                type="submit"
                                variant="secondary"
                                className="h-14 w-full rounded-2xl text-base font-semibold transition-all hover:-translate-y-0.5 hover:shadow-md"
                                disabled={processing}
                            >
                                {processing ? 'Signing in...' : 'Sign in'}
                            </Button>
                        </div>
                    )}
                </Form>

                {/* New users register with Google below this label. */}
                <div className="my-6 flex items-center gap-4 text-[10px] font-semibold tracking-[0.18em] text-[#94a3b8] uppercase">
                    <span className="h-px flex-1 bg-[#e2e8f0]" />
                    Register using Google
                    <span className="h-px flex-1 bg-[#e2e8f0]" />
                </div>

                <Button
                    asChild
                    variant="default"
                    className="h-14 w-full gap-3 rounded-2xl text-base font-medium transition-all hover:-translate-y-0.5 hover:shadow-md"
                >
                    <a href="/auth/google/signup">
                        <GoogleIcon className="size-5" />
                        Sign up with Google
                    </a>
                </Button>

                <div className="mt-6 flex items-start gap-3 rounded-2xl bg-[#f1f5f9] px-5 py-5">
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#dbeafe] text-[#3b82f6]">
                        <Check className="size-4" strokeWidth={3} />
                    </span>
                    <p className="text-sm leading-6 text-[#64748b]">
                        No passwords to remember. We only use your verified
                        Google email to connect you to your family.
                    </p>
                </div>

                <p className="mt-8 text-center text-[13px] leading-6 text-[#94a3b8]">
                    By continuing, you agree to the{' '}
                    <span className="text-[#64748b]">Terms of Service</span> and{' '}
                    <span className="text-[#64748b]">Privacy Policy</span>.
                </p>
            </div>

            <NewAccountDialog />
        </>
    );
}

Login.layout = (page: ReactNode) => (
    <AuthLoginLayout>{page}</AuthLoginLayout>
);
