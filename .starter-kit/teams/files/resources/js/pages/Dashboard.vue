<script setup lang="ts">
import { dashboardUrl } from '@/lib/navigation'
import type { BreadcrumbItem } from '@/types'

defineProps<{
  pendingInvitations: App.Data.PendingTeamInvitationData[]
}>()

const page = usePage()
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  {
    title: 'Dashboard',
    href: dashboardUrl(page.props.currentTeam?.slug),
  },
])
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Dashboard" />

    <PendingInvitationsModal
      v-if="pendingInvitations.length > 0"
      :invitations="pendingInvitations"
    />
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
      </div>
      <div
        class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
      >
        <PlaceholderPattern />
      </div>
    </div>
  </AppLayout>
</template>
