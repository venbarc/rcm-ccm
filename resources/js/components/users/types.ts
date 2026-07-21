import type { PaginationLink } from '@/components/pagination';

export interface TeamUser {
    id: number;
    name: string;
    email: string;
}

export interface ManagedUser extends TeamUser {
    is_admin: boolean;
    can_assign_claims: boolean;
    account_types: string[] | null;
    admins: TeamUser[];
    created_at: string;
}

export interface UserPage {
    data: ManagedUser[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}
