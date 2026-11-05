<!--
 - @copyright Copyright (c) 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <NcEmptyContent class="emp-content">
    <template #icon>
      <img :src="Icon">
    </template>
    <template #name>
      <h2 v-if="activeProject">
        {{ t(appName, '{orchestraName} project registration for {projectName}', { orchestraName, projectName }) }}
      </h2>
      <h2 v-else>
        {{ t(appName, '{orchestraName} project registration', { orchestraName }) }}
      </h2>
    </template>
    <template #description>
      <div v-if="activeProject"
           class="flex-container flex-center"
      >
        <NcActions v-if="projects.length > 1"
                   :menuTitle="t(appName, 'choose another one')"
        >
          <NcActionRouter v-for="project in projects"
                          :key="project.id"
                          :name="project.name"
                          :to="{ name: 'registrationHome', params: { projectName: project.name } }"
          />
        </NcActions>
        <span v-if="projects.length > 1" class="start-button-junctor">{{ t(appName, 'or') }}</span>
        <RouterButton :to="routerDestination('registrationPersonalProfile')"
                      icon="icon-confirm"
                      iconPosition="right"
        >
          {{ t(appName, 'register') }}
        </RouterButton>
        <span v-if="isPublicPage" class="start-button-junctor">{{ t(appName, 'or') }}</span>
        <RouterButton v-if="isPublicPage"
                      :to="loginRedirection('registrationHome')"
                      :external="true"
                      icon="icon-confirm"
                      iconPosition="right"
        >
          {{ t(appName, 'login and register') }}
        </RouterButton>
      </div>
      <h2 v-else>
        {{ t(appName, 'The project registration for all projects is closed.') }}
      </h2>
    </template>
  </NcEmptyContent>
</template>

<script setup lang="ts">
import { getCurrentUser } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import {
  NcActionRouter,
  NcActions,
  NcEmptyContent,
} from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import RouterButton from '../components/RouterButton.vue'
import Icon from '../../img/cafevdbmembers.svg'
import { appName } from '../config.ts'
import { useAppDataStore } from '../stores/appData.ts'

const appData = useAppDataStore()

const {
  activeProject,
  orchestraName,
  projectName,
  projects,
} = storeToRefs(appData)
const routerDestination = appData.registrationRouteRecord
const isPublicPage = computed(() => !getCurrentUser())

const loginRedirection = appData.loginRedirection
</script>

<style lang="scss" scoped>
.start-button-junctor {
  margin: 0 1ex;
}

.flex-container {
  display: flex;
  &.flex-center {
    align-items:center;
  }
}
</style>
