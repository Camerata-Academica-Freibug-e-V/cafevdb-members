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
  <ul class="project-details">
    <NcListItem v-if="participant.projectInstruments.length > 1"
                class="instruments-container"
                :name="t(appId, 'Instruments')"
    >
      <template #subname>
        <ul class="project-instruments">
          <NcListItem v-for="instrument in participant.projectInstruments"
                      :key="instrument.id"
                      :name="instrument.name"
                      :details="[instrument.voice > 0 ? t(appId, 'voice {voice}', { voice: instrument.voice }) : '', instrument.sectionLeader ? t(appId, 'section leader') : ''].filter(x => x.length > 0).join(', ')"
          />
        </ul>
      </template>
    </NcListItem>
    <NcListItem v-else-if="participant.projectInstruments.length == 1"
                :name="participant.projectInstruments[0].name"
                :details="[participant.projectInstruments[0].voice > 0 ? t(appId, 'voice {voice}', { voice: participant.projectInstruments[0].voice }) : '', participant.projectInstruments[0].sectionLeader ? t(appId, 'section leader') : ''].filter(x => x.length > 0).join(', ')"
    />
    <NcListItem :name="t(appId, 'Photos')"
                class="photos-item"
                :bold="true"
    >
      <template #subname>
        <a :target="md5(projectPathUrl(participant.project))" :href="projectPathUrl(participant.project)">
          {{ projectPath(participant.project) }}
        </a>
      </template>
    </NcListItem>
  </ul>
</template>

<script setup lang="ts">
import type {
  Project,
} from '../../stores/appData.ts'
import type {
  ProjectParticipant,
} from '../../stores/memberData.ts'

import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcListItem } from '@nextcloud/vue'
import { md5 } from 'js-md5'
import { appName as appId } from '../../config.ts'
import logger from '../../logger.ts'

const props = defineProps<{
  participant: ProjectParticipant
  memberRootFolder: string
}>()

logger.info('PROPS', { props })

const projectPath = (project: Project) => {
  const components = [
    props.memberRootFolder,
  ]
  if (project.type === 'temporary') {
    components.push(t(appId, 'projects'))
    components.push('' + project.year)
  }
  components.push(project.name)
  return '/' + components.join('/')
}

const projectPathUrl = (project: Project) => {
  const path = projectPath(project)
  return generateUrl('apps/files') + '?dir=' + path
}
</script>

<style lang="scss" scoped>
.project-details {
  :deep() {
    .list-item {
      padding-right: 0;
      ul .list-item {
        padding-top:2px;
        padding-bottom:2px;
      }
    }
    .instruments-container > .list-item > a {
      height: fit-content;
    }
    .list-item__wrapper.photos-item {
      .list-item-content__main {
        flex-shrink: 0;
      }
      .list-item-content {
        &__details, &__subname {
          a {
            color: CornFlowerBlue;
            text-decoration: underline;
            font-weight:normal;
          }
        }
      }
    }
  }
}
</style>
