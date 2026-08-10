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
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type AccountTypeOption } from '@/types';
import { router } from '@inertiajs/react';
import { Building2, ChevronsUpDown, LoaderCircle, RefreshCw } from 'lucide-react';
import { useState } from 'react';

interface AccountSwitcherBadgeProps {
    activeAccount: string | null;
    allowedAccountTypes: string[];
    accountTypes: AccountTypeOption[];
}

export function AccountSwitcherBadge({ activeAccount, allowedAccountTypes, accountTypes }: AccountSwitcherBadgeProps) {
    const { isMobile, state } = useSidebar();
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
        'bg-sidebar-accent font-semibold text-sidebar-accent-foreground hover:bg-secondary hover:text-secondary-foreground data-[state=open]:bg-secondary data-[state=open]:text-secondary-foreground disabled:cursor-not-allowed disabled:opacity-70';

    if (availableAccounts.length <= 1) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton asChild className={badgeClassName} tooltip={`Current account: ${currentOption.label}`}>
                        <div role="status">
                            <Building2 aria-hidden="true" />
                            <span>{currentOption.label}</span>
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    return (
        <>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <SidebarMenuButton
                                aria-label={`Switch account. Current account: ${currentOption.label}`}
                                className={badgeClassName}
                                disabled={switchingAccount !== null}
                                title={state === 'collapsed' && !isMobile ? 'Switch account' : undefined}
                                type="button"
                            >
                                {switchingAccount !== null ? (
                                    <LoaderCircle aria-hidden="true" className="animate-spin" />
                                ) : (
                                    <RefreshCw aria-hidden="true" />
                                )}
                                <span>{currentOption.label}</span>
                                <ChevronsUpDown className="ml-auto group-data-[collapsible=icon]:hidden" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="start"
                            className="w-60 rounded-xl"
                            side={isMobile ? 'bottom' : state === 'collapsed' ? 'right' : 'bottom'}
                        >
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
                                        // Wait for the dropdown to close before opening the dialog so
                                        // Radix can release its pointer-events lock cleanly.
                                        setTimeout(() => setComingSoonAccount(option ?? null), 0);
                                        return;
                                    }

                                    if (value === resolvedActiveAccount) {
                                        return;
                                    }

                                    setSwitchingAccount(value);
                                    // Account-scoped Inertia responses must never survive a workspace change.
                                    router.flushAll();
                                    router.post(
                                        '/account-type/switch',
                                        { account_type: value },
                                        {
                                            preserveScroll: true,
                                            preserveState: false,
                                            replace: true,
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
                                        {!option.ready && <span className="text-muted-foreground ml-1.5 text-xs">(coming soon)</span>}
                                    </DropdownMenuRadioItem>
                                ))}
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>

            <Dialog open={comingSoonAccount !== null} onOpenChange={(open) => !open && setComingSoonAccount(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{comingSoonAccount?.label} — Coming Soon</DialogTitle>
                        <DialogDescription>
                            The claims workspace for {comingSoonAccount?.label} is still being developed. Choose one of the available accounts for now
                            and check back soon.
                        </DialogDescription>
                    </DialogHeader>
                </DialogContent>
            </Dialog>
        </>
    );
}
