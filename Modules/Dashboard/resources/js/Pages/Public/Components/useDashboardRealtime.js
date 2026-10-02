import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { affectsDashboard } from './dashboardRealtime';

export default function useDashboardRealtime({ years, selectedCdkId, user, realtime }) {
  const yearKey = years.join(',');
  const effectiveCdkId = selectedCdkId ?? user?.cdk_id ?? null;

  useEffect(() => {
    let echo;
    let refreshTimer;
    let inFlight = false;
    let dirty = false;
    let hasConnected = false;
    let disposed = false;
    const visibleYears = yearKey.split(',').filter(Boolean).map(Number);

    const refresh = () => {
      if (document.visibilityState !== 'visible' || inFlight) {
        dirty = true;
        return;
      }
      dirty = false;
      inFlight = true;
      router.reload({
        only: ['stats'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
          if (disposed) return;
          inFlight = false;
          if (dirty) scheduleRefresh();
        },
      });
    };
    const scheduleRefresh = () => {
      dirty = true;
      clearTimeout(refreshTimer);
      refreshTimer = setTimeout(refresh, 1200);
    };
    const onVisibilityChange = () => {
      if (document.visibilityState === 'visible' && dirty) scheduleRefresh();
    };

    const fallback = setInterval(() => {
      if (document.visibilityState === 'visible') scheduleRefresh();
      else dirty = true;
    }, 5 * 60 * 1000);
    document.addEventListener('visibilitychange', onVisibilityChange);

    if (realtime?.key && user) {
      const secure = window.location.protocol === 'https:';
      const port = realtime.port || Number(window.location.port) || (secure ? 443 : 80);
      const channel = user.cdk_id == null
        ? 'dashboard.province'
        : `dashboard.cdk.${user.cdk_id}`;
      echo = new Echo({
        broadcaster: 'reverb',
        key: realtime.key,
        Pusher,
        cluster: 'mt1',
        wsHost: realtime.host || window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: secure,
        enabledTransports: secure ? ['wss'] : ['ws'],
      });
      echo.private(channel).listen('.dashboard.changed', (change) => {
        if (affectsDashboard(change, visibleYears, effectiveCdkId)) scheduleRefresh();
      });
      const client = echo.connector.pusher;
      client.connection.bind('connected', () => {
        if (hasConnected) scheduleRefresh();
        hasConnected = true;
      });
    }

    return () => {
      disposed = true;
      clearTimeout(refreshTimer);
      clearInterval(fallback);
      document.removeEventListener('visibilitychange', onVisibilityChange);
      echo?.disconnect();
    };
  }, [yearKey, effectiveCdkId, user?.id, user?.cdk_id, realtime?.key, realtime?.host, realtime?.port]);
}
