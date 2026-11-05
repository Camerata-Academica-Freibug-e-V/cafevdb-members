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
          {{ t(appId, 'Error') + ': ' + memberDataError }}
        </p>
        <button class="button primary" @click="putRecryptionRequest">
          {{ t(appId, 'Request Access to my personal Data') }}
        </button>
        <p class="hint">
          {{ t(appId, 'The authorization request has to be processed by a human being, this means that it will need some time before you are granted access to your data. You will be notified by the cloud-software when the request has been processed.') }}
        </p>
      </div>
    </template>
  </NcEmptyContent>
</template>

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { NcEmptyContent } from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import DynamicSvgIcon from '@rotdrop/nextcloud-vue-components/lib/components/DynamicSvgIcon.vue'
import appIcon from '../../img/cafevdbmembers.svg'
import { appName as appId } from '../config.ts'
import { useAppDataStore } from '../stores/appData.ts'
import { useMemberDataStore } from '../stores/memberData.ts'
import { isAxiosErrorResponse } from '../toolkit/types/axios-type-guards.ts'
import { showError, showInfo, TOAST_PERMANENT_TIMEOUT } from '../toolkit/util/toasts.ts'

const appData = useAppDataStore()
const memberData = useMemberDataStore()

const {
  orchestraName,
} = storeToRefs(appData)

const memberDataError = computed(() => memberData.initialized.error)

const putRecryptionRequest = async () => {
  const cloudUser = getCurrentUser()
  if (!cloudUser) {
    showError(t(appId, 'Unable to determine the identity of the current user.'))
    return
  }
  const userId = cloudUser.uid
  try {
    const url = generateOcsUrl('apps/cafevdb/api/v1/maintenance/encryption/recrypt/{userId}', { userId })
    await axios.put(url + '?format=json')
    showInfo(t(appId, 'The authorization request for {userId} has been submitted successfully', { userId }))
  } catch (e) {
    console.info('ERROR', e)
    let message = t(appId, 'reason unknown')
    if (isAxiosErrorResponse(e) && e.response.data) {
      const data = e.response.data as OCSResponse
      if (data.ocs && data.ocs.meta && data.ocs.meta.message) {
        message = data.ocs.meta.message
      }
    }
    showError(
      t(appId, 'Unable to handle access action: {message}', { message }),
      { timeout: TOAST_PERMANENT_TIMEOUT },
    )
  }
}
</script>
