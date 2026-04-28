<?php
/**
 * Notification listener partial.
 * Included by admin and dashboard layouts.
 * Connects to SSE endpoint and shows browser notifications / sound alert.
 */
if (empty($_SESSION['user_id'])) return;
?>
<script>
(function () {
  const userId    = <?= (int)$_SESSION['user_id'] ?>;
  const sseUrl    = '/api/sse/notifications';
  let   lastId    = 0;
  let   count     = 0;

  // Audio context for alert sound (generated tone, no file required)
  function playAlertTone() {
    try {
      const ctx  = new (window.AudioContext || window.webkitAudioContext)();
      const osc  = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.4);
    } catch (_) {}
  }

  function updateBadge(n) {
    const badge = document.getElementById('notifBadge');
    if (!badge) return;
    if (n > 0) {
      badge.textContent = n > 99 ? '99+' : n;
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  }

  function addNotifItem(notif) {
    const list = document.getElementById('notifList');
    if (!list) return;
    // Clear "no notifications" placeholder on first real item
    if (list.children.length === 1 && list.firstElementChild?.textContent?.trim() === 'No new notifications') {
      list.innerHTML = '';
    }
    const li = document.createElement('li');
    li.className = 'p-4 hover:bg-slate-50 cursor-pointer';
    li.innerHTML = `
      <p class="text-sm font-semibold text-slate-800">${notif.title ?? ''}</p>
      ${notif.body ? `<p class="text-xs text-slate-500 mt-0.5">${notif.body}</p>` : ''}
      <p class="text-[10px] text-slate-400 mt-1">${notif.time ?? ''}</p>
    `;
    list.prepend(li);
  }

  function connect() {
    const es = new EventSource(sseUrl + '?lastId=' + lastId);

    es.addEventListener('notification', function (e) {
      try {
        const notif = JSON.parse(e.data);
        lastId = notif.id ?? lastId;
        count++;
        updateBadge(count);
        addNotifItem(notif);
        playAlertTone();

        // Browser notification (if granted)
        if (Notification.permission === 'granted') {
          new Notification(notif.title ?? 'QuickFixDesk', {
            body: notif.body ?? '',
            icon: '/public/favicon.ico',
          });
        }
      } catch (_) {}
    });

    es.onerror = function () {
      es.close();
      // Reconnect after 4 s
      setTimeout(connect, 4000);
    };
  }

  // Request browser notification permission
  if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  // Start SSE
  if (typeof EventSource !== 'undefined') {
    connect();
  }

  // Reset count when bell dropdown opens
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('notifBtn')?.addEventListener('click', () => {
      count = 0;
      updateBadge(0);
    });
  });
})();
</script>
