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
  <div class="page-container" :class="{ 'icon-loading': loading, loading }">
    <h2>{{ t(appId, 'Personal Profile of {publicName}', { publicName: memberData.personalPublicName ?? '' }) }}</h2>
    <div class="input-row">
      <InputText v-model="memberData.firstName"
                 :label="t(appId, 'First Name')"
                 :placeholder="t(appId, 'e.g. Jonathan')"
                 :readonly="readonly"
      />
      <InputText v-model="memberData.surName"
                 :label="t(appId, 'Sur Name')"
                 :placeholder="t(appId, 'e.g. Smith')"
                 :readonly="readonly"
      />
    </div>
    <div v-show="memberData.nickName" class="input-row">
      <InputText v-model="memberData.nickName"
                 :label="t(appId, 'Nick Name')"
                 :placeholder="t(appId, 'e.g. Jonny')"
                 :readonly="readonly"
      />
    </div>
    <div v-show="memberData.addressSupplement" class="input-row">
      <InputText v-model="memberData.addressSupplement"
                 :label="t(appId, 'Address Supplement')"
                 :placeholder="t(appId, 'e.g. c/o Doe')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model="memberData.street"
                 :label="t(appId, 'Street')"
                 :placeholder="t(appId, 'e.g. Underhill')"
                 :readonly="readonly"
      />
      <InputText v-model="memberData.streetNumber"
                 type="number"
                 :label="t(appId, 'Number')"
                 :placeholder="t(appId, 'e.g. 13')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model="memberData.postalCode"
                 type="text"
                 :label="t(appId, 'Postal Code')"
                 :placeholder="t(appId, 'e.g. 4711')"
                 :readonly="readonly"
      />
      <InputText v-model="memberData.city"
                 :label="t(appId, 'City')"
                 :placeholder="t(appId, 'e.g. Bagend')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model="memberData.country"
                 class="country"
                 :label="t(appId, 'Country')"
                 :placeholder="t(appId, 'e.g. The Shire')"
                 :readonly="readonly"
      />
      <InputText v-model="memberData.birthday"
                 type="date"
                 class="birthday"
                 :label="t(appId, 'Birthday')"
                 :placeholder="t(appId, 'e.g. 01.01.1970')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model="memberData.email"
                 :label="t(appId, 'Email')"
                 :placeholder="t(appId, 'e.g. me@you.tld')"
                 :readonly="readonly"
                 icon="email"
      />
    </div>
    <div v-if="memberData.emailAddresses.length > 1"
         class="input-row"
    >
      <InputText v-model="memberData.emailAddresses"
                 type="multiselect"
                 :label="t(appId, 'All Email Addresses')"
                 :options="memberData.emailAddresses"
                 trackBy="address"
                 optionLabel="address"
                 :readonly="readonly"
                 :multiple="true"
      />
    </div>
    <div class="input-row">
      <InputText v-model="memberData.mobilePhone"
                 :label="t(appId, 'Mobile Phone')"
                 :placeholder="t(appId, 'e.g. +12 34 5678 901234')"
                 :readonly="readonly"
      />
      <InputText v-model="memberData.fixedLinePhone"
                 :label="t(appId, 'Fixed Line Phone')"
                 :placeholder="t(appId, 'e.g. +12 34 5678 901234')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model="memberData.instruments"
                 type="multiselect"
                 :label="t(appId, 'Instruments')"
                 :options="instruments"
                 trackBy="id"
                 optionLabel="name"
                 :readonly="readonly"
                 :multiple="true"
      />
    </div>
    <DebugInfo :debugData="memberData" />
  </div>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { storeToRefs } from 'pinia'
import {
  computed,
  onBeforeMount,
  ref,
} from 'vue'
import DebugInfo from '../components/DebugInfo.vue'
import InputText from '../components/InputText.vue'
import { appName as appId } from '../config.ts'
import { useAppDataStore } from '../stores/appData.ts'
import { useMemberDataStore } from '../stores/memberData.ts'

const viewName = 'PersonalProfile'

const loading = ref(true)
const readonly = computed(() => true) // change to ref if changing personal data has been implemented

const memberData = useMemberDataStore()
const {
  instruments,
} = storeToRefs(useAppDataStore())

onBeforeMount(async () => {
  await memberData.initialize()
  memberData.initialized[viewName] = true
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

.input-row {
  display:flex;
  flex-wrap:wrap;
  > * {
    flex: 1 0 40%;
    min-width:20em;
    &.input-type-number {
      flex: 1 0 5%;
      min-width:5em;
    }
    &.input-type-date {
      flex: 0 0 234px;
      width:234px;
      min-width:210px;
    }
  }
  :deep(.input-effect) {
    margin-bottom:0;
  }
}
</style>
