<!--
 - @copyright Copyright (c) 2023-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <div class="page-container personal-profile-view" :class="{ 'icon-loading': loading, loading }">
    <h2 v-if="!!registrationData.personalPublicName">
      {{ t(appName, 'Personal Profile of {publicName}', { publicName: registrationData.personalPublicName || '' }) }}
    </h2>
    <h2 v-else>
      {{ t(appName, 'Personal Profile') }}
    </h2>
    <div class="input-row">
      <InputText v-model:value="registrationData.firstName"
                 :label="t(appName, 'First Name')"
                 :placeholder="t(appName, 'e.g. Jonathan')"
                 :readonly="readonly"
                 :required="true"
                 @input="updatePublicName"
      />
      <InputText v-model:value="registrationData.surName"
                 :label="t(appName, 'Sur Name')"
                 :placeholder="t(appName, 'e.g. Smith')"
                 :readonly="readonly"
                 :required="true"
                 @input="updatePublicName"
      />
    </div>
    <div class="input-row">
      <InputText v-model:value="registrationData.nickName"
                 :label="t(appName, 'Nick Name (optional)')"
                 :placeholder="t(appName, 'e.g. Jonny')"
                 :readonly="readonly"
                 @input="updatePublicName"
      />
    </div>
    <div v-show="registrationData.addressSupplement" class="input-row">
      <InputText v-model:value="registrationData.addressSupplement"
                 :label="t(appName, 'Address Supplement')"
                 :placeholder="t(appName, 'e.g. c/o Doe')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model:value="registrationData.street"
                 :label="t(appName, 'Street')"
                 :placeholder="t(appName, 'e.g. Underhill')"
                 :readonly="readonly"
      />
      <InputText v-model:value="registrationData.streetNumber"
                 type="number"
                 :label="t(appName, 'Number')"
                 :placeholder="t(appName, 'e.g. 13')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model:value="registrationData.postalCode"
                 type="text"
                 :label="t(appName, 'Postal Code')"
                 :placeholder="t(appName, 'e.g. 4711')"
                 :readonly="readonly"
      />
      <InputText v-model:value="registrationData.city"
                 :label="t(appName, 'City')"
                 :placeholder="t(appName, 'e.g. Bagend')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model:value="registrationCountry"
                 type="multiselect"
                 class="country"
                 :label="t(appName, 'Country')"
                 :placeholder="t(appName, 'e.g. The Shire')"
                 :readonly="readonly"
                 :options="countries"
                 trackBy="code"
                 optionLabel="name"
                 :multiple="false"
      />
      <InputText v-model:value="registrationData.birthday"
                 type="date"
                 class="birthday"
                 :label="t(appName, 'Birthday')"
                 :placeholder="t(appName, 'e.g. 01.01.1970')"
                 :readonly="readonly"
                 :required="true"
      />
    </div>
    <div class="input-row">
      <InputText v-model:value="registrationData.email"
                 :label="t(appName, 'Email')"
                 :placeholder="t(appName, 'e.g. me@you.tld')"
                 :readonly="readonly"
                 :required="true"
                 icon="email"
      />
    </div>
    <div v-if="registrationData.emailAddresses.length > 1"
         class="input-row"
    >
      <InputText v-model:value="registrationData.emailAddresses"
                 type="multiselect"
                 :label="t(appName, 'All Email Addresses')"
                 :options="registrationData.emailAddresses"
                 trackBy="address"
                 optionLabel="address"
                 :readonly="readonly"
                 :multiple="true"
      />
    </div>
    <div class="input-row">
      <InputText v-model:value="registrationData.mobilePhone"
                 :label="t(appName, 'Mobile Phone')"
                 :placeholder="t(appName, 'e.g. +12 34 5678 901234')"
                 :readonly="readonly"
      />
      <InputText v-model:value="registrationData.fixedLinePhone"
                 :label="t(appName, 'Fixed Line Phone')"
                 :placeholder="t(appName, 'e.g. +12 34 5678 901234')"
                 :readonly="readonly"
      />
    </div>
    <div class="input-row">
      <InputText v-model:value="registrationData.instruments"
                 type="multiselect"
                 :label="t(appName, 'All my Instruments or Roles')"
                 :options="instruments"
                 :selectable="isNotAnInstrumentFamily"
                 trackBy="id"
                 optionLabel="name"
                 :autoLimit="true"
                 :tagWidth="100"
                 :readonly="readonly"
                 :multiple="true"
                 :placeholder="t(appName, 'e.g. double bass')"
                 :required="true"
      />
    </div>
    <div class="input-row">
      <NcCheckboxRadioSwitch v-model="registrationData.firstTimeApplication"
                             type="radio"
                             value="first-time"
                             :required="true"
      >
        {{ t(appName, 'First time application') }}
      </NcCheckboxRadioSwitch>
      <NcCheckboxRadioSwitch v-model="registrationData.firstTimeApplication"
                             type="radio"
                             value="you-know-me"
                             :required="true"
      >
        {{ t(appName, 'You know me') }}
      </NcCheckboxRadioSwitch>
      <NcRichContenteditable v-if="registrationData.firstTimeApplication === 'first-time'"
                             v-model="registrationData.whoAmI"
                             :maxlength="1024"
                             :autoComplete="autoComplete"
                             :placeholder="t(appName, 'Please introduce yourself!')"
                             :multiline="true"
                             :required="registrationData.firstTimeApplication === 'first-time'"
      />
    </div>
    <div class="navigation flex flex-row flex-justify-full">
      <RouterButton :to="routerDestination('registrationHome')"
                    icon="icon-home"
                    iconPosition="left"
      >
        {{ t(appName, 'Registration Start-Page') }}
      </RouterButton>
      <RouterButton :to="routerDestination('registrationParticipation')"
                    icon="icon-confirm"
                    iconPosition="right"
      >
        {{ t(appName, 'next') }}
      </RouterButton>
    </div>
    <DebugInfo :debugData="registrationData" />
  </div>
