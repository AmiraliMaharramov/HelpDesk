<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? __t('app_name')) ?> — <?= e(__t('app_name')) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { DEFAULT: '#4f46e5', dark: '#4338ca', light: '#818cf8' }
          },
          fontFamily: {
            sans: ['-apple-system','BlinkMacSystemFont','Segoe UI','Roboto','Helvetica Neue','Arial','sans-serif']
          }
        }
      }
    }
  </script>
  <style>
    [x-cloak] { display: none !important; }
    .input-field {
      @apply w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800 text-sm
             placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500
             focus:border-transparent transition-colors bg-white;
    }
    .input-error {
      @apply border-red-400 focus:ring-red-400;
    }
    .btn-primary {
      @apply w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800
             text-white font-semibold py-3 px-6 rounded-xl transition-colors
             focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
    }
    .btn-outline {
      @apply w-full border border-slate-200 hover:border-slate-300 bg-white
             text-slate-700 font-medium py-3 px-6 rounded-xl transition-colors text-sm;
    }
  </style>
</head>
<body class="min-h-screen bg-slate-50">

<div class="flex min-h-screen">

  <!-- ── Left Brand Panel (desktop only) ─────────────────────────────────── -->
  <div class="hidden lg:flex lg:w-5/12 xl:w-2/5 bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 flex-col justify-between p-12 relative overflow-hidden">

    <!-- Decorative circles -->
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-indigo-600/10 pointer-events-none"></div>
    <div class="absolute bottom-20 -left-20 w-64 h-64 rounded-full bg-indigo-500/10 pointer-events-none"></div>

    <!-- Logo -->
    <a href="/" class="flex items-center gap-3 relative z-10">
      <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center flex-shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066
               c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924
               0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724
               0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066
               c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756
               -2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608
               2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </div>
      <span class="text-white text-xl font-bold tracking-tight"><?= e(__t('app_name')) ?></span>
    </a>

    <!-- Headline & tagline -->
    <div class="relative z-10">
      <h2 class="text-white text-4xl font-bold leading-tight mb-4">
        Your Device's<br>Best Friend.
      </h2>
      <p class="text-slate-400 text-lg mb-12 leading-relaxed">
        Fast, expert technical support and e-commerce — on-site, remote, or drop-off.
      </p>

      <!-- Feature bullets -->
      <ul class="space-y-5">
        <?php
        $features = [
          ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',    'text' => 'Live ticket tracking & real-time chat'],
          ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'text' => 'Secure 2FA & enterprise-grade security'],
          ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'text' => 'Invoicing, orders & corporate accounts'],
        ];
        foreach ($features as $f):
        ?>
        <li class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-lg bg-indigo-600/20 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $f['icon'] ?>"/>
            </svg>
          </div>
          <span class="text-slate-300 text-sm leading-relaxed"><?= e($f['text']) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Bottom: language switcher + copyright -->
    <div class="relative z-10">
      <div class="flex items-center gap-2 mb-4">
        <?php
        $langs = ['en' => __t('lang_en'), 'tr' => __t('lang_tr'), 'az' => __t('lang_az')];
        $cur   = Lang::current();
        foreach ($langs as $code => $label):
        ?>
        <a href="/set-lang/<?= e($code) ?>"
           class="text-xs px-3 py-1.5 rounded-full transition-colors
                  <?= $code === $cur
                        ? 'bg-indigo-600 text-white'
                        : 'bg-slate-700 text-slate-300 hover:bg-slate-600' ?>">
          <?= e($label) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <p class="text-slate-600 text-xs">© <?= date('Y') ?> <?= e(__t('app_name')) ?>. <?= e(__t('footer_rights')) ?></p>
    </div>
  </div>

  <!-- ── Right Form Panel ──────────────────────────────────────────────────── -->
  <div class="flex-1 flex flex-col min-h-screen">

    <!-- Mobile top bar -->
    <div class="lg:hidden flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-white">
      <a href="/" class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066
                 c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756
                 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37
                 a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0
                 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572
                 c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826
                 -3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <span class="font-bold text-slate-800"><?= e(__t('app_name')) ?></span>
      </a>

      <!-- Mobile language switcher -->
      <div class="flex items-center gap-1.5">
        <?php foreach ($langs as $code => $label): ?>
        <a href="/set-lang/<?= e($code) ?>"
           class="text-xs px-2.5 py-1 rounded-full transition-colors
                  <?= $code === $cur
                        ? 'bg-indigo-100 text-indigo-700 font-medium'
                        : 'text-slate-500 hover:text-slate-800' ?>">
          <?= e(strtoupper($code)) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Form area -->
    <div class="flex-1 flex items-center justify-center px-6 py-10">
      <div class="w-full max-w-md">
        <?= $pageContent ?>
      </div>
    </div>

    <!-- Footer row -->
    <div class="px-6 py-4 flex items-center justify-center gap-4 text-xs text-slate-400 border-t border-slate-100">
      <a href="/" class="hover:text-indigo-600 transition-colors">← <?= e(__t('app_name')) ?></a>
      <span>·</span>
      <a href="/faq" class="hover:text-indigo-600 transition-colors"><?= e(__t('nav_faq')) ?></a>
      <span>·</span>
      <a href="/contact" class="hover:text-indigo-600 transition-colors"><?= e(__t('nav_contact')) ?></a>
    </div>
  </div>

</div>
</body>
</html>
