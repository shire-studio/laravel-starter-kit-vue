<script setup lang="ts">
import { store as storeInvitation } from '@/routes/teams/invitations'

const props = defineProps<{
  team: App.Data.TeamData
  availableRoles: App.Data.TeamRoleData[]
  open: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const inviteRole = ref<App.Enums.TeamRole>('member')
const formKey = ref(0)

function handleOpenChange(value: boolean) {
  emit('update:open', value)

  if (!value) {
    inviteRole.value = 'member'
    formKey.value++
  }
}
</script>

<template>
  <UiDialog :open="props.open" @update:open="handleOpenChange">
    <UiDialogContent>
      <Form
        :key="formKey"
        v-bind="storeInvitation.form(props.team.slug)"
        class="space-y-6"
        v-slot="{ errors, processing }"
        @success="emit('update:open', false)"
      >
        <UiDialogHeader>
          <UiDialogTitle>Invite a team member</UiDialogTitle>
          <UiDialogDescription>
            Send an invitation to join this team.
          </UiDialogDescription>
        </UiDialogHeader>

        <div class="grid gap-4">
          <div class="grid gap-2">
            <UiLabel for="invite-email">Email address</UiLabel>
            <UiInput
              id="invite-email"
              name="email"
              data-test="invite-email"
              type="email"
              placeholder="colleague@example.com"
              required
            />
            <InputError :message="errors.email" />
          </div>

          <div class="grid gap-2">
            <UiLabel for="invite-role">Role</UiLabel>
            <UiSelect v-model="inviteRole" name="role">
              <UiSelectTrigger
                id="invite-role"
                class="w-full"
                data-test="invite-role"
              >
                <UiSelectValue placeholder="Select a role" />
              </UiSelectTrigger>
              <UiSelectContent>
                <UiSelectItem
                  v-for="role in props.availableRoles"
                  :key="role.value"
                  :value="role.value"
                >
                  {{ role.label }}
                </UiSelectItem>
              </UiSelectContent>
            </UiSelect>
            <InputError :message="errors.role" />
          </div>
        </div>

        <UiDialogFooter class="gap-2">
          <UiDialogClose as-child>
            <UiButton variant="secondary">Cancel</UiButton>
          </UiDialogClose>

          <UiButton
            type="submit"
            data-test="invite-submit"
            :disabled="processing"
          >
            Send invitation
          </UiButton>
        </UiDialogFooter>
      </Form>
    </UiDialogContent>
  </UiDialog>
</template>
