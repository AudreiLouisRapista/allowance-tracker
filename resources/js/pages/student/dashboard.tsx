import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react'; // useState remembers if the Alert is visible; useEffect runs the 5-second timer

import { Alert, AlertTitle } from '@/components/ui/alert'; 
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboardPathForRole } from '@/lib/dashboard-path';


type WelcomeFlash = {
    type: 'new' | 'returning'; // 'new' = first sign-in ever (google_id was empty), 'returning' = signed in before
    name: string; // the user's name from the tenant users table
};

// Shape of the shared props this page reads
type PageProps = {
    name: string; // the app name (config('app.name') shared by HandleInertiaRequests)
    flash: {
        welcome?: WelcomeFlash | null; // one-time welcome data, null when there is nothing to show
    };
};

// How long the Alert stays visible, in milliseconds
const WELCOME_DURATION_MS = 5000;


export default function StudentDashboard() {

    // appName = the app's name; flash = the one-time messages from the last request
        const { name: appName, flash } = usePage<PageProps>().props;
        const welcome = flash.welcome;
    
        // The Alert starts visible only if the backend sent welcome data
        const [isWelcomeVisible, setIsWelcomeVisible] = useState(Boolean(welcome));
    
        useEffect(() => {
            // Nothing is shown, so there is no timer to start
            if (!isWelcomeVisible) {
                return;
            }
    
            // After 5 seconds, hide the Alert
            const timerId = setTimeout(
                () => setIsWelcomeVisible(false),
                WELCOME_DURATION_MS,
            );
    
            // Cleanup: cancel the timer if the page closes before the 5 seconds end
            return () => clearTimeout(timerId);
        }, [isWelcomeVisible]);

    return (
        <>
            <Head title="Student Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {/* Render the Alert only while it is visible and the welcome data exists */}
                {isWelcomeVisible && welcome && (
                    <Alert>
                        <AlertTitle>
                            {/* First-time users get "Welcome to {appName}, {name}"; returning users get "Welcome back! {name}" */}
                            {welcome.type === 'new'
                                ? `Welcome to ${appName}, ${welcome.name}`
                                : `Welcome back! ${welcome.name}`}
                        </AlertTitle>
                    </Alert>
                )}
                <h1 className="text-xl font-semibold">Student Dashboard</h1>
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {Array.from({ length: 3 }, (_, index) => (
                        <div
                            key={index}
                            className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                        >
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        </div>
                    ))}
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

StudentDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboardPathForRole('student'),
        },
    ],
};
