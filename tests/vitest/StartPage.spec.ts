import axios from '@nextcloud/axios';
import { showError } from '@nextcloud/dialogs';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { accessErrorMessage } from '../../src/services/access-error.ts';
import { useMemberDataStore } from '../../src/stores/memberData.ts';
import StartPage from '../../src/views/StartPage.vue';

vi.mock('@nextcloud/l10n', () => ({
  translate: (_app: string, text: string, parameters: Record<string, string|number> = {}) =>
    text.replace(/\{(\w+)\}/g, (placeholder, key) => String(parameters[key] ?? placeholder)),
}));
vi.mock('@nextcloud/axios', () => ({ default: { put: vi.fn() } }));
vi.mock('@nextcloud/auth', () => ({ getCurrentUser: () => ({ uid: 'member' }) }));
vi.mock('@nextcloud/router', () => ({ generateOcsUrl: (url: string) => url }));
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showInfo: vi.fn(), TOAST_PERMANENT_TIMEOUT: -1 }));
vi.mock('@nextcloud/vue', () => ({
  NcEmptyContent: { template: '<section><slot /><slot name="description" /></section>' },
}));
vi.mock('@rotdrop/nextcloud-vue-components/lib/components/DynamicSvgIcon.vue', () => ({ default: { template: '<span />' } }));
vi.mock('../../src/config.ts', () => ({ appName: 'cafevdbmembers' }));
vi.mock('../../src/stores/appData.ts', async () => {
  const { defineStore } = await import('pinia');
  return { useAppDataStore: defineStore('start-page-app', { state: () => ({ orchestraName: 'Orchestra' }) }) };
});
vi.mock('../../src/stores/memberData.ts', async () => {
  const { defineStore } = await import('pinia');
  return {
    useMemberDataStore: defineStore('start-page-member', {
      state: () => ({
        initialized: {
          error: 'The server returned HTTP status 400.',
          recryptRequest: null as number|null,
          recryptRequestError: null as string|null,
        },
      }),
    }),
  };
});

describe('Start page access guidance', () => {
  beforeEach(async () => {
    vi.clearAllMocks();
    const { createPinia, setActivePinia } = await import('pinia');
    setActivePinia(createPinia());
  });

  it('shows the failure detail and administrator guidance without implying authorization is the cause', () => {
    const wrapper = mount(StartPage);
    expect(wrapper.text()).toContain('Your personal data could not be accessed: The server returned HTTP status 400.');
    expect(wrapper.text()).toContain('If the problem persists, contact the orchestra administrators.');
    expect(wrapper.get('button').text()).toBe('Request access to my personal data');
    expect(wrapper.text()).not.toContain('already pending');
  });

  it('reflects pending requests and changes to the lookup state', async () => {
    const store = useMemberDataStore();
    store.initialized.recryptRequest = 123;
    const wrapper = mount(StartPage);
    expect(wrapper.text()).toContain('An access request is already pending.');
    expect(wrapper.get('button').text()).toBe('Resubmit access request');
    store.initialized.recryptRequest = null;
    store.initialized.recryptRequestError = 'Lookup failed';
    await flushPromises();
    expect(wrapper.text()).toContain('Unable to check whether an access request is pending: Lookup failed');
    expect(wrapper.text()).not.toContain('already pending');
  });

  it('disables submission until acknowledged and then reflects the pending request', async () => {
    let finish!: (value: unknown) => void;
    vi.mocked(axios.put).mockImplementationOnce(() => new Promise(resolve => { finish = resolve; }));
    const wrapper = mount(StartPage);
    await wrapper.get('button').trigger('click');
    expect(wrapper.get('button').attributes('disabled')).toBeDefined();
    expect(wrapper.text()).not.toContain('already pending');
    finish({});
    await flushPromises();
    expect(wrapper.get('button').attributes('disabled')).toBeUndefined();
    expect(wrapper.get('button').text()).toBe('Resubmit access request');
    expect(wrapper.text()).toContain('An access request is already pending.');
  });

  it('retains the state and reports a failed submission with HTTP detail', async () => {
    vi.mocked(axios.put).mockRejectedValueOnce({ isAxiosError: true, response: { status: 503, data: {} } });
    const wrapper = mount(StartPage);
    await wrapper.get('button').trigger('click');
    await flushPromises();
    expect(wrapper.get('button').text()).toBe('Request access to my personal data');
    expect(showError).toHaveBeenCalledWith(
      'Unable to submit the access request: The server returned HTTP status 503.',
      { timeout: -1 },
    );
  });
});

describe('Access error details', () => {
  it('uses OCS details inside nested errors, even when the messages list is empty', () => {
    const cause = { isAxiosError: true, response: { status: 400, data: { messages: [], ocs: { meta: { message: 'Invalid query' } } } } };
    expect(accessErrorMessage(new Error('Outer error', { cause: new Error('Inner error', { cause }) }))).toBe('Invalid query');
  });

  it('uses HTTP status for uninformative server bodies', () => {
    expect(accessErrorMessage({ isAxiosError: true, response: { status: 400, data: { messages: [] } } }))
      .toBe('The server returned HTTP status 400.');
  });

  it('preserves network error messages without a response', () => {
    expect(accessErrorMessage(new Error('Lookup failed', { cause: new Error('Network Error') }))).toBe('Network Error');
  });
});
