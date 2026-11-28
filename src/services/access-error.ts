/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n';
import { appName } from '../config.ts';
import { isAxiosErrorResponse } from '../toolkit/types/axios-type-guards.ts';

/**
 * Extract a user-facing error without exposing exception traces.
 *
 * @param error The caught request or application error.
 */
export const accessErrorMessage = (error: unknown): string => {
  const seen = new Set<unknown>();
  let current = error;
  while (!seen.has(current)) {
    seen.add(current);
    if (isAxiosErrorResponse(current)) {
      const data = current.response.data;
      if (data && typeof data === 'object') {
        const messages = 'messages' in data ? data.messages : null;
        if (Array.isArray(messages)) {
          const message = messages.filter((value): value is string => typeof value === 'string' && value.trim() !== '').join(' ');
          if (message) {
            return message;
          }
        }
        const ocs = 'ocs' in data ? data.ocs : null;
        const meta = ocs && typeof ocs === 'object' && 'meta' in ocs ? ocs.meta : null;
        const message = meta && typeof meta === 'object' && 'message' in meta ? meta.message : null;
        if (typeof message === 'string' && message.trim() !== '') {
          return message;
        }
      }
      return t(appName, 'The server returned HTTP status {status}.', { status: current.response.status });
    }
    if (!(current instanceof Error) || !current.cause) {
      break;
    }
    current = current.cause;
  }
  if (current instanceof Error && current.message) {
    return current.message;
  }
  return t(appName, 'No further error details are available.');
};
