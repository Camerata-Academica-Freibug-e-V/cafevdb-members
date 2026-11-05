<!--
 - @copyright Copyright (c) 2023, 2024, 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <NcContent class="root-view" :class="{ 'icon-loading': loading }" :appName="appName">
    <NcAppNavigation>
      <template #list>
        <NcAppNavigationItem :to="routerDestination('registrationHome')"
                             :name="isPublicPage ? t(appName, 'Home') : t(appName, 'Start Registration')"
                             icon="icon-home"
        />
        <NcAppNavigationItem :to="routerDestination('registrationPersonalProfile')"
                             :name="t(appName, 'Personal Profile')"
                             icon="icon-user"
                             :class="{ disabled: !activeProject }"
        />
        <NcAppNavigationItem :to="routerDestination('registrationParticipation')"
                             :name="t(appName, 'Instrumentation and Events')"
                             icon="icon-music"
                             :class="{ disabled: !activeProject }"
        />
        <NcAppNavigationItem :to="routerDestination('registrationProjectOptions')"
                             :name="t(appName, 'Options')"
                             icon="icon-details"
                             :class="{ disabled: !activeProject }"
        />
        <NcAppNavigationItem :to="routerDestination('registrationSubmission')"
                             :name="t(appName, 'Summary and Submission')"
                             icon="icon-checkmark"
                             :class="{ disabled: !activeProject }"
        />
      </template>
      <template #footer>
        <NcAppNavigationSettings>
          <NcCheckboxRadioSwitch v-model="debug">
            {{ t(appName, 'Enable Debug') }}
          </NcCheckboxRadioSwitch>
        </NcAppNavigationSettings>
      </template>
    </NcAppNavigation>
    <NcAppContent :class="{ 'icon-loading': loading }">
      <router-view v-show="!loading" v-model:loading="loading" />
    </NcAppContent>
  </NcContent>
</template>

<script setup lang="ts">
import { getCurrentUser } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import {
  NcAppContent,
  NcAppNavigation,
  NcAppNavigationItem,
  NcAppNavigationSettings,
  NcCheckboxRadioSwitch,
  NcContent,
} from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import {
  computed,
  onBeforeMount,
  ref,
  watch,
} from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { appName } from './config.ts'
import logger from './logger.ts'
import { useAppDataStore } from './stores/appData.ts'
import { useMemberDataStore } from './stores/memberData.ts'

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

const {
  activeProject,
  debug,
  projectName,
} = storeToRefs(appData)
const routerDestination = appData.registrationRouteRecord

const loading = ref(true)
const readonly = ref(true)

const router = useRouter()
const currentRoute = useRoute()

logger.info('CURRENT ROUTE', {
  route: { ...currentRoute },
  activeProject,
})

const isPublicPage = computed(() => !getCurrentUser())

const setPageTitle = () => {
  if (getCurrentUser()) {
    return
  }
  const pageTitleElement = document.getElementById('nextcloud')!
  const pageTitle = activeProject.value
    ? t(appName, 'Project Application for {projectName}', { projectName: projectName.value })
    : t(appName, 'Project Application')
  pageTitleElement.innerHTML = pageTitle
}

watch(activeProject, () => setPageTitle())
router.onReady(() => {
  if (!currentRoute.params.projectName && activeProject.value) {
    appData.gotoRegistrationHome()
  }
})

onBeforeMount(async () => {
  await registrationData.initializeRegistrationData()
  setPageTitle()
  readonly.value = false
  loading.value = false
})
</script>

<style lang="scss" scoped>
span {
  &[class^='icon-'], &[class*=' icon-'] {
    display: inline-block;
  }
  &.right-icon {
    margin-left: 1ex;
  }
  &.left-icon {
    margin-right: 1ex;
  }
}

.app-navigation-entry-wrapper.balhdisabled :deep() {
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
