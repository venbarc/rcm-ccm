import { AccountSwitcherBadge } from '@/components/account-switcher-badge';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Activity, ArrowLeft, FileText, LayoutGrid, UserRoundCog } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth, activeAccount, accountTypes } = usePage<SharedData>().props;
    const workspaceItems: NavItem[] = [
        { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
        { title: 'Claims', url: '/claims', icon: FileText },
        { title: 'Activity Logs', url: '/activity-logs', icon: Activity },
    ];
    const administrationItems: NavItem[] = auth.user?.is_admin ? [{ title: 'User Management', url: '/user-management', icon: UserRoundCog }] : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <div className="flex flex-col gap-2 px-2 py-1">
                            <SidebarMenuButton size="lg" asChild>
                                <Link href="/dashboard" prefetch>
                                    <AppLogo />
                                </Link>
                            </SidebarMenuButton>
                            <a
                                href="/oneaccess"
                                className="inline-flex items-center gap-1.5 self-start rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                            >
                                <ArrowLeft className="h-3.5 w-3.5" aria-hidden="true" />
                                OneAccess
                            </a>
                            {auth.user && (
                                <AccountSwitcherBadge
                                    activeAccount={activeAccount}
                                    allowedAccountTypes={auth.user.account_types}
                                    accountTypes={accountTypes}
                                />
                            )}
                        </div>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={workspaceItems} />
                {administrationItems.length > 0 && <NavMain items={administrationItems} label="Administration" />}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
