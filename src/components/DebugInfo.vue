<!--
 - @copyright Copyright (c) 2022, 2024-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <div v-if="debug" class="debug-container">
    <NcCheckboxRadioSwitch v-model="debug">
      {{ t(appId, 'Enable Debug') }}
    </NcCheckboxRadioSwitch>
    <div class="debug">
      <div>{{ t(appId, 'DEBUG: all data') }}</div>
      <pre>{{ stringify(debugData) }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import { appName as appId } from '../config.ts'
import { useAppDataStore } from '../stores/appData.ts'

withDefaults(defineProps<{
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  debugData?: Record<string, any>
}>(), {
  debugData: () => ({}),
})

const { debug } = storeToRefs(useAppDataStore())

const stringify = (data: typeof props.debugData) => {
  console.info('DATA', data)
  try {
    const getCircularReplacer = () => {
      const seen = new WeakSet()
      return (key: string, value: null|object|number|string) => {
        if (key.startsWith('$') || key.startsWith('_')) {
          return
        }
        if (typeof value === 'object' && value !== null) {
          if (seen.has(value)) {
            return
          }
          seen.add(value)
        }
        return value
      }
    }
    return JSON.stringify(data, getCircularReplacer(), 2)
  } catch (e) {
    console.error('ERROR', e)
    return ''
  }
}
</script>

<style lang="scss" scoped>
.debug-container {
  width:100%;
  max-width:32rem;
  overflow:visible;
}
</style>
