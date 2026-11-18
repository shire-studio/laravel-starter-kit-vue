<script setup lang="ts">
import { accept, decline } from '@/routes/invitations'

defineProps<{
  invitations: App.Data.PendingTeamInvitationData[]
}>()

const page = usePage()

const open = ref(true)
const processingCode = ref<string | null>(null)

const acceptInvitation = (invitation: App.Data.PendingTeamInvitationData) => {
  router.visit(accept(invitation.code), {
    onStart: () => (processingCode.value = invitation.code),
    onFinish: () => (processingCode.value = null),
  })
}

const declineInvitation = (invitation: App.Data.PendingTeamInvitationData) => {
  router.visit(decline(invitation.code), {
    onStart: () => (processingCode.value = invitation.code),
    onFinish: () => (processingCode.value = null),
  })
}
</script>

<template>
  <UiDialog v-model:open="open">
    <UiDialogContent>
      <UiDialogHeader>
        <UiDialogTitle>Pending team invitations</UiDialogTitle>
        <UiDialogDescription>
          Accept or decline the teams you have been invited to join.
        </UiDialogDescription>
      </UiDialogHeader>

      <div class="grid gap-4">
        <div
          v-for="invitation in invitations"
          :key="invitation.code"
          data-test="pending-invitation"
          class="rounded-lg border p-4"
        >
          <div class="space-y-1">
            <p class="font-medium">{{ invitation.team_name }}</p>
            <p class="text-sm text-muted-foreground">
              {{ invitation.inviter_name }} invited you to join this team.
            </p>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <UiButton
              data-test="decline-invitation"
              variant="secondary"
              :disabled="processingCode === invitation.code"
              @click="declineInvitation(invitation)"
            >
              Decline
            </UiButton>

            <UiButton
              data-test="accept-invitation"
              :disabled="processingCode === invitation.code"
              @click="acceptInvitation(invitation)"
            >
              Accept
            </UiButton>
          </div>
        </div>

        <InputError :message="page.props.errors.invitation" />
      </div>
    </UiDialogContent>
  </UiDialog>
</template>
