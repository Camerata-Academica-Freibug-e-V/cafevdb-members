/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 */

import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { registerHooks } from 'node:module';
import { beforeEach, mock, test } from 'node:test';
import { createPinia, setActivePinia } from 'pinia';
import ts from 'typescript';

registerHooks({
  load(url, context, nextLoad) {
    if (url.endsWith('/src/stores/memberData.ts')) {
      return {
        format: 'module',
        source: ts.transpileModule(readFileSync(new URL(url), 'utf8'), {
          compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ESNext },
        }).outputText,
        shortCircuit: true,
      };
    }
    return nextLoad(url, context);
  },
});

const metadataRoot = '../../build/ts-types/php-modules/Wrapped/OCA/RotDrop/Toolkit/Doctrine/ORM/EntityMetadata/';
const metadata = async (name) => (await import(`${metadataRoot}${name}Metadata.ts`)).default;
const reference = (entityClassName, flatIdentifier) => ({ entityClassName, flatIdentifier });
const collection = (entityClassName, identifiers = []) => ({
  entityClassName,
  entities: Object.fromEntries(identifiers.map(([key, id]) => [key, { flatIdentifier: id }])),
});
const date = (value) => ({ date: value, timezone_type: 3, timezone: 'Europe/Berlin' });
let graph;
let requests;
let failure;
let toastMessages;

const add = async (name, id, fields) => {
  const defaults = Object.fromEntries(Object.entries(await metadata(name)).map(([key, field]) =>
    [key, field.mapping === 'to-many' ? collection(field.type) : null]));
  graph[name] ??= {};
  graph[name][id] = { ...defaults, ...fields };
};

const response = async (name, ids, depth) => {
  const repositories = {};
  const depths = {};
  const visit = async (name, id, depth) => {
    const key = `${name}/${id}`;
    if ((depths[key] ?? -1) >= depth) {
      return;
    }
    assert.ok(!['FileData', 'EncryptedFileData', 'ImageFileData'].includes(name), 'Member loading must not fetch binary file bodies');
    depths[key] = depth;
    const dto = graph[name]?.[id];
    assert.ok(dto, `Unexpected entity fetch: ${key}`);
    repositories[name] ??= {};
    repositories[name][id] = dto;
    if (depth === 0) {
      return;
    }
    for (const [fieldName, field] of Object.entries(await metadata(name))) {
      const value = dto[fieldName];
      if (field.mapping === 'to-one' && value) {
        await visit(value.entityClassName, value.flatIdentifier, depth - 1);
      } else if (field.mapping === 'to-many') {
        for (const valueReference of Object.values(value.entities)) {
          await visit(valueReference.entityClassName ?? value.entityClassName, valueReference.flatIdentifier, depth - 1);
        }
      }
    }
  };
  for (const id of ids) {
    await visit(name, id, depth);
  }
  return { data: { ocs: { data: { entities: ids.length ? { [name]: ids } : {}, repositories } } } };
};

