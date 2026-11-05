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
  <NcSettingsSection :name="t(appName, 'CAFeVDB Database Connector, Personal Settings')">
    <TextField id="test-input"
               v-model="settings.inputTest"
               :label="t(appName, 'Test Input')"
               :hint="t(appName, 'Test Hint')"
               @submit="saveTextInput('inputTest')"
    />
  </NcSettingsSection>
</template>

<script setup lang="ts">
import {
  NcSettingsSection,
} from '@nextcloud/vue'
import {
  reactive,
} from 'vue'
import TextField from '@rotdrop/nextcloud-vue-components/lib/components/TextFieldWithSubmitButton.vue'
import { appName } from './config.ts'
import {
  fetchSettings,
  saveConfirmedSetting,
} from './toolkit/util/settings-sync.ts'

const settings = reactive({
  inputTest: '',
})

const getData = async () => {
  return fetchSettings({ section: 'personal', settings })
}
getData()

const saveTextInput = async (settingsKey: string, value?: string, force?: boolean) => {
  if (value === undefined) {
    value = settings[settingsKey] || ''
  }
  return saveConfirmedSetting({ value, section: 'personal', settingsKey, force, settings })
}
</script>

<style lang="scss" scoped>
.settings-section {
  :deep(&__name) {
    padding-left:60px;
    background-image:url('../img/cafevdbmembers.svg');
    background-repeat:no-repeat;
    background-origin:border-box;
    background-size:45px;
    background-position:left center;
    height:30px;
  }
}
</style>
