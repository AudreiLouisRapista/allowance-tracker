import { Head, router, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';


type ArchoveStudent = {
    id: number;
    name: string;
    email: string;
    phonenumber: string | null | undefined;
    status: { id: number; status: string }; // Comes from the status relationship.
    removed_at : string | null
};

type PageProps = {
    flash: {
        add_student_error?: string | null;
    };
};

export default function Students({ familyMember }: { familyMember: ArchoveStudent[] }) {
    const { flash } = usePage<PageProps>().props;
    // The student being edited. null means the edit dialog is closed.

    // RECOVER: ask first, then send RECOVER /students/{id}.
    const handleRecover = (student: ArchoveStudent) => {
        if (confirm(`Restore ${student.name}?`)) {
            router.patch(`/students/${student.id}/restore`,{}, { preserveScroll: true });
        }
    };

    const [addDialogOpen, setAddDialogOpen] = useState(Boolean(flash.add_student_error));



    return (
        <>
            <Head title="Archive" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className="text-xl font-semibold">Archive</h1>
                    <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                        <Button
                            className="w-fit transition-all hover:-translate-y-0.5 hover:shadow-md" variant="secondary"
                            size="sm"
                            >
                            <Link href="/students">Back</Link>
                        </Button>
                    </div>
                </div>
                
                {/* READ: one table row for each student sent by the controller. */}
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b">
                            <th className="py-2">Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th className='text-center'>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {familyMember.map((student) => (
                            <tr key={student.id} className="border-b">
                                <td className="py-2">{student.name}</td>
                                <td>{student.email}</td>
                                <td>{student.phonenumber ? `+63${student.phonenumber}` : '-'}</td>
                                <td className="font-bold text-red-500">{student.status.status}</td>
                                <td className="font-bold">{student.removed_at ? new Date (student.removed_at).toLocaleDateString() : '-'}</td>
                                <td  className='text-center'>
                                    {/* UPDATE: remember which student to edit, which opens the dialog below. */}
                                    <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                                        <Button size="sm" variant="default" onClick={() => handleRecover(student)}>Recover</Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {/* Message for an empty list */}
                        {familyMember.length === 0 && (
                            <tr>
                                <td colSpan={6} className="py-8 text-center text-muted-foreground">No students yet.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

        </>
    );
}

// Breadcrumb shown above the page.
Students.layout = { breadcrumbs: [{ title: 'Students', href: '/students' }] };