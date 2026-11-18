<script setup lang="ts">
import { leave as leaveTeamAction } from '@/routes/teams'

const props = defineProps<{
  team: App.Data.TeamData | null
  open: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const processing = ref(false)

const leaveTeam = () => {
  if (!props.team) {
    return
  }

  router.visit(leaveTeamAction(props.team.slug), {
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
        <UiDialogTitle>Leave team</UiDialogTitle>
        <UiDialogDescription>
          Are you sure you want to leave
          <strong>{{ props.team?.name }}</strong
          >?
        </UiDialogDescription>
      </UiDialogHeader>

      <UiDialogFooter class="gap-2">
        <UiDialogClose as-child>
          <UiButton variant="secondary">Cancel</UiButton>
        </UiDialogClose>

        <UiButton
          data-test="leave-team-confirm"
          variant="destructive"
          :disabled="processing"
          @click="leaveTeam"
        >
          Leave team
        </UiButton>
      </UiDialogFooter>
    </UiDialogContent>
  </UiDialog>
</template>
