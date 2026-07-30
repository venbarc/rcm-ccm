import type { PaginationLink } from '@/components/pagination';

export interface TeamUser {
    id: number;
    name: string;
    email: string;
}

export interface TeamCandidate extends TeamUser {
    owner: TeamUser | null;
    is_selectable: boolean;
    is_selected: boolean;
}

export interface ManagedUser extends TeamUser {
    is_admin: boolean;
    account_types: string[] | null;
    admins: TeamUser[];
    can_manage: boolean;
    members_under_you_count: number;
    created_at: string;
}

export interface UserPage {
    data: ManagedUser[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}
