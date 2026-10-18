export function dashboardPathForRole(role: unknown): string {
    if (role === 'parent') {
        return '/dashboard';
    }

    if (role === 'student') {
        return '/student/dashboard';
    }

    throw new Error(`Unsupported dashboard role: ${String(role)}`);
}
