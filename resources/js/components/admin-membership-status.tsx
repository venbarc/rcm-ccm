import { Badge } from '@/components/ui/badge';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ShieldAlert, ShieldCheck, UserRoundCheck } from 'lucide-react';

const unassignedMessage = "You don't have an admin yet. Contact an admin to add you to their members.";

export function AdminMembershipStatus() {
    const { adminMembership, auth } = usePage<SharedData>().props;

    if (!auth.user) {
        return null;
    }

    if (auth.user.is_admin) {
        return (
            <Badge className="gap-1.5 border-blue-300 bg-blue-50 px-2.5 py-1 text-blue-800" title="Administrator" variant="outline">
                <ShieldCheck aria-hidden="true" className="size-3.5 shrink-0" />
                <span className="font-semibold">Administrator</span>
            </Badge>
        );
    }

    if (adminMembership) {
        return (
            <Badge
                className="max-w-[min(24rem,55vw)] gap-1.5 border-emerald-300 bg-emerald-50 px-2.5 py-1 text-emerald-800"
                title={`You are under admin ${adminMembership.name}`}
                variant="outline"
            >
                <UserRoundCheck aria-hidden="true" className="size-3.5 shrink-0" />
                <span className="hidden whitespace-nowrap sm:inline">You are under admin:</span>
                <span className="truncate font-semibold">{adminMembership.name}</span>
            </Badge>
        );
    }

    return (
        <Badge
            className="max-w-[min(38rem,65vw)] gap-1.5 border-amber-300 bg-amber-50 px-2.5 py-1 text-amber-800"
            role="status"
            title={unassignedMessage}
            variant="outline"
        >
            <ShieldAlert aria-hidden="true" className="size-3.5 shrink-0" />
            <span className="hidden truncate lg:inline">{unassignedMessage}</span>
            <span className="whitespace-nowrap lg:hidden">No admin assigned</span>
        </Badge>
    );
}
