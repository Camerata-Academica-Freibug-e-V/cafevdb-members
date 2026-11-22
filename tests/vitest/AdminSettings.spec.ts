import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import axios from '@nextcloud/axios';
import AdminSettings from '../../src/AdminSettings.vue';
import { fetchSettings, saveConfirmedSetting } from '../../src/toolkit/util/settings-sync.ts';
import { showInfo, showSuccess } from '../../src/toolkit/util/toasts.ts';

vi.mock('@nextcloud/l10n', () => ({ translate: (_app: string, text: string) => text }));
vi.mock('@nextcloud/router', () => ({ generateUrl: (url: string) => url }));
vi.mock('@nextcloud/axios', () => ({ default: { post: vi.fn() } }));
vi.mock('@nextcloud/vue', () => ({
  NcSettingsSection: { template: '<section><slot /></section>' },
  NcProgressBar: { template: '<div />' },
  NcNoteCard: { template: '<aside><slot /></aside>' },
  NcButton: { template: '<button><slot /></button>' },
  NcPasswordField: {
    props: ['modelValue', 'label', 'disabled'],
    emits: ['update:modelValue'],
    template: '<input type="password" :aria-label="label" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)">',
  },
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
vi.mock('../../src/toolkit/util/toasts.ts', () => ({
  showError: vi.fn(), showInfo: vi.fn(), showSuccess: vi.fn(), TOAST_PERMANENT_TIMEOUT: -1,
}));

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

  it('saves the CafeVDB-published grantee password without returning it to settings state', async () => {
    vi.mocked(fetchSettings).mockImplementation(async ({ settings }) => {
      Object.assign(settings, {
        inheritViewsDatabase: 'on',
        connectorStatus: {
          database: 'producer',
          descriptor: { database: 'producer', account: { user: 'cafevdb_nextcloud', accountHost: 'localhost' } },
          passwordConfigured: false,
          actualAccount: null,
          warnings: [],
        },
      });
      return true;
    });
    const wrapper = mount(AdminSettings);
    await flushPromises();
    await wrapper.get('input[aria-label="Password for the expected MariaDB account"]').setValue('private password');
    await wrapper.get('form.connector-password').trigger('submit');
    await flushPromises();

    expect(axios.post).toHaveBeenCalledWith(
      'apps/cafevdbmembers/settings/admin/viewsDatabasePassword',
      { value: 'private password' },
    );
    expect((wrapper.get('input[aria-label="Password for the expected MariaDB account"]').element as HTMLInputElement).value).toBe('');
  });

  it('reports a successful connector check only after the refresh completes', async () => {
    vi.mocked(fetchSettings).mockImplementation(async ({ settings }) => {
      settings.connectorStatus = { database: 'views', descriptor: null, actualAccount: 'connector@localhost', warnings: [] };
      return true;
    });
    const wrapper = mount(AdminSettings);
    await flushPromises();
    expect(showSuccess).not.toHaveBeenCalled();

    let finish!: (value: boolean) => void;
    vi.mocked(fetchSettings).mockImplementationOnce(() => new Promise(resolve => { finish = resolve; }));
    const button = wrapper.findAll('button').find(item => item.text() === 'Check connector configuration')!;
    await button.trigger('click');
    expect(button.attributes('disabled')).toBeDefined();
    expect(button.text()).toContain('Checking connector configuration');
    expect(showSuccess).not.toHaveBeenCalled();
    finish(true);
    await flushPromises();
    expect(button.attributes('disabled')).toBeUndefined();
    expect(showSuccess).toHaveBeenCalledWith('Connector configuration checked. The personalized views connection is accessible.');
  });

  it('reports warnings without claiming the connector is accessible', async () => {
    vi.mocked(fetchSettings).mockImplementation(async ({ settings }) => {
      settings.connectorStatus = { database: 'views', descriptor: null, actualAccount: null, warnings: ['Connection failed'] };
      return true;
    });
    const wrapper = mount(AdminSettings);
    await flushPromises();
    await wrapper.findAll('button').find(item => item.text() === 'Check connector configuration')!.trigger('click');
    await flushPromises();
    expect(showInfo).toHaveBeenCalledWith('Connector configuration checked. Review the connector diagnostics.');
    expect(showSuccess).not.toHaveBeenCalled();
  });

  it('does not report success when the settings refresh fails', async () => {
    vi.mocked(fetchSettings).mockResolvedValue(false);
    const wrapper = mount(AdminSettings);
    await flushPromises();
    await wrapper.findAll('button').find(item => item.text() === 'Check connector configuration')!.trigger('click');
    await flushPromises();
    expect(showInfo).not.toHaveBeenCalled();
    expect(showSuccess).not.toHaveBeenCalled();
  });
});
