<script setup lang="ts">
import { ChevronDown, Mail, UserPlus, X } from '@lucide/vue'
import { edit, index, update } from '@/routes/teams'
import { update as updateMember } from '@/routes/teams/members'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
  team: App.Data.TeamData
  members: App.Data.TeamMemberData[]
  invitations: App.Data.TeamInvitationData[]
  permissions: App.Data.TeamPermissionsData
  availableRoles: App.Data.TeamRoleData[]
}>()

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
  {
    title: 'Teams',
    href: index(),
  },
  {
    title: props.team.name,
    href: edit(props.team.slug),
  },
])

const page = usePage()
const resolveInitials = getInitials

const inviteDialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const removeMemberDialogOpen = ref(false)
const memberToRemove = ref<App.Data.TeamMemberData | null>(null)
const cancelInvitationDialogOpen = ref(false)
const invitationToCancel = ref<App.Data.TeamInvitationData | null>(null)

const pageTitle = computed(() =>
  props.permissions.can_update_team
    ? `Edit ${props.team.name}`
    : `View ${props.team.name}`,
)

const updateMemberRole = (
  member: App.Data.TeamMemberData,
  newRole: App.Enums.TeamRole,
) => {
  router.visit(updateMember([props.team.slug, member.id]), {
    data: { role: newRole },
    preserveScroll: true,
  })
}

const confirmRemoveMember = (member: App.Data.TeamMemberData) => {
  memberToRemove.value = member
  removeMemberDialogOpen.value = true
}

