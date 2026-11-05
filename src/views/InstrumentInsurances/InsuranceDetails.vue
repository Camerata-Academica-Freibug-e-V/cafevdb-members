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
  <ul class="insurance-details">
    <NcListItem :name="t(appId, 'manufacturer')" :details="insurance.manufacturer" />
    <NcListItem :name="t(appId, 'manufacturered')" :details="insurance.yearOfConstruction" />
    <NcListItem :name="t(appId, 'insurance broker')" :details="insurance.insuranceRate.broker.shortName" />
    <NcListItem :name="t(appId, 'insurance start')" :details="formatDate(insurance.startOfInsurance)" />
    <NcListItem :name="t(appId, 'geographical scope')" :details="t(appId, insurance.insuranceRate.geographicalScope)" />
    <NcListItem :name="t(appId, 'insurance rate')" :details="insurance.insuranceRate.rate * 100.0 + '%'" />
    <NcListItem :name="t(appId, 'value')" :details="insurance.insuranceAmount + ' ' + currencySymbol" />
    <NcListItem :name="t(appId, 'insurance fee')" :details="(insurance.insuranceAmount * insurance.insuranceRate.rate * (1. + taxRate)).toFixed(2) + ' ' + currencySymbol" />
    <NcListItem :name="t(appId, 'due date')" :details="formatDate(insurance.insuranceRate.dueDate, 'omit-year')" />
    <NcListItem v-if="includeRole" :name="t(appId, 'my role')" :details="roles" />
  </ul>
</template>

<script setup lang="ts">
import type { InstrumentInsurance } from '../../stores/memberData.ts'

import { translate as t } from '@nextcloud/l10n'
import { NcListItem } from '@nextcloud/vue'
import {
  computed,
} from 'vue'
import { appName as appId } from '../../config.ts'
import formatDate from '../../util/formatDate.ts'

const props = withDefaults(defineProps<{
  insurance: InstrumentInsurance
  taxRate: number
  currencySymbol: string
  includeRole?: boolean
}>(), {
  // eslint-disable-next-line vue/no-boolean-default
  includeRole: true,
})

// @todo remove boiler-plate when upgrading to vue3.5+
const insurance = computed(() => props.insurance)
const taxRate = computed(() => props.taxRate)
const currencySymbol = computed(() => props.currencySymbol)
const includeRole = computed(() => props.includeRole)

const roles = computed(() => {
  const roles: string[] = []
  if (props.insurance.isDebitor) {
    roles.push(t(appId, 'debitor'))
  }
  if (props.insurance.isHolder !== props.insurance.isOwner) {
    if (props.insurance.isOwner) {
      roles.push(t(appId, 'owner'))
    }
    if (props.insurance.isHolder) {
      roles.push(t(appId, 'holder'))
    }
  } else {
    if (props.insurance.isOwner) {
      roles.push(t(appId, 'owner'))
    }
  }
  return roles.join('; ')
})
</script>
