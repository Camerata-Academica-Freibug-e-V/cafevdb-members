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
 -->
<template>
  <div class="templateroot">
    <h1 class="title">
      {{ t(appName, 'CAFeVDB Database Connector, Admin Settings') }}
    </h1>
    <NcSettingsSection :name="t(appName, 'Settings for Registered Members')">
      <TextField v-model="settings.memberRootFolder"
                 :label="t(appName, 'Member-Data Root-Folder')"
                 :hint="t(appName, 'Specify the root folder below which all member-data will be mounted.')"
                 @submit="saveTextInput('memberRootFolder')"
      />
      <div v-if="showSyncProgress">
        <div class="sync-status">
          <span class="sync-text">{{ syncText }}</span>
          <button v-if="syncFinished"
                  class="button primary sync-clear"
                  :title="t(appName, 'Remove the status feedback from the last sync.')"
                  @click="hideProgressFeedback()"
          >
            {{ t(appName, 'Ok') }}
          </button>
          <span class="flex-spacer" />
          <span class="sync-counter">{{ syncCounter }}</span>
        </div>
        <NcProgressBar :value="syncPercentage"
                       :error="syncError"
                       size="medium"
        />
      </div>
      <button v-else
              type="button"
              class="button primary"
              :title="t(appName, 'Synchronize the hierarchy of shared folders below {root} with the projects of the {managementApp}-orchestra-management app.', { root: settings.memberRootFolder + '/', managementApp: orchestraAppName })"
              @click="synchronizeFolders()"
      >
        {{ t(appName, 'Synchronize Folder-Structure') }}
      </button>
      <TextField v-model="settings.cloudUserViewsDatabase"
                 :label="t(appName, 'Personalized Views Database')"
                 :hint="t(appName, 'The name of the data-base which holds the personalized single-row views which contain the data for the currently logged-on user.')"
                 @submit="saveTextInput('cloudUserViewsDatabase')"
      />
    </NcSettingsSection>
    <NcSettingsSection :name="t(appName, 'Project Registration Settings')">
      <TextField v-model="settings.registrationReplyTo"
                 :label="t(appName, 'Sender and ReplyTo for the registration notification emails.')"
                 :hint="t(appName, `The applicants are notified by email after they have submitted their project application,
they also receive password-reset emails if they want to review or change their submitted data at a later.
This is the sender and reply-to email address of these automatically generated emails.`)"
                 :placeholder="t(appName, 'orchestra+registration@my.domain.tld')"
                 @submit="saveTextInput('registrationReplyTo')"
      />
      <div>TODO</div>
      <div>{{ t(appName, 'Terms and Conditions') }}</div>
      <div>{{ t(appName, 'Privacy Statement') }}</div>
    </NcSettingsSection>
  </div>
</template>

<script setup lang="ts">
import type { InitialState } from 'cafevdbmembers'

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
  NcProgressBar,
  NcSettingsSection,
} from '@nextcloud/vue'
import {
  computed,
  reactive,
  ref,
} from 'vue'
import TextField from '@rotdrop/nextcloud-vue-components/lib/components/TextFieldWithSubmitButton.vue'
import { appName } from './config.ts'
import { isAxiosErrorResponse } from './toolkit/types/axios-type-guards.ts'
import getInitialState from './toolkit/util/initial-state.ts'
import {
  fetchSettings,
  saveConfirmedSetting,
} from './toolkit/util/settings-sync.ts'
import { showError, TOAST_PERMANENT_TIMEOUT } from './toolkit/util/toasts.ts'

interface CloudUserGroup {
  displayName: string
  gid: string
}

const initialState = getInitialState<InitialState>()

const settings = reactive({
  memberRootFolder: '',
  cloudUserViewsDatabase: '',
  memberFolderGroups: [] as CloudUserGroup[],
  registrationReplyTo: '',
})

const orchestraAppName = ref(initialState?.orchestraAppName ?? 'unknown')
const syncFailure = ref(false)
const syncTotals = ref(0)
const syncDone = ref(0)
const synchronizing = ref(false)
const syncLabel = ref('')
const syncCounter = ref('')

const showSyncProgress = computed(() => synchronizing.value)
const syncPercentage = computed(() => syncTotals.value > 0 ? syncDone.value * 100 / syncTotals.value : 0)
const syncError = computed(() => syncFailure.value)
const syncText = computed(() => syncLabel.value)
const syncFinished = computed(() => (syncDone.value > 0 && syncDone.value === syncTotals.value) || syncFailure.value)

const getData = async () => {
  return fetchSettings({ section: 'admin', settings })
}
getData()

const synchronizeFolders = async () => {
  synchronizing.value = true
  syncTotals.value = settings.memberFolderGroups.length
  syncDone.value = 0
  syncFailure.value = false
  let group: undefined|CloudUserGroup
  for (group of settings.memberFolderGroups) {
    console.info('GROUP', group)
    syncLabel.value = t(appName, 'Synchronizing for group {group}', { group: group.displayName })
    syncCounter.value = t(appName, '{current} of {totals}', { current: syncDone.value + 1, totals: syncTotals.value })
    try {
      await axios.post(generateUrl('apps/' + appName + '/settings/admin/synchronize'), { value: group.gid })
    } catch (e) {
      let message = t(appName, 'reason unknown')
      if (isAxiosErrorResponse(e) && e.response.data) {
        const responseData = e.response.data as { messages?: string[] }
        if (Array.isArray(responseData.messages)) {
          message = responseData.messages.join(' ')
        }
        console.info('RESPONSE', e.response)
      }
      showError(t(appName, 'Folder for "{group}" could not be created: {message}', { group: group.displayName, message }), { timeout: TOAST_PERMANENT_TIMEOUT })
      syncFailure.value = true
      break
    }
    ++syncDone.value
  }
  syncLabel.value = syncFailure.value
    ? t(appName, 'Failed at group "{group}" after {numFolders} have been processed successfully, {remainingFolders} are remaining.', {
      group: group?.displayName ?? '',
      numFolders: syncDone.value,
      remainingFolders: syncTotals.value - syncDone.value,
    })
    : t(appName, 'All done, folder structure for all {numFolders} folders is up to date.', { numFolders: syncTotals.value })
}

const hideProgressFeedback = () => {
  synchronizing.value = false
  syncFailure.value = false
}

const saveTextInput = async (settingsKey: string, value?: string, force?: boolean) => {
  if (value === undefined) {
    value = settings[settingsKey] || ''
  }
  return saveConfirmedSetting({ value, section: 'admin', settingsKey, force, settings })
}

</script>

<style lang="scss" scoped>
.templateroot {
  h1.title {
    margin: calc(var(--default-grid-baseline) * 7);
    margin-bottom: 0;
    font-size:revert;
    font-weight:revert;
    padding-left:60px;
    background-image:url('../img/cafevdbmembers.svg');
    background-repeat:no-repeat;
    background-origin:border-box;
    background-size:45px;
    background-position:left center;
    height:fit-content;
  }
  .sync-status {
    display:flex;
    flex-direction:row;
    align-items:center;
      width:100%;
    .flex-spacer {
      flex-grow:4;
      height:34px
    }
    button.sync-clear {
      margin-left:1ex;
    }
  }
}
</style>
