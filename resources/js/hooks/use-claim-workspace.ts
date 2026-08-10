import { useActiveAccount } from '@/hooks/use-active-account';
import { claimWorkspace } from '@/lib/claim-workspace';

export function useClaimWorkspace() {
    const { account } = useActiveAccount();

    return claimWorkspace(account?.value);
}
