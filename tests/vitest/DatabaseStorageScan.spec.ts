import { getCurrentUser } from '@nextcloud/auth';
import axios from '@nextcloud/axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@nextcloud/auth', () => ({ getCurrentUser: vi.fn() }));
vi.mock('@nextcloud/axios', () => ({ default: { post: vi.fn() } }));
vi.mock('../../src/toolkit/util/generate-url.ts', () => ({
  default: (path: string) => `/apps/cafevdbmembers/${path}`,
}));

describe('database storage scan trigger', () => {
  beforeEach(() => {
    vi.resetModules();
    vi.resetAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => vi.useRealTimers());

  it('scans on load and retries after a failure while logged in', async () => {
    vi.mocked(getCurrentUser).mockReturnValue({ uid: 'member' } as ReturnType<typeof getCurrentUser>);
    vi.mocked(axios.post).mockRejectedValueOnce(new Error('offline')).mockResolvedValue({});
    const error = vi.spyOn(console, 'error').mockImplementation(() => {});

    await import('../../src/database-storage-scan.ts');
    await vi.waitFor(() => expect(axios.post).toHaveBeenCalledTimes(1));
    expect(axios.post).toHaveBeenCalledWith('/apps/cafevdbmembers/database-storage/scan');
    expect(error).toHaveBeenCalledOnce();
    await vi.advanceTimersByTimeAsync(600_000);
    expect(axios.post).toHaveBeenCalledTimes(2);
    error.mockRestore();
  });

  it('does not scan or schedule retries without a logged-in user', async () => {
    vi.mocked(getCurrentUser).mockReturnValue(null);
    await import('../../src/database-storage-scan.ts');
    expect(axios.post).not.toHaveBeenCalled();
    expect(vi.getTimerCount()).toBe(0);
  });
});
