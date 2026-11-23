<script setup lang="ts">
import { destroy as destroyMember } from '@/routes/teams/members'

const props = defineProps<{
  team: App.Data.TeamData
  member: App.Data.TeamMemberData | null
  open: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const processing = ref(false)

const removeMember = () => {
  if (!props.member) {
    return
  }

  router.visit(destroyMember([props.team.slug, props.member.id]), {
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
        <UiDialogTitle>Remove team member</UiDialogTitle>
        <UiDialogDescription>
          Are you sure you want to remove
          <strong>{{ props.member?.name }}</strong> from this team?
        </UiDialogDescription>
      </UiDialogHeader>

      <UiDialogFooter class="gap-2">
        <UiDialogClose as-child>
          <UiButton variant="secondary">Cancel</UiButton>
        </UiDialogClose>

        <UiButton
          data-test="remove-member-confirm"
          variant="destructive"
          :disabled="processing"
          @click="removeMember"
        >
          Remove member
        </UiButton>
      </UiDialogFooter>
    </UiDialogContent>
  </UiDialog>
</template>
