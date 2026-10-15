import { Head, usePage } from '@inertiajs/react'; // Head sets the browser tab title; usePage reads the props Laravel shares with every page
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'; // shadcn Alert pieces, used to show a sign-in error
import { Button } from '@/components/ui/button'; // shadcn Button, used for the "Sign in with Google" button
import NewAccountDialog from '@/components/new-account-dialog'; // shadcn Dialog, used to confirm creating a new account when the Google account is not yet registered

// Shape of the data this page reads from Laravel (shared by HandleInertiaRequests)
type PageProps = {
    flash: {
        google_error?: string | null; // error text set by GoogleAuthController::backToLogin(), null when there is no error
    };
};

export default function Login() {
    // Read the one-time flash data. It exists only for the request right after a failed Google sign-in
    const { flash } = usePage<PageProps>().props;

    return (
        <>
            {/* Sets the browser tab title */}
            <Head title="Log in" />

            <div className="flex flex-col gap-6">
                {/* Only render the Alert when the backend sent an error message */}
                {flash.google_error && (
                    <Alert variant="destructive">
                        {/* Short heading of the error */}
                        <AlertTitle>Sign-in failed</AlertTitle>
                        {/* The exact message from the backend, e.g. "This account has been removed from the family." */}
                        <AlertDescription>{flash.google_error}</AlertDescription>
                    </Alert>
                )}

                {/* asChild makes the Button render our <a> tag instead of its own <button>, so it keeps the button style but works as a link */}
                <Button
                    asChild
                    variant="outline"
                    className="w-full"
                    data-test="google-login-button"
                >
                    {/* A plain <a> (not Inertia's <Link>) so the browser does a full page navigation to Google; Inertia's background request can't follow a redirect to another site */}
                    <a href="/auth/google/redirect">
                        {/* Google "G" logo as inline SVG, so no new library is needed. aria-hidden hides it from screen readers because the text already says it */}
                        <svg viewBox="0 0 24 24" className="size-5" aria-hidden="true">
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
                        {/* The label you asked for */}
                        Sign in with Google
                    </a>
                </Button>
            </div>

             <NewAccountDialog />
        </>
    );

}

// Title and description that the auth layout shows above the page content
Login.layout = {
    title: 'Log in to your account',
    description: 'Sign in with your Google account to continue',
};