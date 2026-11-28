<!--
 * @copyright Copyright (c) 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 -
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 -
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->
<template>
  <NcEmptyContent class="emp-content">
    {{ t(appId, '{orchestraName} Orchestra Member Portal', { orchestraName }) }}
    <template #icon>
      <DynamicSvgIcon :size="64" :data="appIcon" :title="orchestraName + ' members logo'" />
    </template>
    <template #description>
      <div v-if="memberDataError" class="error-section">
        <p class="error-info">
          {{ t(appId, 'Your personal data could not be accessed: {errorMessage}', { errorMessage: memberDataError }) }}
        </p>
        <p>
          {{ t(appId, 'Please use the button below to request access. If the problem persists, contact the orchestra administrators.') }}
        </p>
        <p v-if="requestPending" role="status">
          {{ t(appId, 'An access request is already pending.') }}
        </p>
        <p v-else-if="memberData.initialized.recryptRequestError" class="error-info">
          {{ t(appId, 'Unable to check whether an access request is pending: {errorMessage}', { errorMessage: memberData.initialized.recryptRequestError }) }}
        </p>
        <button class="button primary" :disabled="submittingRequest" @click="putRecryptionRequest">
          {{ submittingRequest
            ? t(appId, 'Submitting access request')
            : requestPending
              ? t(appId, 'Resubmit access request')
              : t(appId, 'Request access to my personal data') }}
        </button>
        <p class="hint">
          {{ t(appId, 'The authorization request has to be processed by a human being, this means that it will need some time before you are granted access to your data. You will be notified by the cloud-software when the request has been processed.') }}
        </p>
      </div>
    </template>
  </NcEmptyContent>
</template>

<script setup lang="ts">
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { showError, showInfo, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { NcEmptyContent } from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import DynamicSvgIcon from '@rotdrop/nextcloud-vue-components/lib/components/DynamicSvgIcon.vue'
import appIcon from '../../img/cafevdbmembers.svg'
import { appName as appId } from '../config.ts'
import { accessErrorMessage } from '../services/access-error.ts'
import { useAppDataStore } from '../stores/appData.ts'
import { useMemberDataStore } from '../stores/memberData.ts'

const appData = useAppDataStore()
const memberData = useMemberDataStore()

const {
  orchestraName,
} = storeToRefs(appData)

const memberDataError = computed(() => memberData.initialized.error)
const requestSubmitted = ref(false)
const submittingRequest = ref(false)
const requestPending = computed(() => requestSubmitted.value || memberData.initialized.recryptRequest !== null)

const putRecryptionRequest = async () => {
  if (submittingRequest.value) {
    return
  }
  const cloudUser = getCurrentUser()
  if (!cloudUser) {
    showError(t(appId, 'Unable to determine the identity of the current user.'))
    return
  }
  const userId = cloudUser.uid
  submittingRequest.value = true
  try {
    const url = generateOcsUrl('apps/cafevdb/api/v1/maintenance/encryption/recrypt/{userId}', { userId })
    await axios.put(url + '?format=json')
    requestSubmitted.value = true
    memberData.initialized.recryptRequestError = null
    showInfo(t(appId, 'The authorization request for {userId} has been submitted successfully', { userId }))
  } catch (e) {
    console.info('ERROR', e)
    showError(
      t(appId, 'Unable to submit the access request: {message}', { message: accessErrorMessage(e) }),
      { timeout: TOAST_PERMANENT_TIMEOUT },
    )
  } finally {
    submittingRequest.value = false
  }
}
</script>
