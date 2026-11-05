<!--
 * @copyright Copyright (c) 2022-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <NcContent :appName="appId">
    <NcAppNavigation>
      <template #list>
        <NcAppNavigationItem :to="{ name: 'home' }"
                             :name="t(appId, 'Home')"
                             icon="icon-home"
                             @click="showSidebar = false"
        />
        <NcAppNavigationItem :to="memberDataError ? {} : { name: 'personalProfile' }"
                             :name="t(appId, 'Personal Profile')"
                             icon="icon-files-dark"
                             :class="{ disabled: memberDataError }"
                             @click="showSidebar = false"
        />
        <NcAppNavigationItem :to="memberDataError ? {} : { name: 'bankAccounts' }"
                             :name="t(appId, 'Bank Accounts')"
                             icon="icon-files-dark"
                             :class="{ disabled: memberDataError }"
                             @click="showSidebar = false"
        />
        <NcAppNavigationItem :to="memberDataError ? {} : { name: 'instrumentInsurances' }"
                             :name="t(appId, 'Instrument Insurances')"
                             icon="icon-files-dark"
                             :class="{ disabled: memberDataError }"
                             @click="showSidebar = false"
        />
        <NcAppNavigationItem :to="memberDataError ? {} : { name: 'projects' }"
                             :name="t(appId, 'Projects')"
                             icon="icon-files-dark"
                             :class="{ disabled: memberDataError }"
                             @click="showSidebar = false"
        />
      </template>
      <template #footer>
        <NcAppNavigationSettings>
          <NcCheckboxRadioSwitch v-model="debug">
            {{ t(appId, 'Enable Debug') }}
          </NcCheckboxRadioSwitch>
        </NcAppNavigationSettings>
      </template>
    </NcAppNavigation>

    <NcAppContent :class="{ 'icon-loading': loading }" @showDetails="showSidebar = true">
      <router-view v-show="!loading" v-model:loading="loading" @viewDetails="handleDetailsRequest" />
    </NcAppContent>

    <NcAppSidebar v-show="showSidebar"
                  v-model:loading="loading"
                  :name="sidebarTitle"
                  @close="closeSidebar"
    >
      <NcAppSidebarTab v-if="sidebarView === 'InstrumentInsurances'"
                       id="details-side-bar"
                       icon="icon-share"
                       :name="t(appId, 'details')"
      >
        <InsuranceDetails v-bind="sidebarProps as InsuranceDetailsProps" />
      </NcAppSidebarTab>
      <NcAppSidebarTab v-if="sidebarView === 'Projects'"
                       id="details-side-bar"
                       icon="icon-share"
                       :name="t(appId, 'details')"
      >
        <ProjectDetails v-bind="sidebarProps as SideBarProps[typeof sidebarView]" />
      </NcAppSidebarTab>
    </NcAppSidebar>
  </NcContent>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import {
  NcAppContent,
  NcAppNavigation,
  NcAppNavigationItem,
  NcAppNavigationSettings,
  NcAppSidebar,
  NcAppSidebarTab,
  NcCheckboxRadioSwitch,
  NcContent,
} from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import {
  computed,
  ref,
  watch,
} from 'vue'
import { useRouter } from 'vue-router'
import InsuranceDetails from './views/InstrumentInsurances/InsuranceDetails.vue'
import ProjectDetails from './views/Projects/ProjectDetails.vue'
import { appName as appId } from './config.ts'
import { useAppDataStore } from './stores/appData.ts'
import { useMemberDataStore } from './stores/memberData.ts'

type InsuranceDetailsProps = InstanceType<typeof InsuranceDetails>['$props']
type ProjectDetailsProps = InstanceType<typeof ProjectDetails>['$props']
type SideBarProps = {
  Projects: ProjectDetailsProps
  InstrumentInsurances: InsuranceDetailsProps
}

export type ViewDetailsComponent = keyof SideBarProps

export type ViewDetailsEventData<T extends ViewDetailsComponent> = {
  viewName: T
  title: string
  props: SideBarProps[T]
}

const memberData = useMemberDataStore()
const appData = useAppDataStore()
const {
  debug,
} = storeToRefs(appData)

const loading = ref(true)
const showSidebar = ref(false)
const sidebarTitle = ref('')
const sidebarView = ref<ViewDetailsComponent|undefined>(undefined)
const sidebarProps = ref<SideBarProps[ViewDetailsComponent]>({} as SideBarProps[ViewDetailsComponent])
let memberDataPollTimer = null as null|ReturnType<typeof setTimeout>
const memberDataPollTimeout = 60 * 1000

const memberDataError = computed(() => memberData.initialized.error)

memberData.initialized.error = null
memberData.initialize(true, true).finally(() => {
  loading.value = false
})

const router = useRouter()
router.beforeEach((to) => {
  if (memberDataError.value && to.name !== 'home') {
    return { name: 'home' }
  }
})

const closeSidebar = () => {
  showSidebar.value = false
}

const handleDetailsRequest = <T extends ViewDetailsComponent>(data: { title: string, viewName: T, props: SideBarProps[T] }) => {
  showSidebar.value = true
  sidebarTitle.value = data.title
  sidebarView.value = data.viewName
  sidebarProps.value = data.props
}

const pollMemberData = async () => {
  await memberData.initialize(true, false) // silent, do not reset
  if (memberDataError.value) {
    memberDataPollTimer = setTimeout(() => pollMemberData(), memberDataPollTimeout)
  } else {
    memberDataPollTimer = null
    loading.value = false
  }
}

watch(memberDataError, (newVal, oldVal) => {
  if (oldVal && memberDataPollTimer) {
    clearTimeout(memberDataPollTimer)
    memberDataPollTimer = null
  } else if (newVal && !memberDataPollTimer) {
    memberDataPollTimer = setTimeout(() => pollMemberData(), memberDataPollTimeout)
  }
})
</script>

<style lang="scss" scoped>
.app-navigation-entry.disabled :deep() {
  opacity: 0.5;
  &, & * {
    cursor: default !important;
    pointer-events: none;
  }
}

.empty-content :deep() {
  h2 ~ p {
    text-align: center;
  }
  .hint {
    color: var(--color-text-lighter);
  }
  .error-section {
    text-align: center;
    .error-info {
      font-weight: bold;
      font-style: italic;
      max-width: 66ex;
    }
    .hint {
      max-width: 66ex;
    }
  }
}
</style>
