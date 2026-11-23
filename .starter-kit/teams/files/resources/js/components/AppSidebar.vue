<script setup lang="ts">
import { dashboardUrl, extraNavItems, mainNavItems } from '@/lib/navigation'

const page = usePage()
const teamSlug = computed(() => page.props.currentTeam?.slug)
</script>

<template>
  <UiSidebar collapsible="icon" variant="inset">
    <UiSidebarHeader>
      <UiSidebarMenu>
        <UiSidebarMenuItem>
          <UiSidebarMenuButton size="lg" as-child>
            <Link :href="dashboardUrl(teamSlug)">
              <AppLogo />
            </Link>
          </UiSidebarMenuButton>
        </UiSidebarMenuItem>
      </UiSidebarMenu>
      <UiSidebarMenu>
        <UiSidebarMenuItem>
          <TeamSwitcher />
        </UiSidebarMenuItem>
      </UiSidebarMenu>
    </UiSidebarHeader>

    <UiSidebarContent>
      <NavMain :items="mainNavItems(teamSlug)" />
    </UiSidebarContent>

    <UiSidebarFooter>
      <NavFooter :items="extraNavItems" />
      <NavUser />
    </UiSidebarFooter>
  </UiSidebar>
</template>