mock.module('@nextcloud/axios', {
  defaultExport: {
    get: async (url, { params } = {}) => {
      if (url.includes('maintenance/encryption/recrypt')) {
        return { data: { ocs: { data: { request: 'pending-recryption' } } } };
      }
      if (failure) {
        throw failure;
      }
      const [, name, depthString] = /v1\/entities\/([^/]+)\/(\d+)$/.exec(url);
      const depth = Number(depthString);
      const criteria = JSON.parse(atob(params.findBy ?? params.find));
      requests.push({ name, depth, criteria });
      let ids;
      if (params.find) {
        if (typeof criteria === 'string') {
          assert.ok(!criteria.includes(':'), 'Composite associations must already be loaded');
          ids = [criteria];
        } else {
          assert.deepEqual(Object.keys(criteria), ['id'], 'Composite associations must already be loaded');
          ids = [String(criteria.id)];
        }
      } else {
        ids = Object.keys(graph[name] ?? {}).filter((id) =>
          criteria.musician === undefined || graph[name][id].musician.flatIdentifier === String(criteria.musician));
      }
      return response(name, ids, depth);
    },
  },
});
mock.module('@nextcloud/l10n', {
  namedExports: { translate: (_app, message, parameters = []) => message.replace('%s', parameters[0] ?? '') },
});
mock.module('../../src/toolkit/util/generate-url.ts', {
  namedExports: { generateOcsUrl: (url) => url },
});
mock.module('@nextcloud/auth', {
  namedExports: {
    getCurrentUser: () => ({ uid: 'cloud-user' }),
    getGuestUser: () => ({}),
  },
});
mock.module('@nextcloud/router', {
  namedExports: { generateOcsUrl: (url) => url },
});
mock.module('../../src/logger.ts', {
  defaultExport: { info: () => {}, error: () => {} },
});
mock.module('../../src/toolkit/util/initial-state.ts', {
  defaultExport: () => null,
});
mock.module('../../src/toolkit/util/toasts.ts', {
  namedExports: {
    showError: (message) => toastMessages.push(message),
    TOAST_PERMANENT_TIMEOUT: -1,
  },
});
mock.module('../../src/stores/appData.ts', {
  namedExports: { useAppDataStore: () => ({ activeProject: null }) },
});

const { loadMemberData } = await import('../../src/services/member-data.ts');
const { repositories } = await import('../../src/toolkit/services/entity-repository.ts');
const { useMemberDataStore } = await import('../../src/stores/memberData.ts');

