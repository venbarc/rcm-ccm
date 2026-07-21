import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type AccountTypeOption } from '@/types';
import { router } from '@inertiajs/react';
import { CheckCircle2, ChevronsUpDown, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

interface AccountSwitcherBadgeProps {
    activeAccount: string | null;
    allowedAccountTypes: string[];
    accountTypes: AccountTypeOption[];
}

export function AccountSwitcherBadge({ activeAccount, allowedAccountTypes, accountTypes }: AccountSwitcherBadgeProps) {
    const [switchingAccount, setSwitchingAccount] = useState<string | null>(null);
    const [comingSoonAccount, setComingSoonAccount] = useState<AccountTypeOption | null>(null);

    const allowedValues = new Set(allowedAccountTypes);
    const availableAccounts = accountTypes.filter((option) => allowedValues.has(option.value));
    const resolvedActiveAccount = activeAccount ?? availableAccounts.find((option) => option.ready)?.value ?? null;
    const currentOption = availableAccounts.find((option) => option.value === resolvedActiveAccount);

    if (!currentOption) {
        return null;
    }

    const badgeClassName =
        'group-data-[collapsible=icon]:hidden inline-flex max-w-full items-center gap-1 rounded-lg bg-sky-100 px-3 py-1 text-xs font-semibold text-blue-950 transition hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-70';

    if (availableAccounts.length <= 1) {
        return (
            <span className={badgeClassName}>
                <span className="truncate">{currentOption.label}</span>
            </span>
        );
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button type="button" className={badgeClassName} disabled={switchingAccount !== null}>
                        {switchingAccount !== null ? <LoaderCircle className="size-3 animate-spin" /> : <CheckCircle2 className="size-3" />}
                        <span className="max-w-40 truncate">{currentOption.label}</span>
                        <ChevronsUpDown className="size-3" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-60 rounded-xl" align="start">
                    <DropdownMenuLabel>Switch Account</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuRadioGroup
                        value={resolvedActiveAccount ?? ''}
                        onValueChange={(value) => {
                            if (switchingAccount !== null) {
                                return;
                            }

                            const option = availableAccounts.find((account) => account.value === value);

                            if (!option?.ready) {
                                // Defer to the next tick so the dropdown finishes closing before the
                                // dialog opens — opening both overlays in the same tick leaves Radix's
                                // body pointer-events lock stuck after the dialog is dismissed.
                                setTimeout(() => setComingSoonAccount(option ?? null), 0);
                                return;
                            }

                            if (value === resolvedActiveAccount) {
                                return;
                            }

                            setSwitchingAccount(value);
                            router.post(
                                '/account-type/switch',
                                { account_type: value },
                                {
                                    preserveScroll: true,
                                    onFinish: () => setSwitchingAccount(null),
                                },
                            );
                        }}
                    >
                        {availableAccounts.map((option) => (
                            <DropdownMenuRadioItem
                                key={option.value}
                                value={option.value}
                                disabled={switchingAccount !== null}
                                className="cursor-pointer"
                            >
                                {option.label}
                                {!option.ready && <span className="ml-1.5 text-xs text-muted-foreground">(coming soon)</span>}
                            </DropdownMenuRadioItem>
                        ))}
                    </DropdownMenuRadioGroup>
                </DropdownMenuContent>
            </DropdownMenu>

            <Dialog open={comingSoonAccount !== null} onOpenChange={(open) => !open && setComingSoonAccount(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{comingSoonAccount?.label} — Coming Soon</DialogTitle>
                        <DialogDescription>
                            The claims workspace for {comingSoonAccount?.label} is still being developed. We're focused on Tricity Pain Associates
                            right now — check back soon for this account.
                        </DialogDescription>
                    </DialogHeader>
                </DialogContent>
            </Dialog>
        </>
    );
}
