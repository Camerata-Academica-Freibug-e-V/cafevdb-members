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
  <div class="page-container participation-view" :class="{ 'icon-loading': loading, loading }">
    <h2>
      {{ t(appName, 'Instrumentation, Rehearsals and Concerts for "{name}"', { name: activeProject?.name || '' }) }}
    </h2>
    <h3>
      {{ t(appName, 'Please configure the instrument or the role you intend to play in this project.') }}
    </h3>
    <div v-if="!loading && registrationProject">
      <div class="input-row">
        <InputText v-model="registrationData.instruments"
                   type="multiselect"
                   :label="t(appName, 'All my Instruments or Roles')"
                   :options="instruments"
                   groupValues="instruments"
                   groupLabel="family"
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
        <InputText v-model="registrationProject.instruments"
                   type="multiselect"
                   :label="t(appName, 'Project Instruments or Roles')"
                   :options="personalProjectInstrumentOptions"
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
      <div v-if="personalProjectInstrumentOptions.length === 0">
        {{ t(appName, 'You do not seem to play any instrument configured for the project: {instruments}.', {
          instruments: projectInstrumentsText }) }}
      </div>
      <div class="event-list">
        <h3>
          {{ t(appName, 'Timetable') }}
        </h3>
        <NcCheckboxRadioSwitch v-model="noAbsenceCheck" :disabled="!noAbsence">
          {{ t(appName, 'I will participate in all events and not miss a single one!') }}
        </NcCheckboxRadioSwitch>
        <div v-if="!noAbsenceCheck"
             class="absence-instructions"
        >
          {{ t(appName, 'Please open the dots menu for each particular event you cannot participate in, toggle the contained checkbox and give a short explanation!') }}
        </div>
        <div v-if="!noAbsenceCheck" class="absence-instructions">
          {{ t(appName, 'Please understand that applications of people without or with less absence are preferred.') }}
        </div>
        <ul class="event-list">
          <NcListItem v-for="event in activeProject?.projectEvents"
                      :key="event.id"
                      :ref="(el) => addEventItemRef(el, event.id)"
                      :name="event.calendarObject.summary"
                      :forceDisplayActions="true"
                      class="calendar-event"
          >
            <template #subname>
              <span class="event-times-location">{{ calendarDateTime(event.calendarObject) }}</span>
              <span v-if="event.calendarObject.location" class="event-location">{{ ', ' + t(appName, 'location: {location}', { location: event.calendarObject.location }) }}</span>
            </template>
            <template v-if="registrationProject?.absence[event.id]" #indicator>
              <AbsenceIndicator :size="24" fillColor="#ff0000" />
            </template>
            <template v-if="!noAbsenceCheck && event.absenceField > 0" #actions>
              <NcActionCheckbox v-model="registrationProject.absence[event.id]">
                {{ t(appName, 'I cannot participate') }}
              </NcActionCheckbox>
              <NcActionTextEditable v-if="registrationProject.absence[event.id]"
                                    v-model:modelValue="registrationProject.absenceReasons[event.id]"
                                    :name="t(appName, '… because …')"
                                    required
                                    @submit="submitAbsenceReason($event, event.id)"
              >
                <template #icon>
                  <Pencil :size="20" />
                </template>
              </NcActionTextEditable>
            </template>
            <template #extra>
              <div class="event-description">
                {{ event.calendarObject.description }}
              </div>
              <div v-if="registrationProject.absence[event.id]" class="absence-reason">
                <span class="absence-reason-intro">{{ t(appName, 'I cannot participate because …') }}</span>
                <NcTextArea v-model="registrationProject.absenceReasons[event.id]"
                            class="absence-reason-text"
                />
              </div>
            </template>
          </NcListItem>
        </ul>
      </div>
    </div>
    <div class="navigation flex flex-row flex-justify-full">
      <RouterButton :to="routerDestination('registrationPersonalProfile')"
                    icon="icon-history"
                    iconPosition="left"
      >
        {{ t(appName, 'back') }}
      </RouterButton>
      <RouterButton :to="routerDestination('registrationProjectOptions')"
                    icon="icon-confirm"
                    iconPosition="right"
      >
        {{ t(appName, 'next') }}
      </RouterButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  NcActions,
} from '@nextcloud/vue'
import type Vue from 'vue'
import type { CalendarObject } from '../../stores/appData.ts'

