import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface AccountTypeOption {
    value: string;
    label: string;
    ready: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    app: { timezone: string; businessTimezone: string };
    quote: { message: string; author: string };
    auth: Auth;
    activeAccount: string | null;
    adminMembership: Pick<User, 'id' | 'name'> | null;
    accountTypes: AccountTypeOption[];
    flash: { status?: string; success?: string; error?: string };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_admin: boolean;
    account_types: string[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
