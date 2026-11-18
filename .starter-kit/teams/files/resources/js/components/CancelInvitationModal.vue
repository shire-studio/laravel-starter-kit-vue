<script setup lang="ts">
import { destroy as destroyInvitation } from '@/routes/teams/invitations'

const props = defineProps<{
  team: App.Data.TeamData
  invitation: App.Data.TeamInvitationData | null
  open: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const processing = ref(false)

const cancelInvitation = () => {
  if (!props.invitation) {
    return
  }

  router.visit(destroyInvitation([props.team.slug, props.invitation.code]), {
    preserveScroll: true,
    onStart: () => (processing.value = true),
    onFinish: () => (processing.value = false),
    onSuccess: () => emit('update:open', false),
  })
}
</script>

<template>
  <UiDialog :open="props.open" @update:open="emit('update:open', $event)">
    <UiDialogContent>
      <UiDialogHeader>
        <UiDialogTitle>Cancel invitation</UiDialogTitle>
        <UiDialogDescription>
          Are you sure you want to cancel the invitation for
          <strong>{{ props.invitation?.email }}</strong
          >?
        </UiDialogDescription>
      </UiDialogHeader>

      <UiDialogFooter class="gap-2">
        <UiDialogClose as-child>
          <UiButton variant="secondary">Keep invitation</UiButton>
        </UiDialogClose>

        <UiButton
          data-test="cancel-invitation-confirm"
          variant="destructive"
          :disabled="processing"
          @click="cancelInvitation"
        >
          Cancel invitation
        </UiButton>
      </UiDialogFooter>
    </UiDialogContent>
  </UiDialog>
</template>
