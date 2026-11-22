import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AdminSettings from '../../src/AdminSettings.vue';
import { fetchSettings, saveConfirmedSetting } from '../../src/toolkit/util/settings-sync.ts';

vi.mock('@nextcloud/l10n', () => ({ translate: (_app: string, text: string) => text }));
vi.mock('@nextcloud/router', () => ({ generateUrl: (url: string) => url }));
vi.mock('@nextcloud/axios', () => ({ default: { post: vi.fn() } }));
vi.mock('@nextcloud/vue', () => ({
  NcSettingsSection: { template: '<section><slot /></section>' },
  NcProgressBar: { template: '<div />' },
  NcNoteCard: { template: '<aside><slot /></aside>' },
  NcButton: { template: '<button><slot /></button>' },
  NcCheckboxRadioSwitch: {
    props: ['modelValue', 'type'],
    emits: ['update:modelValue'],
    template: '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)">',
  },
}));
vi.mock('@rotdrop/nextcloud-vue-components/lib/components/TextFieldWithSubmitButton.vue', () => ({
  default: {
    props: ['modelValue', 'label'],
    template: '<input :aria-label="label" :value="modelValue">',
  },
}));
vi.mock('../../src/config.ts', () => ({ appName: 'cafevdbmembers' }));
vi.mock('../../src/toolkit/util/initial-state.ts', () => ({ default: () => ({ orchestraAppName: 'cafevdb' }) }));
vi.mock('../../src/toolkit/util/settings-sync.ts', () => ({
  fetchSettings: vi.fn(),
  saveConfirmedSetting: vi.fn(),
}));
vi.mock('../../src/toolkit/util/toasts.ts', () => ({ showError: vi.fn(), TOAST_PERMANENT_TIMEOUT: -1 }));

describe('Connector settings', () => {
  beforeEach(() => vi.resetAllMocks());

  it('shows inherited database and account diagnostics without an editable override', async () => {
    vi.mocked(fetchSettings).mockImplementation(async ({ settings }) => {
      Object.assign(settings, {
        inheritViewsDatabase: 'on',
        cloudUserViewsDatabase: 'preserved-override',
        connectorStatus: {
          database: 'producer',
          descriptor: { database: 'producer', account: { user: 'connector', accountHost: '%' } },
          actualAccount: 'connector@localhost',
          warnings: ['Account mismatch'],
        },
      });
      return true;
    });
    const wrapper = mount(AdminSettings);
    await flushPromises();

    expect(wrapper.find('input[aria-label="Personalized Views Database"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('producer');
    expect(wrapper.text()).toContain('connector@%');
    expect(wrapper.text()).toContain('connector@localhost');
    expect(wrapper.text()).toContain('Account mismatch');
    await wrapper.get('input[type="checkbox"]').setValue(false);
    expect(saveConfirmedSetting).toHaveBeenCalledWith(expect.objectContaining({
      value: 'off',
      section: 'admin',
      settingsKey: 'inheritViewsDatabase',
    }));
  });

  it('retains the editable legacy override and exposes unavailable-connection warnings', async () => {
    vi.mocked(fetchSettings).mockImplementation(async ({ settings }) => {
      Object.assign(settings, {
        inheritViewsDatabase: 'off',
        cloudUserViewsDatabase: 'legacy',
        connectorStatus: {
          database: 'legacy',
          descriptor: null,
          actualAccount: null,
          warnings: ['Metadata unavailable'],
        },
      });
      return true;
    });
    const wrapper = mount(AdminSettings);
    await flushPromises();

    expect((wrapper.get('input[aria-label="Personalized Views Database"]').element as HTMLInputElement).value).toBe('legacy');
    expect(wrapper.text()).toContain('Unverified');
    expect(wrapper.text()).toContain('Metadata unavailable');
  });
});
