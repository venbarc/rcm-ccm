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
                        <SidebarMenuButton asChild size="lg" tooltip="RCM CCM dashboard">
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            className="border border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                            tooltip="Back to OneAccess"
                        >
                            <a href="/oneaccess">
                                <ArrowLeft aria-hidden="true" />
                                <span>Back to OneAccess</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                {auth.user && (
                    <AccountSwitcherBadge activeAccount={activeAccount} allowedAccountTypes={auth.user.account_types} accountTypes={accountTypes} />
                )}
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
