<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    BadgeDollarSign,
    Landmark,
    LayoutGrid,
    MessageSquare,
    PiggyBank,
    Target,
} from 'lucide-vue-next';
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
import { dashboard } from '@/routes';
import { index as accounts } from '@/routes/accounts';
import { index as budgets } from '@/routes/budgets';
import { index as chat } from '@/routes/chat';
import { index as debts } from '@/routes/debts';
import { index as goals } from '@/routes/goals';
import { index as transactions } from '@/routes/transactions';
import type { NavItem } from '@/types';

const page = usePage();
const currentTeam = computed(() => page.props.currentTeam);

const dashboardUrl = computed(() =>
    currentTeam.value ? dashboard(currentTeam.value.slug).url : '/',
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl.value,
        icon: LayoutGrid,
    },
    {
        title: 'Accounts',
        href: currentTeam.value ? accounts({ current_team: currentTeam.value.slug }).url : '#',
        icon: Landmark,
    },
    {
        title: 'Transactions',
        href: currentTeam.value ? transactions({ current_team: currentTeam.value.slug }).url : '#',
        icon: ArrowRightLeft,
    },
    {
        title: 'Budgets',
        href: currentTeam.value ? budgets({ current_team: currentTeam.value.slug }).url : '#',
        icon: PiggyBank,
    },
    {
        title: 'Goals',
        href: currentTeam.value ? goals({ current_team: currentTeam.value.slug }).url : '#',
        icon: Target,
    },
    {
        title: 'Debts',
        href: currentTeam.value ? debts({ current_team: currentTeam.value.slug }).url : '#',
        icon: BadgeDollarSign,
    },
    {
        title: 'Chat',
        href: currentTeam.value ? chat({ current_team: currentTeam.value.slug }).url : '#',
        icon: MessageSquare,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