import {
  getCanonicalLocale,
  translate as t,
} from '@nextcloud/l10n'
import {
  NcActionCheckbox,
  NcActionTextEditable,
  NcCheckboxRadioSwitch,
  NcListItem,
  NcTextArea,
} from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import {
  getCurrentInstance,
  onBeforeMount,
  onMounted,
  ref,
} from 'vue'
import AbsenceIndicator from 'vue-material-design-icons/AlertOctagon.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import InputText from '../../components/InputText.vue'
import RouterButton from '../../components/RouterButton.vue'
import { appName } from '../../config.ts'
import logger from '../../logger.ts'
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

logger.info('Participation')

const registrationData = useMemberDataStore()
const appData = useAppDataStore()
const routerDestination = appData.registrationRouteRecord

const loading = ref(true)
const readonly = ref(true)
const noAbsenceCheck = ref(true)
const projectEventItems: Record<number, Vue> = {}
const locale = getCanonicalLocale()

const {
  activeProject,
  instruments,
  projectInstrumentsText,
  // projectName,
} = storeToRefs(appData)
const {
  noAbsence,
  personalProjectInstrumentOptions,
  registrationProject,
} = storeToRefs(registrationData)

onBeforeMount(async () => {
  if (!activeProject.value) {
    appData.gotoRegistrationHome()
    return
  }
  await registrationData.initializeRegistrationData()
  readonly.value = false
  loading.value = false
  noAbsenceCheck.value = noAbsence.value
})

const addEventItemRef = (el: Vue, eventId: number) => {
  projectEventItems[eventId] = el
}

onMounted(() => {
  logger.info('Event Item', {
    refs: projectEventItems,
    instance: getCurrentInstance()?.proxy,
  })
})

const submitAbsenceReason = (vueEvent, eventId: number) => {
  const actions: typeof NcActions = projectEventItems[eventId].$refs.actions as typeof NcActions
  if (actions) {
    actions.closeMenu()
  }
  registrationProject.value!.absenceReasons[eventId] = vueEvent.target.getElementsByTagName('textarea')[0].value
}

// const localeDateTime = (dateTime: Date) => {
//   const options = {
//     timeStyle: 'short',
//     dateStyle: 'medium',
//   }
//   return dateTime.toLocaleString(locale, options)
// }

const localeTime = (dateTime: Date) => {
  const options: Intl.DateTimeFormatOptions = {
    timeStyle: 'short',
  }
  return dateTime.toLocaleTimeString(locale, options)
}

const localeDate = (dateTime: Date) => {
  const options: Intl.DateTimeFormatOptions = {
    dateStyle: 'medium',
  }
  return dateTime.toLocaleDateString(locale, options)
}

const calendarDateTime = (calendarEvent: CalendarObject) => {
  if (calendarEvent.allday) {
    const end = new Date(calendarEvent.endDateTime)
    // end dates of whole day event always point to 00:00 of the day AFTER the event
    end.setDate(end.getDate() - 1)
    if (end <= calendarEvent.startDateTime) {
      return localeDate(calendarEvent.startDateTime)
    } else {
      return localeDate(calendarEvent.startDateTime) + ' - ' + localeDate(end)
    }
  } else {
    const startDate = localeDate(calendarEvent.startDateTime)
    const endDate = localeDate(calendarEvent.endDateTime)
    const startTime = localeTime(calendarEvent.startDateTime)
    const endTime = localeTime(calendarEvent.endDateTime)
    if (startDate === endDate) {
      return startDate + ', ' + startTime + ' - ' + endTime
    } else {
      return startDate + ', ' + startTime + ' - ' + endDate + ', ' + endTime
    }
  }
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

.WIP {
  color:red;
  font-weight:bold;
}

.event-list {
  :deep(.list-item) {
    flex-wrap: wrap;
    .list-item__extra {
      width: 100%;
    }
  }
  .absence-reason-intro {
    font-weight: bold;
    font-style: italic;
    color: var(--color-text-maxcontrast);
  }
  .event-description {
    padding-left: 1ex;
    font-style: italic;
    color: var(--color-text-maxcontrast);
  }
}
</style>
