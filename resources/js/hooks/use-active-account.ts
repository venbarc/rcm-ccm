import { type AccountTypeOption, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface ActiveAccountWorkspace {
    account: AccountTypeOption | null;
    label: string;
    claimsTitle: string;
    workspaceLabel: string;
}

export function useActiveAccount(): ActiveAccountWorkspace {
    const { activeAccount, accountTypes } = usePage<SharedData>().props;
    const account = accountTypes.find((option) => option.value === activeAccount) ?? null;
    const label = account?.label ?? 'Claims';

    return {
        account,
        label,
        claimsTitle: account ? `${label} Claims` : 'Claims',
        workspaceLabel: account ? `${label} workspace` : 'Claims workspace',
    };
}
