/**
 * @copyright Copyright (c) 2022, 2023, 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

import type { RouteLocationRaw } from 'vue-router';
import type { ProjectParticipantField } from './memberData.ts';

import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import { defineStore } from 'pinia';
import {
  computed,
  ref,
  watch,
} from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { appName } from '../config.ts';
import logger from '../logger.ts';
import getInitialState from '../toolkit/util/initial-state.ts';

export interface InstrumentFamily {
  family: string;
}

export interface Instrument {
  id: number;
  name: string;
  sortOrder: number;
  families: InstrumentFamily[];
}

export interface CalendarObject {
  start: string;
  startDateTime: Date;
  end: string;
  endDateTime: Date;
  summary: string;
  location: string;
  description: string;
  allday: boolean;
}

export interface ProjectEvent {
  id: number;
  calendarObject: CalendarObject;
  absenceField: number;
}

export interface InstrumentationNumber {
  project: number; // project id
  instrument: Instrument;
  voice: number;
}

export interface Project {
  id: number;
  name: string;
  year: number;
  type: 'temporary'|'template'|'permanent';
  projectEvents: ProjectEvent[];
  clubMembers?: boolean;
  participantFields: Record<number, ProjectParticipantField>;
  instrumentation: InstrumentationNumber[];
}

export interface InstrumentGroup {
  family: string;
  sortOrder: number;
  instruments: Instrument[];
}

export interface Locale {
  code: string;
  region: string;
  language: string;
}

export interface Country {
  code: string;
}

const projectsArray = getInitialState<Project[]>({ section: 'projects', defaults: [] })!;
let activeProjectIndex = getInitialState<number>({ section: 'activeProject', defaults: -1 });
const flatInstruments = getInitialState<Instrument[]>({ section: 'instruments', defaults: [] })!;
const countries = getInitialState<Country[]>({ section: 'countries', defaults: [] });
const displayLocale = getInitialState<Locale>({ section: 'displayLocale', defaults: null });

interface InitialState {
  orchestraAppName: string;
  orchestraName?: string;
  orchestraLocale: string;
  currencySymbol: string;
  currencyCode: string;
}

const initialState = getInitialState<InitialState>();

// of course, total over-kill ... just playing around
export const useAppDataStore = defineStore('app-data', () => {
  // convert the flat array of instruments to grouped options for Vue Multiselect
  const groupedInstruments: Record<string, InstrumentGroup> = {};
  for (const instrument of flatInstruments) {
    const familyTag = instrument.families.map((family) => family.family).join(', ');
    const optionGroup = groupedInstruments[familyTag] || { family: familyTag, sortOrder: 0, instruments: [] };
    optionGroup.instruments.push(instrument);
    optionGroup.sortOrder += instrument.sortOrder;
    groupedInstruments[familyTag] = optionGroup;
  }
  const instruments: Instrument[] = [];
  for (const optionGroup of Object.values(groupedInstruments).sort((a, b) => a.sortOrder - b.sortOrder)) {
    instruments.push({
      id: -1,
      name: optionGroup.family,
      sortOrder: -1,
      families: [],
    });
    instruments.splice(instruments.length, 0, ...optionGroup.instruments);
  }

  const orchestraName = computed(() => initialState?.orchestraName || t(appName, '[UNKNOWN]'));

  if (activeProjectIndex === null && projectsArray) {
    activeProjectIndex = 0;
  }
  const activeProject = ref<null|Project>((projectsArray && activeProjectIndex! >= 0) ? projectsArray[activeProjectIndex!] : null);

  for (const project of projectsArray) {
    for (const event of project.projectEvents) {
      event.calendarObject.startDateTime = new Date(event.calendarObject.start);
      event.calendarObject.endDateTime = new Date(event.calendarObject.end);
    }
    project.projectEvents.sort((a, b) => a.calendarObject.startDateTime.getTime() - b.calendarObject.startDateTime.getTime());
  }

  const projects = computed(() => projectsArray);

  console.info('PROJECTS', projects);

  // couple of computed properties as shortcut form the active project if any

  const projectInstruments = computed<Instrument[]>(() => {
    if (!activeProject.value) {
      return [];
    }
    const possibleInstruments = activeProject.value.instrumentation.filter(
      (instrumentationNumber) => instrumentationNumber.voice === 0,
    );
    return possibleInstruments.map((instrumentationNumber) => instrumentationNumber.instrument);
  });

  const projectInstrumentsText = computed(() => projectInstruments.value.map((instrument) => instrument.name).join(', '));

  const currentRoute = useRoute();
  watch(() => currentRoute.path, (newValue) => {
    if (newValue === '/') {
      return;
    }
    const projectName = currentRoute.params.projectName as string;
    const project = projectName ? projects.value.find((project) => project.name === projectName) : undefined;
    if (project && project.id !== activeProject.value?.id) {
      console.info('Changing active project');
      activeProject.value = project;
    }
  });

  const projectName = computed(() => activeProject.value?.name || (currentRoute.params.projectName as string) || '');

  const router = useRouter();

  const registrationRouteRecord = (routeName: string, token?: string): RouteLocationRaw => {
    logger.info('CURRENT ROUTE', { currentRoute });
    // The Vue-Rouer can handle optional parameters, but seemingly not missing parameters
    const params: Record<string, string> = projectName.value ? { projectName: projectName.value } : {};
    token = token || currentRoute.params.token as string;
    if (token) {
      params.token = token;
    }
    return { name: routeName, params };
  };

  const gotoRegistrationHome = () => router.replace(registrationRouteRecord('registrationHome'));

  const loginRedirection = (routeName: string) => {
    const finalDestination = router.resolve(registrationRouteRecord(routeName));
    console.info('FINAL', finalDestination);
    return generateUrl('/login?redirect_url=') + encodeURIComponent(finalDestination.href);
  };

  return {
    activeProject,
    countries: computed(() => countries),
    debug: ref(false),
    displayLocale: computed(() => displayLocale),
    initialState,
    instruments,
    memberRootFolder: ref<string>(''),
    orchestraName,
    projectInstruments,
    projectInstrumentsText,
    projectName,
    projects,
    registrationRouteRecord,
    gotoRegistrationHome,
    loginRedirection,
  };
});
