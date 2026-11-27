/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 */

import type { Instrument } from '../stores/appData.ts';
import type { InstrumentInsurance, ProjectParticipant, ProjectParticipantField, SepaBankAccount } from '../stores/memberData.ts';
import type { FrontEndEntity } from '../toolkit/services/entity-factory.ts';

import { translate as t } from '@nextcloud/l10n';
import { appName } from '../config.ts';
import * as EntityRepository from '../toolkit/services/entity-repository.ts';

const dateString = (value: unknown): string|null => {
  if (value === null) {
    return null;
  }
  if (typeof value === 'string') {
    return value;
  }
  if (typeof value === 'object' && value !== null && 'date' in value && typeof value.date === 'string') {
    return value.date;
  }
  throw new TypeError('Invalid serialized date.');
};

const requiredDateString = (value: unknown): string => {
  const date = dateString(value);
  if (date === null) {
    throw new TypeError('Missing required date.');
  }
  return date;
};

const instrumentData = async (instrument: FrontEndEntity<'Instrument'>): Promise<Instrument> => {
  if (instrument.id === null) {
    throw new TypeError('Missing instrument identifier.');
  }
  const families = await Promise.all(Object.values(instrument.families));
  return {
    id: instrument.id,
    name: instrument.name,
    sortOrder: instrument.sortOrder,
    families: families.map((family) => ({ family: family.family })),
  };
};

const bankAccountData = async (account: FrontEndEntity<'SepaBankAccount'>): Promise<SepaBankAccount> => {
  const mandates = await Promise.all(Object.values(account.sepaDebitMandates));
  const sepaDebitMandates = mandates.map((mandate) => ({
    sequence: mandate.sequence,
    mandateReference: mandate.mandateReference,
    created: dateString(mandate.created),
    updated: dateString(mandate.updated),
    deleted: dateString(mandate.deleted),
    mandateDate: requiredDateString(mandate.mandateDate),
    lastUsedDate: dateString(mandate.lastUsedDate) ?? undefined,
  }));
  const numDeletedDebitMandates = sepaDebitMandates.filter((mandate) => mandate.deleted).length;
  return {
    sequence: account.sequence,
    iban: account.iban,
    bankAccountOwner: account.bankAccountOwner,
    created: dateString(account.created),
    updated: dateString(account.updated),
    deleted: dateString(account.deleted),
    sepaDebitMandates,
    numDeletedDebitMandates,
    numActiveDebitMandates: mandates.length - numDeletedDebitMandates,
  };
};

const insuranceData = async (insurance: FrontEndEntity<'InstrumentInsurance'>): Promise<InstrumentInsurance> => {
  const insuranceRate = await insurance.insuranceRate;
  const broker = await insuranceRate.broker;
  const rate = Number(insuranceRate.rate);
  if (!Number.isFinite(rate)) {
    throw new TypeError('Invalid insurance rate.');
  }
  return {
    id: insurance.id,
    isDebitor: insurance.isDebitor,
    isHolder: insurance.isHolder,
    isOwner: insurance.isOwner,
    object: insurance.object,
    manufacturer: insurance.manufacturer,
    yearOfConstruction: insurance.yearOfConstruction,
    insuranceAmount: insurance.insuranceAmount,
    startOfInsurance: requiredDateString(insurance.startOfInsurance),
    deleted: dateString(insurance.deleted) ?? undefined,
    insuranceRate: {
      rate,
      broker: { shortName: broker.shortName },
      geographicalScope: insuranceRate.geographicalScope,
      dueDate: requiredDateString(insuranceRate.dueDate),
    },
  };
};

const participantFieldData = async (field: FrontEndEntity<'ProjectParticipantField'>): Promise<ProjectParticipantField> => {
  const data = await Promise.all(Object.entries(field.fieldData).map(async ([key, value]) => {
    const datum = await value;
    const option = await datum.dataOption;
    if (option === null) {
      throw new Error(`Missing data option for participant field datum "${key}".`);
    }
    const supportingDocument = await datum.supportingDocument;
    const payments = await Promise.all(Object.values(datum.payments).map(async (value) => {
      const payment = await value;
      const compositePayment = await payment.compositePayment;
      const account = compositePayment ? await compositePayment.sepaBankAccount : null;
      const mandate = compositePayment ? await compositePayment.sepaDebitMandate : null;
      return {
        id: payment.id,
        amount: payment.amount,
        subject: payment.subject,
        compositePayment: compositePayment
          ? {
            id: compositePayment.id,
            amount: compositePayment.amount,
            dateOfReceipt: dateString(compositePayment.dateOfReceipt),
            subject: compositePayment.subject,
            notificationMessageId: compositePayment.notificationMessageId,
            created: dateString(compositePayment.created),
            updated: dateString(compositePayment.updated),
            sepaBankAccount: account?.iban ?? null,
            sepaDebitMandate: mandate?.mandateReference ?? null,
          }
          : null,
      };
    }));
    return [key, {
      optionKey: key,
      optionValue: datum.optionValue,
      deposit: datum.deposit,
      dataOption: {
        label: option.label,
        untranslatedLabel: option.untranslatedLabel,
        data: option.data,
      },
      supportingDocumentId: supportingDocument?.id,
      payments,
    }] as const;
  }));
  const options = await Promise.all(Object.entries(field.dataOptions).map(async ([key, value]) => {
    const option = await value;
    return [key, {
      key,
      field: field.id,
      label: option.label,
      untranslatedLabel: option.untranslatedLabel,
      data: option.data,
      deposit: option.deposit,
      limit: option.limit,
      tooltip: option.tooltip,
    }] as const;
  }));
  const defaultValue = await field.defaultValue;
  const projectEvent = await field.projectEvent;
  return {
    id: field.id,
    name: field.name,
    untranslatedName: field.untranslatedName,
    tooltip: field.tooltip,
    dataType: field.dataType,
    multiplicity: field.multiplicity,
    fieldData: Object.fromEntries(data),
    dataOptions: Object.fromEntries(options),
    defaultValue: defaultValue ? String(defaultValue.key) : null,
    absenceEvent: projectEvent?.id ?? 0,
    deleted: dateString(field.deleted),
  };
};