const confirmCancelInvitation = (invitation: App.Data.TeamInvitationData) => {
  invitationToCancel.value = invitation
  cancelInvitationDialogOpen.value = true
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head :title="pageTitle" />

    <h1 class="sr-only">{{ pageTitle }}</h1>

    <SettingsLayout>
      <div class="flex flex-col space-y-10">
        <div v-if="permissions.can_update_team" class="space-y-6">
          <Heading
            variant="small"
            title="Team settings"
            description="Update your team name and settings"
          />

          <Form
            :key="team.id"
            v-bind="update.form(team.slug)"
            class="space-y-6"
            :options="{ preserveScroll: true }"
            v-slot="{ errors, processing }"
          >
            <div class="grid gap-2">
              <UiLabel for="name">Team name</UiLabel>
              <UiInput
                id="name"
                name="name"
                data-test="team-name"
                :default-value="team.name"
                required
              />
              <InputError :message="errors.name" />
            </div>

            <div class="flex items-center gap-4">
              <UiButton
                type="submit"
                data-test="team-save"
                :disabled="processing"
              >
                Save
              </UiButton>
            </div>
          </Form>
        </div>

        <div v-else class="space-y-6">
          <Heading variant="small" :title="team.name" />
        </div>

        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <Heading
              variant="small"
              title="Team members"
              :description="
                permissions.can_create_invitation
                  ? 'Manage who belongs to this team'
                  : ''
              "
            />

            <UiButton
              v-if="permissions.can_create_invitation"
              data-test="invite-member-button"
              @click="inviteDialogOpen = true"
            >
              <UserPlus /> Invite member
            </UiButton>
          </div>

          <div class="space-y-3">
            <div
              v-for="member in members"
              :key="member.id"
              data-test="member-row"
              class="flex items-center justify-between rounded-lg border p-4"
            >
              <div class="flex items-center gap-4">
                <UiAvatar class="h-10 w-10">
                  <UiAvatarFallback>{{
                    resolveInitials(member.name)
                  }}</UiAvatarFallback>
                </UiAvatar>
                <div>
                  <div class="font-medium">
                    {{ member.name }}
                  </div>
                  <div class="text-sm text-muted-foreground">
                    {{ member.email }}
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <UiDropdownMenu v-if="member.can_update">
                  <UiDropdownMenuTrigger as-child>
                    <UiButton
                      data-test="member-role"
                      variant="outline"
                      size="sm"
                    >
                      {{ member.role_label }}
                      <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                    </UiButton>
                  </UiDropdownMenuTrigger>
                  <UiDropdownMenuContent>
                    <UiDropdownMenuItem
                      v-for="role in availableRoles"
                      :key="role.value"
                      data-test="member-role-option"
                      @click="updateMemberRole(member, role.value)"
                    >
                      {{ role.label }}
                    </UiDropdownMenuItem>
                  </UiDropdownMenuContent>
                </UiDropdownMenu>
                <UiBadge v-else variant="secondary">
                  {{ member.role_label }}
                </UiBadge>

                <UiTooltipProvider v-if="member.can_remove">
                  <UiTooltip>
                    <UiTooltipTrigger as-child>
                      <UiButton
                        data-test="remove-member"
                        variant="ghost"
                        size="sm"
                        @click="confirmRemoveMember(member)"
                      >
                        <X class="h-4 w-4" />
                        <span class="sr-only">Remove member</span>
                      </UiButton>
                    </UiTooltipTrigger>
                    <UiTooltipContent>
                      <p>Remove member</p>
                    </UiTooltipContent>
                  </UiTooltip>
                </UiTooltipProvider>
              </div>
            </div>
          </div>

          <InputError :message="page.props.errors.role" />
        </div>

        <div v-if="invitations.length > 0" class="space-y-6">
          <Heading
            variant="small"
            title="Pending invitations"
            description="Invitations that haven't been accepted yet"
          />

          <div class="space-y-3">
            <div
              v-for="invitation in invitations"
              :key="invitation.code"
              data-test="invitation-row"
              class="flex items-center justify-between rounded-lg border p-4"
            >
              <div class="flex items-center gap-4">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-muted"
                >
                  <Mail class="h-5 w-5 text-muted-foreground" />
                </div>
                <div>
                  <div class="font-medium">
                    {{ invitation.email }}
                  </div>
                  <div class="text-sm text-muted-foreground">
                    {{ invitation.role_label }}
                  </div>
                </div>
              </div>

              <UiTooltipProvider v-if="permissions.can_cancel_invitation">
                <UiTooltip>
                  <UiTooltipTrigger as-child>
                    <UiButton
                      data-test="cancel-invitation"
                      variant="ghost"
                      size="sm"
                      @click="confirmCancelInvitation(invitation)"
                    >
                      <X class="h-4 w-4" />
                      <span class="sr-only">Cancel invitation</span>
                    </UiButton>
                  </UiTooltipTrigger>
                  <UiTooltipContent>
                    <p>Cancel invitation</p>
                  </UiTooltipContent>
                </UiTooltip>
              </UiTooltipProvider>
            </div>
          </div>
        </div>

        <div
          v-if="permissions.can_delete_team && !team.is_personal"
          class="space-y-6"
        >
          <Heading
            variant="small"
            title="Delete team"
            description="Permanently delete your team"
          />
          <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
          >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
              <p class="font-medium">Warning</p>
              <p class="text-sm">
                Please proceed with caution, this cannot be undone.
              </p>
            </div>
            <UiButton
              data-test="delete-team"
              variant="destructive"
              @click="deleteDialogOpen = true"
              >Delete team</UiButton
            >
          </div>
        </div>
      </div>

      <InviteMemberModal
        v-if="permissions.can_create_invitation"
        :team="team"
        :available-roles="availableRoles"
        :open="inviteDialogOpen"
        @update:open="inviteDialogOpen = $event"
      />

      <RemoveMemberModal
        :team="team"
        :member="memberToRemove"
        :open="removeMemberDialogOpen"
        @update:open="removeMemberDialogOpen = $event"
      />

      <CancelInvitationModal
        :team="team"
        :invitation="invitationToCancel"
        :open="cancelInvitationDialogOpen"
        @update:open="cancelInvitationDialogOpen = $event"
      />

      <DeleteTeamModal
        v-if="permissions.can_delete_team && !team.is_personal"
        :team="team"
        :open="deleteDialogOpen"
        @update:open="deleteDialogOpen = $event"
      />
    </SettingsLayout>
  </AppLayout>
</template>
