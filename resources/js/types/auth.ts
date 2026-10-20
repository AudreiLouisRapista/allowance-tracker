export type User = {
    id: number;
    phonenumber?: string;
    name: string;
    email: string;
    avatar?: string;
    role?: 'parent' | 'student';
    status?: string;
    is_creator?: number;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
