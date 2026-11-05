/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 */

import assert from 'node:assert/strict';
import { cp, mkdir, mkdtemp, rm, symlink, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { build, loadConfigFromFile } from 'vite';

const appRoot = fileURLToPath(new URL('../../', import.meta.url));

test('builds inside Nextcloud without resolving the server tsconfig dependencies', async () => {
  const directory = await mkdtemp(path.join(tmpdir(), 'cafevdbmembers-vite-'));
  try {
    const root = path.join(directory, 'apps', 'cafevdbmembers');
    await mkdir(root, { recursive: true });
    await writeFile(path.join(directory, 'tsconfig.json'), JSON.stringify({
      extends: '@vue/tsconfig/tsconfig.json',
    }));
    await cp(path.join(appRoot, 'tsconfig.json'), path.join(root, 'tsconfig.json'));
    await symlink(path.join(appRoot, 'node_modules'), path.join(root, 'node_modules'), 'dir');
    const entry = path.join(root, 'entry.ts');
    await writeFile(entry, 'export const value: number = 42;');
    const loaded = await loadConfigFromFile(
      { command: 'build', mode: 'production' },
      path.join(appRoot, 'vite.config.ts'),
    );
    assert.ok(loaded);
    assert.equal(loaded.config.tsconfig, path.join(appRoot, 'tsconfig.json'));
    const options = {
      configFile: false,
      root,
      logLevel: 'silent',
      build: { write: false, rolldownOptions: { input: entry } },
    };
    await assert.rejects(build(options), /Tsconfig not found @vue\/tsconfig\/tsconfig.json/);
    await build({ ...options, tsconfig: loaded.config.tsconfig });
  } finally {
    await rm(directory, { recursive: true, force: true });
  }
});
