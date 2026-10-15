import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

export default function NewAccountDialog() {
    // The server shares the held Google email here, or null when there is nothing to confirm.
    const { pendingRegistration } = usePage<{ pendingRegistration?: string | null }>().props;

    // True while the account is being created, so the buttons cannot be clicked twice.
    const [processing, setProcessing] = useState(false);

    // "Create account": ask the server to build the family, its database, and the parent user.
    const handleCreate = () => {
        router.post('/auth/register', {}, {
            onStart: () => setProcessing(true),   // Show the spinner while the server works (a few seconds).
            onFinish: () => setProcessing(false), // Stop it when the server answers.
        });
    };

    // "Cancel" (also Esc or a click outside): tell the server to forget the held Google details.
    const handleCancel = () => {
        if (processing) {
            return; // Do not allow cancelling while the account is being built.
        }

        router.post('/auth/register/cancel');
    };

    return (
        <Dialog
            // Open exactly when the server says there is a held email.
            open={Boolean(pendingRegistration)}
            // Closing the dialog any other way counts as cancelling.
            onOpenChange={(open) => {
                if (!open) {
                    handleCancel();
                }
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create a new account?</DialogTitle>
                    <DialogDescription>
                        {pendingRegistration} is new to the Allowance Tracker. Do you want
                        to create a new account with this Google account?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" onClick={handleCancel} disabled={processing}>
                        Cancel
                    </Button>
                    <Button onClick={handleCreate} disabled={processing}>
                        {processing && <Spinner />}
                        {processing ? 'Creating your account...' : 'Create account'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}