const participationData = async (participant: FrontEndEntity<'ProjectParticipant'>): Promise<ProjectParticipant> => {
  const project = await participant.project;
  // Load field options as well as data, without recursively fetching file contents.
  await EntityRepository.fetch({ entityName: 'Project', identifier: { id: project.id }, depth: 2 });
  const currentProject = EntityRepository.find('Project', String(project.id));
  if (currentProject === undefined) {
    throw new Error(`Missing project entity "${project.id}".`);
  }
  const fields = await Promise.all(Object.values(currentProject.participantFields).map(async (value) => {
    const field = await value;
    return [field.id, await participantFieldData(field)] as const;
  }));
  const instruments = await Promise.all(Object.values(participant.projectInstruments).map(async (value) => {
    const projectInstrument = await value;
    const instrument = await projectInstrument.instrument;
    if (instrument.id === null) {
      throw new TypeError('Missing instrument identifier.');
    }
    return {
      id: instrument.id,
      name: instrument.name,
      voice: projectInstrument.voice ?? 0,
      sectionLeader: projectInstrument.sectionLeader,
    };
  }));
  return {
    project: {
      id: project.id,
      name: project.name,
      type: project.type,
      year: project.year,
      clubMembers: project.clubMembers,
    },
    participantFields: Object.fromEntries(fields),
    projectInstruments: instruments,
  };
};

/**
 * Fetch the member's entity mesh and adapt it to the editable registration/profile state.
 *
 * @param userId The logged-in user's id, used in error messages only.
 */
export const loadMemberData = async (userId: string) => {
  const result = await EntityRepository.search({ entityName: 'Musician', findBy: {}, depth: 1 });
  const musicians = Object.values(result.Musician ?? {});
  if (musicians.length === 0) {
    throw new Error(t(appName, 'No member-data found for user-id "%s".', [userId]));
  }
  if (musicians.length !== 1) {
    throw new Error(t(appName, 'More than one musician found for user-id "%s".', [userId]));
  }
  const musician = musicians[0];
  const findBy = { musician: musician.id };
  // Expand only the associations used by the member views. A deep search from
  // Musician would also traverse encrypted files and their binary contents.
  await Promise.all([
    EntityRepository.search({ entityName: 'MusicianInstrument', findBy, depth: 2 }),
    EntityRepository.search({ entityName: 'SepaBankAccount', findBy, depth: 1 }),
    EntityRepository.search({ entityName: 'ProjectParticipant', findBy, depth: 2 }),
    EntityRepository.search({ entityName: 'InstrumentInsurance', findBy, depth: 2 }),
  ]);
  const [emailAddresses, instruments, sepaBankAccounts, instrumentInsurances, projectParticipation] = await Promise.all([
    Promise.all(Object.values(musician.emailAddresses).map(async (value) => {
      const email = await value;
      return { address: email.address, primary: email.address === musician.email };
    })),
    Promise.all(Object.values(musician.instruments).map(async (value) => {
      const musicianInstrument = await value;
      return { ...await instrumentData(await musicianInstrument.instrument), ranking: musicianInstrument.ranking };
    })),
    Promise.all(Object.values(musician.sepaBankAccounts).map(async (value) => bankAccountData(await value))),
    Promise.all(Object.values(musician.instrumentInsurances).map(async (value) => insuranceData(await value))),
    Promise.all(Object.values(musician.projectParticipation).map(async (value) => participationData(await value))),
  ]);
  const projectTypeOrder = { permanent: 0, temporary: 1, template: 2 };
  projectParticipation.sort(({ project: left }, { project: right }) =>
    projectTypeOrder[left.type] - projectTypeOrder[right.type]
    || right.year - left.year
    || (left.name < right.name ? -1 : left.name > right.name ? 1 : 0));
  const birthday = dateString(musician.birthday);
  return {
    addressSupplement: musician.addressSupplement,
    birthday: birthday === null ? undefined : new Date(birthday),
    city: musician.city,
    country: musician.country,
    email: musician.email,
    emailAddresses,
    firstName: musician.firstName,
    fixedLinePhone: musician.fixedLinePhone,
    instruments,
    instrumentInsurances,
    mobilePhone: musician.mobilePhone,
    nickName: musician.nickName,
    personalPublicName: `${musician.nickName || musician.firstName} ${musician.surName}`,
    postalCode: musician.postalCode === null ? undefined : String(musician.postalCode),
    projectParticipation,
    sepaBankAccounts,
    street: musician.street,
    streetNumber: musician.streetNumber,
    surName: musician.surName,
  };
};
