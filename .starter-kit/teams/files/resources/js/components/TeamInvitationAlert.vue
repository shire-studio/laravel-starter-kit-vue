<script setup lang="ts">
import { Info } from '@lucide/vue'

defineProps<{
  invitation: App.Data.TeamInvitationContextData
  action: 'Log in' | 'Register'
}>()
</script>

<template>
  <div data-test="team-invitation-alert">
    <UiAlert
      class="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-100 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400"
    >
      <Info class="size-4" />
      <UiAlertDescription class="text-blue-900 dark:text-blue-100">
        {{ action }} to join the "{{ invitation.team_name }}" team.
      </UiAlertDescription>
    </UiAlert>
  </div>
</template>