beforeEach(async () => {
  graph = {};
  requests = [];
  failure = null;
  toastMessages = [];
  setActivePinia(createPinia());
  for (const name of Object.keys(repositories)) {
    delete repositories[name];
  }
  await add('Musician', '1', {
    id: 1,
    firstName: 'First',
    nickName: 'Nick',
    surName: 'Member',
    email: 'member@example.org',
    postalCode: '01234',
    birthday: date('1990-01-02 00:00:00.000000'),
    emailAddresses: collection('MusicianEmailAddress', [['primary', 'primary'], ['other', 'other']]),
    instruments: collection('MusicianInstrument', [['11', '1:11']]),
    sepaBankAccounts: collection('SepaBankAccount', [['1', '1:1']]),
    instrumentInsurances: collection('InstrumentInsurance', [['8', '8']]),
    projectParticipation: collection('ProjectParticipant', [['100', '100:1'], ['101', '101:1'], ['102', '102:1']]),
    encryptedFiles: collection('EncryptedFile', [['private', 'private']]),
  });
  await add('EncryptedFile', 'private', { fileData: collection('FileData', [['private', 'private']]) });
  await add('MusicianEmailAddress', 'primary', { address: 'member@example.org', musician: reference('Musician', '1') });
  await add('MusicianEmailAddress', 'other', { address: 'other@example.org', musician: reference('Musician', '1') });
  await add('MusicianInstrument', '1:11', {
    musician: reference('Musician', '1'),
    instrument: reference('Instrument', '11'),
    ranking: 1,
  });
  await add('Instrument', '11', {
    id: 11,
    name: 'Violin',
    sortOrder: 10,
    families: collection('InstrumentFamily', [['Strings', 'Strings']]),
  });
  await add('InstrumentFamily', 'Strings', { family: 'Strings' });
  await add('SepaBankAccount', '1:1', {
    musician: reference('Musician', '1'),
    sequence: 1,
    iban: 'test-iban',
    bankAccountOwner: 'Member',
    created: date('2020-01-01'),
    sepaDebitMandates: collection('SepaDebitMandate', [['1', '1:1']]),
  });
  await add('SepaDebitMandate', '1:1', {
    musician: reference('Musician', '1'),
    sepaBankAccount: reference('SepaBankAccount', '1:1'),
    sequence: 1,
    mandateReference: 'mandate-reference',
    mandateDate: date('2020-01-02'),
    deleted: date('2025-01-01'),
  });
  await add('InstrumentInsurance', '8', {
    id: 8,
    musician: reference('Musician', '1'),
    insuranceRate: reference('InsuranceRate', 'Broker:World'),
    isDebitor: true,
    isHolder: true,
    isOwner: true,
    object: 'Violin',
    insuranceAmount: 10000,
    startOfInsurance: date('2020-01-01'),
  });
  await add('InsuranceRate', 'Broker:World', {
    broker: reference('InsuranceBroker', 'Broker'),
    rate: '0.0025',
    geographicalScope: 'World',
    dueDate: date('2000-01-01'),
  });
  await add('InsuranceBroker', 'Broker', { shortName: 'Broker' });
  for (const [id, type, year] of [['100', 'temporary', 2026], ['101', 'permanent', 2020], ['102', 'template', 2030]]) {
    await add('Project', id, {
      id: Number(id),
      name: `Project ${id}`,
      year,
      type,
      clubMembers: id === '100',
      participantFields: id === '100' ? collection('ProjectParticipantField', [['10', '10']]) : collection('ProjectParticipantField'),
    });
    await add('ProjectParticipant', `${id}:1`, {
      musician: reference('Musician', '1'),
      project: reference('Project', id),
      projectInstruments: collection('ProjectInstrument', [['11', `${id}:1:11:1`]]),
    });
    await add('ProjectInstrument', `${id}:1:11:1`, {
      instrument: reference('Instrument', '11'),
      voice: 1,
      sectionLeader: true,
    });
  }
  await add('ProjectParticipantField', '10', {
    id: 10,
    project: reference('Project', '100'),
    name: 'Instrumentenversicherung',
    untranslatedName: 'Instrument Insurance',
    multiplicity: 'simple',
    dataType: 'receivables',
    dataOptions: collection('ProjectParticipantFieldDataOption', [['option-uuid', '10:option-uuid']]),
    fieldData: collection('ProjectParticipantFieldDatum', [['datum-uuid', '10:datum-uuid:100:1']]),
    defaultValue: reference('ProjectParticipantFieldDataOption', '10:option-uuid'),
  });
  await add('ProjectParticipantFieldDataOption', '10:option-uuid', {
    field: reference('ProjectParticipantField', '10'),
    key: 'option-uuid',
    label: '2026',
    untranslatedLabel: '2026',
    data: '2026',
    deposit: 0,
    limit: 1,
  });
  await add('ProjectParticipantFieldDatum', '10:datum-uuid:100:1', {
    optionKey: 'datum-uuid',
    field: reference('ProjectParticipantField', '10'),
    dataOption: reference('ProjectParticipantFieldDataOption', '10:option-uuid'),
    supportingDocument: reference('DatabaseStorageFile', '55'),
    payments: collection('ProjectPayment', [['22', '22']]),
  });
  await add('DatabaseStorageFile', '55', { id: 55, file: reference('EncryptedFile', 'private') });
  await add('ProjectPayment', '22', {
    id: 22,
    amount: '25.00',
    subject: 'Insurance',
    compositePayment: reference('CompositePayment', '33'),
  });
  await add('CompositePayment', '33', {
    id: 33,
    amount: 25,
    dateOfReceipt: date('2026-01-01'),
    sepaBankAccount: reference('SepaBankAccount', '1:1'),
    sepaDebitMandate: reference('SepaDebitMandate', '1:1'),
  });
});

