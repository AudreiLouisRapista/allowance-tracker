import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    phonenumber,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    phonenumber: string | null; // Sent by ProfileController::edit. It is null when no phone is saved.
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and phone number"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phonenumber">Phone number</Label>

                                <div className="flex">
                                    {/* Fixed Philippine prefix. It is not saved, only the digits after it are. */}
                                    <span className="inline-flex items-center rounded-l-md border border-r-0 px-3 text-sm text-muted-foreground">
                                        +63
                                    </span>

                                    <Input
                                        id="phonenumber"
                                        name="phonenumber"
                                        className="rounded-l-none"
                                        defaultValue={phonenumber ?? ''} 
                                        inputMode="numeric"         
                                        maxLength={10}             
                                        autoComplete="tel-national"
                                        placeholder="9XXXXXXXXX"
                                        // Remove anything that is not a digit as the person types.
                                        onInput={(e) => {
                                            e.currentTarget.value = e.currentTarget.value.replace(/\D/g, '');
                                        }}
                                    />
                                </div>

                                <InputError
                                    className="mt-2"
                                    message={errors.phonenumber}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                {/* No name attribute, so the email is never sent. It cannot be changed. */}
                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full bg-muted"
                                    defaultValue={auth.user.email}
                                    readOnly
                                />

                                <p className="text-sm text-muted-foreground">
                                    Your email comes from your Google account and cannot be changed.
                                </p>
                            </div>


                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
