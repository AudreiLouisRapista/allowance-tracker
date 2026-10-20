import { Head, router, usePage, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';

type ArchivedFamilyMember = {
    id: number;
    name: string;
    email: string;
    phonenumber: string | null | undefined;
    status: { id: number; status: string }; // Comes from the status relationship.
    role: { id: number; role: string } | null; // Comes from the role relationship.
    removed_at : string | null
};

type PageProps = {
    auth: {
        user: {
            id: number;
            is_creator: number;
        };
    };
};

export default function FamilyMemberArchives({ familyMembers }: { familyMembers: ArchivedFamilyMember[] }) {
    const { auth } = usePage<PageProps>().props;
    // Only the family creator may restore another parent.
    const isMainParent = Number(auth?.user?.is_creator) === 1;

    const handleRecover = (member: ArchivedFamilyMember) => {
        if (confirm(`Restore ${member.name}?`)) {
            router.patch(`/family-members/${member.id}/restore`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Archive" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className="text-xl font-semibold">Archive</h1>
                    <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                        <Button
                            className="w-fit transition-all hover:-translate-y-0.5 hover:shadow-md" variant="outline"
                            size="sm"
                            >
                            <Link href="/family-members">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
                
                {/* One row for each archived family member. */}
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
                        {familyMembers.map((member) => (
                            <tr key={member.id} className="border-b">
                                <td className="py-2">{member.name}</td>
                                <td>{member.email}</td>
                                <td>{member.phonenumber ? `+63${member.phonenumber}` : '-'}</td>
                                <td className="font-bold text-red-500">{member.status.status}</td>
                                <td className="font-bold">{member.removed_at ? new Date(member.removed_at).toLocaleDateString() : '-'}</td>
                                <td  className='text-center'>
                                    {/* Only the main parent may restore another parent. */}
                                    {member.role?.role !== 'parent' || isMainParent ? (
                                        <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                                            <Button size="sm" variant="default" onClick={() => handleRecover(member)}>Recover</Button>
                                        </div>
                                    ) : (
                                        <span className="text-xs text-muted-foreground">Main parent only</span>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {/* Message for an empty list */}
                        {familyMembers.length === 0 && (
                            <tr>
                                <td colSpan={6} className="py-8 text-center text-muted-foreground">No archived family members.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

        </>
    );
}

// Breadcrumb shown above the page.
FamilyMemberArchives.layout = { breadcrumbs: [{ title: 'Archives', href: '/family-members/archives' }] };