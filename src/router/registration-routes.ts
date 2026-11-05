/**
 * @copyright Copyright (c) 2022-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import type { RouteRecordRaw } from 'vue-router';

const prefix = '/registration';

const projectNameParameter = '/:projectName([A-Z]\\w+\\d{4})?' as const;
const tokenParameter = '/:token([0-9a-f]{64})?' as const;
const urlParameters = `${projectNameParameter}${tokenParameter}` as const;

const routes: RouteRecordRaw[] = [
  {
    path: `${prefix}${urlParameters}`,
    component: () => import('../views/RegistrationStart.vue'),
    name: 'registrationHome' as const,
    props: true,
  },
  {
    path: `${prefix}${'/personal-profile'}${urlParameters}`,
    component: () => import('../views/Registration/PersonalProfile.vue'),
    name: 'registrationPersonalProfile' as const,
    props: true,
  },
  {
    path: `${prefix}${'/participation'}${urlParameters}`,
    component: () => import('../views/Registration/Participation.vue'),
    name: 'registrationParticipation' as const,
    props: true,
  },
  {
    path: `${prefix}${'/project-options'}${urlParameters}`,
    component: () => import('../views/Registration/ProjectOptions.vue'),
    name: 'registrationProjectOptions' as const,
    props: true,
  },
  {
    path: `${prefix}${'/submission'}${urlParameters}`,
    component: () => import('../views/Registration/Submission.vue'),
    name: 'registrationSubmission' as const,
    props: true,
  },
] as const;

export {
  prefix,
};

export default routes;
