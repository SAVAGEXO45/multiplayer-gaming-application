<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Gift,
    Gamepad2,
    LayoutDashboard,
    Medal,
    ShieldCheck,
    Trophy,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => Boolean(page.props.auth.user?.is_admin));

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Games', href: '/games', icon: Gamepad2 },
    { title: 'Rooms', href: '/rooms', icon: Users },
    { title: 'Leaderboard', href: '/leaderboard', icon: Trophy },
    { title: 'Achievements', href: '/achievements', icon: Medal },
    { title: 'Rewards', href: '/rewards', icon: Gift },
];

const adminNavItems: NavItem[] = [
    { title: 'Admin overview', href: '/admin', icon: ShieldCheck },
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Games', href: '/admin/games', icon: Gamepad2 },
    { title: 'Rooms', href: '/admin/rooms', icon: LayoutDashboard },
    { title: 'Rewards', href: '/admin/rewards', icon: Gift },
    { title: 'Activity', href: '/admin/activity', icon: Medal },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Arcade" />
            <NavMain
                v-if="isAdmin"
                :items="adminNavItems"
                label="Administration"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
