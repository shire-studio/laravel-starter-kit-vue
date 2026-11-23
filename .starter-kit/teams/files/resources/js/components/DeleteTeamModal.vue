<script setup lang="ts">
import { destroy } from '@/routes/teams'

const props = defineProps<{
  team: App.Data.TeamData
  open: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const confirmationName = ref('')
const formKey = ref(0)

const canDeleteTeam = computed(() => confirmationName.value === props.team.name)

const handleOpenChange = (nextOpen: boolean) => {
  emit('update:open', nextOpen)

  if (!nextOpen) {
    confirmationName.value = ''
    formKey.value++
  }
}
</script>

<template>
  <UiDialog :open="props.open" @update:open="handleOpenChange">
    <UiDialogContent>
      <Form
        :key="formKey"
        v-bind="destroy.form(props.team.slug)"
        class="space-y-6"
        v-slot="{ errors, processing }"
        @success="handleOpenChange(false)"
      >
        <UiDialogHeader>
          <UiDialogTitle>Are you sure?</UiDialogTitle>
          <UiDialogDescription>
            This action cannot be undone. This will permanently delete the team
            <strong>"{{ props.team.name }}"</strong>.
          </UiDialogDescription>
        </UiDialogHeader>

        <div class="space-y-4 py-4">
          <div class="grid gap-2">
            <UiLabel for="confirmation-name">
              Type
              <strong>"{{ props.team.name }}"</strong> to confirm
            </UiLabel>
            <UiInput
              id="confirmation-name"
              name="name"
              data-test="delete-team-name"
              v-model="confirmationName"
              placeholder="Enter team name"
              autocomplete="off"
            />
            <InputError :message="errors.name" />
          </div>
        </div>

        <UiDialogFooter class="gap-2">
          <UiDialogClose as-child>
            <UiButton variant="secondary">Cancel</UiButton>
          </UiDialogClose>

          <UiButton
            data-test="delete-team-submit"
            variant="destructive"
            type="submit"
            :disabled="!canDeleteTeam || processing"
          >
            Delete team
          </UiButton>
        </UiDialogFooter>
      </Form>
    </UiDialogContent>
  </UiDialog>
</template>
