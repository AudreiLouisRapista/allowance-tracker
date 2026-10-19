import { Form, Head, router, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { useAutoDismiss } from '@/hooks/use-auto-dismiss';

type Student = {
    id: number;
    name: string;
    email: string;
    phonenumber: string | null | undefined;
    status: { id: number; status: string }; // Comes from the status relationship.
    role: { id: number; role: string };     // Comes from the role relationship.
};

type PageProps = {
    flash: {
        add_member_error?: string | null;
        add_member_success?: string | null;
    };
    // The Google account waiting for confirmation, shared by HandleInertiaRequests.
    pendingMember?: { email: string; role: 'parent' | 'student' } | null;
};

export default function FamilyMembers({ familyMember }: { familyMember: Student[] }) {
    const { flash, pendingMember } = usePage<PageProps>().props;
    const memberError = useAutoDismiss(flash.add_member_error);
    const memberSuccess = useAutoDismiss(flash.add_member_success);
    // The student being edited. null means the edit dialog is closed.
    const [editing, setEditing] = useState<Student | null>(null);

    // The role selected in the Add Family member dialog.
    const [role, setRole] = useState<'parent' | 'student'>('student');

    // True while POST /family-members/register is running.
    const [addingMember, setAddingMember] = useState(false);

    // REMOVE: ask first, then send DELETE /students/{id}.
    const handleRemove = (student: Student) => {
        if (confirm(`Remove ${student.name}?`)) {
            router.delete(`/students/${student.id}`, { preserveScroll: true });
        }
    };

    // Reopen the dialog automatically when the backend reports an add-member error.
    const [addDialogOpen, setAddDialogOpen] = useState(Boolean(memberError));



    return (
        <>
            <Head title="Family members" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className="text-xl font-semibold">Family members</h1>

                    {/* Buttons sit on the right on larger screens and stack on mobile. */}
                    <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                        <Button
                            className="w-full transition-all hover:-translate-y-0.5 hover:shadow-md sm:w-auto"
                            size="sm"
                            onClick={() => setAddDialogOpen(true)}
                        >
                            Add Family member
                        </Button>
                        <Button
                            asChild
                            className="w-full transition-all hover:-translate-y-0.5 hover:shadow-md sm:w-auto"
                            variant="secondary"
                            size="sm"
                        >
                            <Link href="/archives">Show Archives</Link>
                        </Button>
                    </div>
                </div>

                {/* Show why adding the member failed. */}
                {memberError && (
                    <p role="alert" className="text-sm text-destructive">{memberError}</p>
                )}

                {/* Show that the member was created. */}
                {memberSuccess && (
                    <p className="text-sm text-green-600">{memberSuccess}</p>
                )}
                    
                {/* READ: one table row for each family member sent by the controller. */}
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b">
                            <th className="py-2">Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Statu</th>
                            <th>Role</th>
                            <th className='text-right'>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {familyMember.map((student) => (
                            <tr key={student.id} className="border-b transition-colors hover:bg-muted/50">
                                <td className="py-2">{student.name}</td>
                                <td>{student.email}</td>
                                <td>{student.phonenumber ? `+63${student.phonenumber}` : '-'}</td>
                                <td className="font-bold text-green-500">{student.status.status}</td>
                                <td className="font-bold">{student.role.role}</td>
                                <td  className='text-right'>
                                    {/* UPDATE: remember which student to edit, which opens the dialog below. */}
                                    <div className="flex flex-wrap justify-end gap-2">
                                        <Button
                                            className="transition-all hover:-translate-y-0.5 hover:shadow-sm"
                                            size="sm"
                                            variant="outline"
                                            onClick={() => setEditing(student)}
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            className="transition-all hover:-translate-y-0.5 hover:shadow-sm"
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => handleRemove(student)}
                                        >
                                            Archive
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {/* Message for an empty list */}
                        {familyMember.length === 0 && (
                            <tr>
                                <td colSpan={6} className="py-8 text-center text-muted-foreground">No family members yet.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* UPDATE: the dialog opens when "editing" holds a student, and closes when it is null. */}
            <Dialog open={editing !== null} onOpenChange={(open) => { if (!open) setEditing(null); }}>
                <DialogContent>
                    {editing && (
                        // key makes the form start fresh for each student. PATCH /students/{id} saves it.
                        <Form key={editing.id} action={`/students/${editing.id}`} method="patch" onSuccess={() => setEditing(null)} className="space-y-4">
                            {({ processing, errors }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>Edit family member</DialogTitle>
                                        <DialogDescription>{editing.email} (the email cannot be changed)</DialogDescription>
                                    </DialogHeader>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-name">Name</Label>
                                        <Input id="edit-name" name="name" defaultValue={editing.name} required />
                                        <InputError message={errors.name} />
                                    </div>
                                        <div className="grid gap-2">
                                        <Label htmlFor="edit-name">Phone #</Label>
                                        <Input id="edit-phonenumber]" 
                                        name="phonenumber" 
                                        defaultValue={editing.phonenumber ?? ''}
                                        inputMode="numeric"
                                        maxLength={10}
                                        placeholder='9xxxxxxxxx' />
                                        <InputError message={errors.phonenumber} />
                                    </div>
                                    <DialogFooter>
                                        <Button type="submit" disabled={processing}>   {processing ? 'Updating...' : 'Update'}</Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>


            {/* Add Family member using Google OAuth */}
            <Dialog
                open={addDialogOpen}
                onOpenChange={setAddDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Family member</DialogTitle>
                        <DialogDescription>
                            Register a family member using their Google account.
                            The family member will receive an email invitation to join.
                        </DialogDescription>
                    </DialogHeader>

                    {/* The parent chooses what role the new Google account will have. */}
                    <div className="grid gap-2">
                        <Label htmlFor="member-role">Account role</Label>
                        <Select value={role} onValueChange={(value) => setRole(value as 'parent' | 'student')}>
                            <SelectTrigger id="member-role" className="w-full">
                                <SelectValue placeholder="Select a role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="student">Student</SelectItem>
                                <SelectItem value="parent">Parent</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Show the backend error, for example a duplicate Google email. */}
                    {memberError && (
                        <p role="alert" className="text-sm text-destructive">
                            {memberError}
                        </p>
                    )}

                    {/* Send the chosen role to the protected add-member route. */}
                    <Button asChild variant="outline" className="w-full">
                        <a href={`/family-members/add?role=${role}`}>
                            <svg
                                viewBox="0 0 24 24"
                                className="mr-2 size-5"
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
                            Sign up with Google
                        </a>
                    </Button>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setAddDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Confirmation dialog shown after Google returns with a pending account. */}
            <Dialog
                open={Boolean(pendingMember)}
                onOpenChange={(open) => {
                    // Closing the dialog any other way counts as cancelling.
                    if (!open && !addingMember) {
                        router.post('/family-members/register/cancel');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add this family member?</DialogTitle>
                        <DialogDescription>
                            {pendingMember?.email} will be added as a {pendingMember?.role}.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        {/* Cancel: forget the held Google data, create nothing. */}
                        <Button
                            variant="outline"
                            disabled={addingMember}
                            onClick={() => router.post('/family-members/register/cancel')}
                        >
                            Cancel
                        </Button>

                        {/* Confirm: create the user in the current tenant and link parent_student. */}
                        <Button
                            disabled={addingMember}
                            onClick={() =>
                                router.post('/family-members/register', {}, {
                                    onStart: () => setAddingMember(true),
                                    onFinish: () => setAddingMember(false),
                                })
                            }
                        >
                            {addingMember ? 'Adding...' : 'Add member'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </>
    );
}

// Breadcrumb shown above the page.
FamilyMembers.layout = { breadcrumbs: [{ title: 'Family members', href: '/students' }] };