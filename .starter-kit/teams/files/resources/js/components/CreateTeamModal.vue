<script setup lang="ts">
import { store } from '@/routes/teams'

const open = ref(false)
const formKey = ref(0)

function handleOpenChange(value: boolean) {
  open.value = value

  if (!value) {
    formKey.value++
  }
}
</script>

<template>
  <UiDialog :open="open" @update:open="handleOpenChange">
    <UiDialogTrigger as-child>
      <slot />
    </UiDialogTrigger>
    <UiDialogContent>
      <Form
        :key="formKey"
        v-bind="store.form()"
        class="space-y-6"
        v-slot="{ errors, processing }"
        @success="open = false"
      >
        <UiDialogHeader>
          <UiDialogTitle>Create a new team</UiDialogTitle>
          <UiDialogDescription>
            Create a new team to collaborate with others.
          </UiDialogDescription>
        </UiDialogHeader>

        <div class="grid gap-2">
          <UiLabel for="create-team-name">Team name</UiLabel>
          <UiInput
            id="create-team-name"
            name="name"
            data-test="create-team-name"
            placeholder="My team"
            required
          />
          <InputError :message="errors.name" />
        </div>

        <UiDialogFooter class="gap-2">
          <UiDialogClose as-child>
            <UiButton variant="secondary">Cancel</UiButton>
          </UiDialogClose>

          <UiButton
            type="submit"
            data-test="create-team-submit"
            :disabled="processing"
          >
            Create team
          </UiButton>
        </UiDialogFooter>
      </Form>
    </UiDialogContent>
  </UiDialog>
</template>
