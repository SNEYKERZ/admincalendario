export type User = {
    id: number;
    name: string;
    first_name?: string | null;
    last_name?: string | null;
    identification?: string | null;
    phone?: string | null;
    birth_date?: string | null;
    hire_date?: string | null;
    email: string;
    avatar?: string | null;
    photo_url?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

