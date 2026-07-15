import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Activity, ArrowLeft, FileSpreadsheet, FileText, LayoutGrid, UserRoundCog, Users } from 'lucide-react';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [
    {
        title: 'Back to One Access',
        url: '/oneaccess',
        icon: ArrowLeft,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const mainNavItems: NavItem[] = [
        { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
        { title: 'Claims', url: '/claims', icon: FileText },
        ...(auth.user?.is_admin ? [{ title: 'Import Claims', url: '/claims-import', icon: FileSpreadsheet }] : []),
        ...(auth.user?.is_admin || auth.user?.can_assign_claims ? [{ title: 'Assignments', url: '/assignments', icon: Users }] : []),
        { title: 'Activity Logs', url: '/activity-logs', icon: Activity },
        ...(auth.user?.is_admin ? [{ title: 'User Management', url: '/user-management', icon: UserRoundCog }] : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
