<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? __t('admin_panel')) ?> — <?= e(__t('app_name')) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{50:'#eef2ff',100:'#e0e7ff',500:'#6366f1',600:'#4f46e5',700:'#4338ca',900:'#1e1b4b'}}}}}</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .sidebar-link{@apply flex items-center gap-3 px-4 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors text-sm font-medium;}
  .sidebar-link.active{@apply bg-indigo-600 text-white;}
  .sidebar-section{@apply px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 mt-4;}
</style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

  <!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
  <aside id="sidebar" class="w-64 bg-slate-900 flex flex-col flex-shrink-0 overflow-y-auto transition-all duration-300">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700">
      <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
        <i class="fas fa-bolt text-white text-sm"></i>
      </div>
      <span class="font-bold text-white text-lg"><?= e(__t('app_name')) ?></span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4">

      <a href="/admin" class="sidebar-link <?= ($_SERVER['REQUEST_URI'] === '/admin') ? 'active' : '' ?>">
        <i class="fas fa-gauge-high w-5"></i> Dashboard
      </a>

      <div class="sidebar-section"><?= __t('ticket_new') ?></div>
      <a href="/admin/tickets"   class="sidebar-link"><i class="fas fa-ticket w-5"></i> Tickets</a>
      <a href="/admin/devices"   class="sidebar-link"><i class="fas fa-microchip w-5"></i> Devices</a>
      <a href="/admin/routing-rules" class="sidebar-link"><i class="fas fa-route w-5"></i> Routing Rules</a>
      <a href="/admin/canned-responses" class="sidebar-link"><i class="fas fa-comment-dots w-5"></i> Canned Responses</a>

      <div class="sidebar-section"><?= __t('admin_hr') ?></div>
      <a href="/admin/hr/departments" class="sidebar-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/hr/departments') ? 'active' : '' ?>">
        <i class="fas fa-building w-5"></i> <?= __t('admin_departments') ?>
      </a>
      <a href="/admin/staff" class="sidebar-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/staff') ? 'active' : '' ?>">
        <i class="fas fa-users w-5"></i> <?= __t('admin_users') ?>
      </a>
      <a href="/admin/staff/permissions" class="sidebar-link">
        <i class="fas fa-shield-halved w-5"></i> <?= __t('admin_perm_title') ?>
      </a>

      <div class="sidebar-section"><?= __t('admin_cms') ?></div>
      <a href="/admin/cms/faqs"    class="sidebar-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/cms/faqs') ? 'active' : '' ?>">
        <i class="fas fa-circle-question w-5"></i> <?= __t('admin_faq_title') ?>
      </a>
      <a href="/admin/cms/kb"      class="sidebar-link"><i class="fas fa-book w-5"></i> Knowledge Base</a>
      <a href="/admin/cms/pages"   class="sidebar-link"><i class="fas fa-file-lines w-5"></i> Pages</a>
      <a href="/admin/cms/sliders" class="sidebar-link"><i class="fas fa-images w-5"></i> Sliders</a>

      <div class="sidebar-section"><?= __t('admin_orders') ?></div>
      <a href="/admin/users"    class="sidebar-link"><i class="fas fa-user-group w-5"></i> <?= __t('admin_users') ?></a>
      <a href="/admin/orders"   class="sidebar-link"><i class="fas fa-box w-5"></i> <?= __t('admin_orders') ?></a>
      <a href="/admin/invoices" class="sidebar-link"><i class="fas fa-file-invoice-dollar w-5"></i> <?= __t('admin_invoices') ?></a>
      <a href="/admin/products" class="sidebar-link"><i class="fas fa-tags w-5"></i> <?= __t('admin_products') ?></a>

      <div class="sidebar-section">System</div>
      <a href="/admin/reports"     class="sidebar-link"><i class="fas fa-chart-bar w-5"></i> <?= __t('admin_reports') ?></a>
      <a href="/admin/audit-logs"  class="sidebar-link"><i class="fas fa-list-check w-5"></i> Audit Logs</a>
      <a href="/admin/settings"    class="sidebar-link"><i class="fas fa-gear w-5"></i> <?= __t('admin_settings') ?></a>
    </nav>

    <!-- User footer -->
    <div class="px-4 py-4 border-t border-slate-700">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold">
          <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-white truncate"><?= e($_SESSION['user_name'] ?? '') ?></p>
          <p class="text-xs text-slate-400 truncate"><?= e($_SESSION['user_role'] ?? '') ?></p>
        </div>
        <a href="/logout" class="text-slate-400 hover:text-white text-sm"><i class="fas fa-arrow-right-from-bracket"></i></a>
      </div>
    </div>
  </aside>

  <!-- ── Main ────────────────────────────────────────────────────────────── -->
  <div class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar -->
    <header class="bg-white border-b border-slate-200 h-16 flex items-center px-6 gap-4 flex-shrink-0">
      <button onclick="document.getElementById('sidebar').classList.toggle('w-0')" class="text-slate-500 hover:text-slate-800 lg:hidden">
        <i class="fas fa-bars"></i>
      </button>
      <h1 class="text-lg font-semibold text-slate-800 flex-1"><?= e($pageTitle ?? '') ?></h1>

      <!-- Notifications bell -->
      <div class="relative">
        <button id="notifBtn" class="relative text-slate-500 hover:text-slate-800 w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100">
          <i class="fas fa-bell text-lg"></i>
          <span id="notifBadge" class="hidden absolute top-1 right-1 w-4 h-4 bg-red-500 rounded-full text-white text-[10px] flex items-center justify-center font-bold">0</span>
        </button>
        <!-- Notification dropdown -->
        <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-200 z-50 max-h-96 overflow-y-auto">
          <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <span class="font-semibold text-slate-800">Notifications</span>
            <button onclick="markAllRead()" class="text-xs text-indigo-600 hover:underline">Mark all read</button>
          </div>
          <ul id="notifList" class="divide-y divide-slate-100">
            <li class="p-4 text-sm text-slate-400 text-center">No new notifications</li>
          </ul>
        </div>
      </div>

      <!-- Lang switcher -->
      <div class="flex items-center gap-1 text-sm">
        <a href="/set-lang/en" class="px-2 py-1 rounded <?= (Lang::current()==='en')?'bg-indigo-100 text-indigo-700 font-semibold':'text-slate-500 hover:text-slate-800' ?>">EN</a>
        <a href="/set-lang/tr" class="px-2 py-1 rounded <?= (Lang::current()==='tr')?'bg-indigo-100 text-indigo-700 font-semibold':'text-slate-500 hover:text-slate-800' ?>">TR</a>
        <a href="/set-lang/az" class="px-2 py-1 rounded <?= (Lang::current()==='az')?'bg-indigo-100 text-indigo-700 font-semibold':'text-slate-500 hover:text-slate-800' ?>">AZ</a>
      </div>
    </header>

    <!-- Flash messages -->
    <?php if (!empty($success)): ?>
    <div class="mx-6 mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm" id="flash-success">
      <i class="fas fa-circle-check text-emerald-500"></i>
      <span><?= e($success) ?></span>
      <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400 hover:text-emerald-700"><i class="fas fa-xmark"></i></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm" id="flash-error">
      <i class="fas fa-circle-exclamation text-red-500"></i>
      <span><?= e($error) ?></span>
      <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-700"><i class="fas fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <!-- Content -->
    <main class="flex-1 overflow-y-auto p-6">
      <?= $pageContent ?>
    </main>
  </div>
</div>

<!-- SSE + Notification JS -->
<?php require_once VIEW_PATH . '/partials/notification-listener.php'; ?>

<script>
  // Auto-dismiss flashes after 5 s
  setTimeout(() => {
    document.getElementById('flash-success')?.remove();
    document.getElementById('flash-error')?.remove();
  }, 5000);

  document.getElementById('notifBtn').addEventListener('click', () => {
    document.getElementById('notifDropdown').classList.toggle('hidden');
  });

  function markAllRead() {
    document.getElementById('notifList').innerHTML =
      '<li class="p-4 text-sm text-slate-400 text-center">No new notifications</li>';
    document.getElementById('notifBadge').classList.add('hidden');
    document.getElementById('notifDropdown').classList.add('hidden');
  }
</script>
</body>
</html>
