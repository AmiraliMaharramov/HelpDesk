<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? __t('dashboard_title')) ?> — <?= e(__t('app_name')) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{500:'#6366f1',600:'#4f46e5',700:'#4338ca'}}}}}</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen">

<!-- Header -->
<header class="bg-white border-b border-slate-200 sticky top-0 z-30">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-6">
    <a href="/dashboard" class="flex items-center gap-2 font-bold text-indigo-700 text-lg">
      <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center">
        <i class="fas fa-bolt text-white text-xs"></i>
      </div>
      <?= e(__t('app_name')) ?>
    </a>

    <!-- Nav links -->
    <nav class="hidden md:flex items-center gap-1 text-sm flex-1">
      <a href="/dashboard" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 font-medium"><?= __t('dashboard_title') ?></a>
      <a href="/dashboard/tickets" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 font-medium"><?= __t('dashboard_tickets') ?></a>
      <a href="/tickets/new" class="px-3 py-2 rounded-lg text-indigo-600 hover:bg-indigo-50 font-medium">+ <?= __t('ticket_new') ?></a>
    </nav>

    <!-- Right -->
    <div class="ml-auto flex items-center gap-3">
      <!-- Lang -->
      <div class="flex items-center gap-1 text-xs">
        <a href="/set-lang/en" class="px-1.5 py-0.5 rounded <?= (Lang::current()==='en')?'bg-indigo-100 text-indigo-700 font-semibold':'text-slate-400' ?>">EN</a>
        <a href="/set-lang/tr" class="px-1.5 py-0.5 rounded <?= (Lang::current()==='tr')?'bg-indigo-100 text-indigo-700 font-semibold':'text-slate-400' ?>">TR</a>
        <a href="/set-lang/az" class="px-1.5 py-0.5 rounded <?= (Lang::current()==='az')?'bg-indigo-100 text-indigo-700 font-semibold':'text-slate-400' ?>">AZ</a>
      </div>

      <!-- User menu -->
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold">
          <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
        </div>
        <span class="hidden sm:block text-sm font-medium text-slate-700"><?= e($_SESSION['user_name'] ?? '') ?></span>
        <a href="/logout" class="text-slate-400 hover:text-slate-700 text-sm ml-1" title="<?= __t('nav_logout') ?>">
          <i class="fas fa-arrow-right-from-bracket"></i>
        </a>
      </div>
    </div>
  </div>
</header>

<!-- Flash messages -->
<?php if (!empty($success)): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
  <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm" id="flash-s">
    <i class="fas fa-circle-check text-emerald-500"></i>
    <span><?= e($success) ?></span>
    <button onclick="this.parentElement.remove()" class="ml-auto"><i class="fas fa-xmark text-emerald-400"></i></button>
  </div>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
  <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm" id="flash-e">
    <i class="fas fa-circle-exclamation text-red-500"></i>
    <span><?= e($error) ?></span>
    <button onclick="this.parentElement.remove()" class="ml-auto"><i class="fas fa-xmark text-red-400"></i></button>
  </div>
</div>
<?php endif; ?>

<!-- Body -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
  <div class="flex gap-6">

    <!-- Sidebar -->
    <aside class="w-56 flex-shrink-0 hidden lg:block">
      <nav class="space-y-1">
        <?php
        $uri = $_SERVER['REQUEST_URI'];
        $links = [
          '/dashboard'           => ['icon'=>'fa-gauge',        'label'=>__t('dashboard_title')],
          '/dashboard/tickets'   => ['icon'=>'fa-ticket',       'label'=>__t('dashboard_tickets')],
          '/tickets/new'         => ['icon'=>'fa-plus-circle',  'label'=>__t('ticket_new')],
          '/dashboard/orders'    => ['icon'=>'fa-box',          'label'=>__t('dashboard_orders')],
          '/dashboard/invoices'  => ['icon'=>'fa-file-invoice', 'label'=>__t('dashboard_invoices')],
          '/dashboard/profile'   => ['icon'=>'fa-user',         'label'=>__t('dashboard_profile')],
        ];
        foreach ($links as $href => $lk):
          $active = ($uri === $href) ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-100';
        ?>
        <a href="<?= $href ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= $active ?>">
          <i class="fas <?= $lk['icon'] ?> w-4 text-center"></i>
          <?= $lk['label'] ?>
        </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 min-w-0">
      <?= $pageContent ?>
    </main>
  </div>
</div>

<script>
  setTimeout(() => {
    document.getElementById('flash-s')?.remove();
    document.getElementById('flash-e')?.remove();
  }, 5000);
</script>
</body>
</html>
