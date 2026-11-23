<!--
 - @copyright Copyright (c) 2022-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <div class="page-container" :class="{ 'icon-loading': loading, loading }">
    <h2>
      {{ t(appId, 'Project-Participation of {publicName}', { publicName: memberData.personalPublicName }) }}
    </h2>
    <ul class="project-list">
      <NcListItem v-for="participant in memberData.projectParticipation"
                  :key="participant.project.id"
                  :name="participant.project.name"
                  :bold="true"
                  :forceDisplayActions="true"
      >
        <template #actions>
          <NcActionButton @click="requestProjectDetails(participant)">
            <template #icon>
              <InfoIcon />
            </template>
            {{ t(appId, 'details') }}
          </NcActionButton>
        </template>
      </NcListItem>
    </ul>
    <DebugInfo :debugData="memberData" />
  </div>
</template>

<script setup lang="ts">
import type { ViewDetailsEventData } from '../App.vue'
import type { ProjectParticipant } from '../stores/memberData.ts'

import axios from '@nextcloud/axios'
import { showError, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
  NcActionButton,
  NcListItem,
} from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import {
  onBeforeMount,
  ref,
} from 'vue'
import InfoIcon from 'vue-material-design-icons/InformationVariant.vue'
import DebugInfo from '../components/DebugInfo.vue'
import { appName as appId } from '../config.ts'
import { useAppDataStore } from '../stores/appData.ts'
import { useMemberDataStore } from '../stores/memberData.ts'
import { isAxiosErrorResponse } from '../toolkit/types/axios-type-guards.ts'
import generateAppUrl from '../toolkit/util/generate-url.ts'

const viewName = 'Projects'

// eslint-disable-next-line vue/define-macros-order
const emit = defineEmits<{
  viewDetails: [data: ViewDetailsEventData<typeof viewName>]
}>()

const loading = ref(false)
const { memberRootFolder } = storeToRefs(useAppDataStore())

const memberData = useMemberDataStore()

const requestProjectDetails = (participant: ProjectParticipant) => {
  emit('viewDetails', {
    viewName,
    title: participant.project.name,
    props: {
      participant,
      memberRootFolder: memberRootFolder.value,
    },
  })
}

onBeforeMount(async () => {
  await memberData.initialize()

  if (memberRootFolder.value === '') {
    try {
      const response = await axios.get(generateAppUrl('settings/app/memberRootFolder'), {})
      memberRootFolder.value = response.data.value
    } catch (e) {
      console.error('ERROR', e)
      let message = t(appId, 'reason unknown')
      if (isAxiosErrorResponse(e) && e.response.data) {
        const data = e.response.data as { messages?: string[] }
        if (Array.isArray(data.messages)) {
          message = data.messages.join(' ')
        }
      }
      // Ignore for the time being
      if (this === false) {
        showError(t(appId, 'Could not fetch root-folder of member file space: {message}', { message }), { timeout: TOAST_PERMANENT_TIMEOUT })
      }
    }
  }
  loading.value = false
})
</script>

<style lang="scss" scoped>
.page-container {
  padding-left:50px;
  padding-top:12px;
  min-height:100%;
  &.loading {
    width:100%;
    * {
      display:none;
    }
  }
}
// .project-list {
//   min-width:32rem;
// }
</style>
