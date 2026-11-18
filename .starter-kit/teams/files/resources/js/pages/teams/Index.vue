<script setup lang="ts">
import { Eye, LogOut, Pencil, Plus } from '@lucide/vue'
import { edit, index } from '@/routes/teams'
import type { BreadcrumbItem } from '@/types'

defineProps<{
  teams: App.Data.TeamData[]
}>()

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'Teams',
    href: index(),
  },
]

const leaveTeamDialogOpen = ref(false)
const teamLeaving = ref<App.Data.TeamData | null>(null)

const openLeaveTeamDialog = (team: App.Data.TeamData) => {
  teamLeaving.value = team
  leaveTeamDialogOpen.value = true
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Teams" />

    <h1 class="sr-only">Teams</h1>

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
          <Heading
            variant="small"
            title="Teams"
            description="Manage your teams and team memberships"
          />

          <CreateTeamModal>
            <UiButton data-test="create-team-button">
              <Plus /> New team
            </UiButton>
          </CreateTeamModal>
        </div>

        <div class="space-y-3">
          <div
            v-for="team in teams"
            :key="team.id"
            class="flex items-center justify-between gap-4 rounded-lg border p-4"
          >
            <div class="flex items-center gap-4">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-medium">{{ team.name }}</span>
                  <UiBadge v-if="team.is_personal" variant="secondary">
                    Personal
                  </UiBadge>
                </div>
                <span class="text-sm text-muted-foreground">
                  {{ team.role_label }}
                </span>
              </div>
            </div>

            <UiTooltipProvider>
              <div class="flex items-center gap-2">
                <UiTooltip v-if="team.can_leave">
                  <UiTooltipTrigger as-child>
                    <UiButton
                      data-test="leave-team"
                      variant="ghost"
                      size="sm"
                      @click="openLeaveTeamDialog(team)"
                    >
                      <LogOut class="h-4 w-4" />
                      <span class="sr-only">Leave team</span>
                    </UiButton>
                  </UiTooltipTrigger>
                  <UiTooltipContent>
                    <p>Leave team</p>
                  </UiTooltipContent>
                </UiTooltip>

                <UiTooltip>
                  <UiTooltipTrigger as-child>
                    <UiButton variant="ghost" size="sm" as-child>
                      <Link :href="edit(team.slug)">
                        <Pencil v-if="team.can_update" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                        <span class="sr-only">
                          {{ team.can_update ? 'Edit team' : 'View team' }}
                        </span>
                      </Link>
                    </UiButton>
                  </UiTooltipTrigger>
                  <UiTooltipContent>
                    <p>
                      {{ team.can_update ? 'Edit team' : 'View team' }}
                    </p>
                  </UiTooltipContent>
                </UiTooltip>
              </div>
            </UiTooltipProvider>
          </div>

          <p
            v-if="teams.length === 0"
            class="py-8 text-center text-muted-foreground"
          >
            You don't belong to any teams yet.
          </p>
        </div>
      </div>

      <LeaveTeamModal
        :team="teamLeaving"
        :open="leaveTeamDialogOpen"
        @update:open="leaveTeamDialogOpen = $event"
      />
    </SettingsLayout>
  </AppLayout>
</template>
