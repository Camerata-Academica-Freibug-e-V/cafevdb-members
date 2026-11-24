/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth';
import axios from '@nextcloud/axios';
import generateAppUrl from './toolkit/util/generate-url.ts';

const interval = 600_000;

async function scan(): Promise<void> {
  if (!getCurrentUser()) {
    return;
  }
  try {
    await axios.post(generateAppUrl('database-storage/scan'));
  } catch (error) {
    console.error('Unable to refresh database storage', error);
  } finally {
    window.setTimeout(scan, interval);
  }
}

void scan();