</template>

<script setup lang="ts">
import type { Country, Instrument } from '../../stores/appData.ts'

import { translate as t } from '@nextcloud/l10n'
import {
  NcCheckboxRadioSwitch,
  NcRichContenteditable,
} from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import {
  onBeforeMount,
  ref,
  watch,
} from 'vue'
import DebugInfo from '../../components/DebugInfo.vue'
import InputText from '../../components/InputText.vue'
import RouterButton from '../../components/RouterButton.vue'
/*
 * @todo Once personal data has been entered check whether there is
 * already registration data. We should also do some sort of fuzzy
 * compare and suggest to retrieve existing data if we find a
 * match. We can authenticate by displaying a partial email and
 * sending an authentication challenge. This would then result in
 * a redirect to the public page where the applicant needs to authenticate
 * (and can do password recovery).
 */
import { appName } from '../../config.ts'
import { useAppDataStore } from '../../stores/appData.ts'
import { useMemberDataStore } from '../../stores/memberData.ts'

// const props = withDefaults(
//   defineProps<{
//     token?: string
//   }>(),
//   {
//     token: undefined,
//   },
// )

const registrationData = useMemberDataStore()
const appData = useAppDataStore()
const routerDestination = appData.registrationRouteRecord

const {
  activeProject,
  countries,
  instruments,
  // projectName,
} = storeToRefs(appData)
const isNotAnInstrumentFamily = (option: Instrument) => option.id > 0
console.info('INSTRUMENTS', instruments)
const {
  country,
  firstName,
  nickName,
  personalPublicName,
  surName,
} = storeToRefs(registrationData)

const loading = ref(true)
const readonly = ref(true)
const registrationCountry = ref<undefined|Country>(undefined)

onBeforeMount(async () => {
  if (!activeProject.value) {
    appData.gotoRegistrationHome()
    return
  }
  await registrationData.initializeRegistrationData()
  registrationCountry.value = countries.value!.find((country) => country.code === registrationData.country)
  readonly.value = false
  loading.value = false
})

watch(registrationCountry, (newValue, _oldValue) => {
  country.value = newValue?.code
})

const updatePublicName = () => {
  personalPublicName.value = (nickName.value || firstName.value || '') + ' ' + (surName.value || '')
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const autoComplete = (_search: any, callback: (arg: any) => void) => {
  callback(null)
}
</script>

<style lang="scss" scoped>
.page-container {
  padding: 12px 0.5em 0 50px;
  min-height:100%;
  &.loading {
    width:100%;
    * {
      display:none;
    }
  }
}

.navigation {
  margin:0.5em 0;
}

.flex {
  display: flex;
  &.flex-row {
    flex-direction: row;
  }
  &.flex-justify-full {
    justify-content: space-between;
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