test('loads the OCS mesh, resolving composite associations and adapting all member views', async () => {
  const member = await loadMemberData('cloud-user');
  assert.equal(member.personalPublicName, 'Nick Member');
  assert.equal(member.birthday.getFullYear(), 1990);
  assert.equal(member.postalCode, '01234');
  assert.deepEqual(member.emailAddresses.map((email) => email.primary), [true, false]);
  assert.deepEqual(member.instruments[0], {
    id: 11,
    name: 'Violin',
    sortOrder: 10,
    families: [{ family: 'Strings' }],
    ranking: 1,
  });
  assert.equal(member.sepaBankAccounts[0].created, '2020-01-01');
  assert.equal(member.sepaBankAccounts[0].numDeletedDebitMandates, 1);
  assert.equal(member.sepaBankAccounts[0].numActiveDebitMandates, 0);
  assert.equal(member.instrumentInsurances[0].insuranceRate.rate, 0.0025);
  assert.equal(member.instrumentInsurances[0].insuranceRate.broker.shortName, 'Broker');
  assert.deepEqual(member.projectParticipation.map(({ project }) => project.type), ['permanent', 'temporary', 'template']);
  const participation = member.projectParticipation[1];
  assert.deepEqual(participation.projectInstruments[0], { id: 11, name: 'Violin', voice: 1, sectionLeader: true });
  const field = participation.participantFields[10];
  assert.equal(field.untranslatedName, 'Instrument Insurance');
  assert.equal(field.defaultValue, 'option-uuid');
  assert.equal(field.fieldData['datum-uuid'].supportingDocumentId, 55);
  assert.equal(field.fieldData['datum-uuid'].payments[0].compositePayment.sepaBankAccount, 'test-iban');
  assert.equal(field.fieldData['datum-uuid'].payments[0].compositePayment.sepaDebitMandate, 'mandate-reference');
  assert.ok(requests.filter(({ criteria }) => criteria.musician !== undefined).every(({ criteria }) => criteria.musician === 1));
  assert.ok(requests.every(({ depth }) => depth <= 2));
  assert.doesNotThrow(() => JSON.stringify(member));
});

test('reports missing and duplicate member records before fetching associations', async () => {
  const musician = graph.Musician['1'];
  delete graph.Musician['1'];
  await assert.rejects(loadMemberData('cloud-user'), /No member-data found.*cloud-user/);
  assert.equal(requests.length, 1);
  graph.Musician = { 1: musician, 2: { ...musician, id: 2 } };
  await assert.rejects(loadMemberData('cloud-user'), /More than one musician/);
  assert.equal(requests.length, 2);
});

test('propagates OCS authorization errors and can retry after access is restored', async () => {
  failure = Object.assign(new Error('Forbidden'), { response: { data: { ocs: { meta: { message: 'No row-access token' } } } } });
  await assert.rejects(loadMemberData('cloud-user'), (error) => error.cause === failure);
  failure = null;
  assert.equal((await loadMemberData('cloud-user')).personalPublicName, 'Nick Member');
});

test('refreshes changed associations and preserves null birthdays', async () => {
  await loadMemberData('cloud-user');
  graph.Musician['1'].birthday = null;
  graph.Musician['1'].nickName = null;
  graph.Musician['1'].instruments = collection('MusicianInstrument');
  const member = await loadMemberData('cloud-user');
  assert.equal(member.birthday, undefined);
  assert.equal(member.personalPublicName, 'First Member');
  assert.deepEqual(member.instruments, []);
});

test('concurrent store initializations share handled errors and preserve recryption requests', async () => {
  const store = useMemberDataStore();
  failure = Object.assign(new Error('Forbidden'), {
    isAxiosError: true,
    response: { data: { ocs: { meta: { message: 'No row-access token' } } } },
  });
  await Promise.all([store.initialize(), store.initialize()]);
  assert.equal(store.initialized.error, 'No row-access token');
  assert.equal(store.initialized.loaded, false);
  assert.equal(store.initialized.promise, null);
  assert.equal(store.initialized.recryptRequest, 'pending-recryption');
  assert.equal(toastMessages.length, 1);
  failure = null;
  await store.initialize(true);
  assert.equal(store.initialized.error, null);
  assert.equal(store.initialized.loaded, true);
  assert.equal(store.personalPublicName, 'Nick Member');
});

test('store refreshes clear derived view flags and do not mutate the reset snapshot', async () => {
  const store = useMemberDataStore();
  await store.initialize(true, true);
  store.initialized.BankAccounts = true;
  await store.initialize(true, true);
  assert.equal(store.initialized.BankAccounts, undefined);
  store.initialized.BankAccounts = true;
  await store.initialize(true, true);
  assert.equal(store.initialized.BankAccounts, undefined);
  assert.equal(store.initialized.promise, null);
  assert.equal(store.sepaBankAccounts.length, 1);
});
