/**
 * @copyright Copyright (c) 2023, 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import { createPinia } from 'pinia';
import { createApp } from 'vue';
import ProjectRegistation from './ProjectRegistration.vue';
import router from './router/app-router.ts';

const pinia = createPinia();

const app = createApp(ProjectRegistation);
app.config.performance = !!(import.meta?.env?.DEV);
app.use(router);
app.use(pinia);
app.mount('#content');

export default app;
