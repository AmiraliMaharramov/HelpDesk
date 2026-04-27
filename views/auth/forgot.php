<?php
/**
 * Auth view — Forgot Password (step 1: enter email)
 * Variables: $pageTitle, $success, $error, $old
 */
?>

<!-- Back link -->
<a href="/login" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-8 transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
  </svg>
  <?= e(__t('nav_login')) ?>
</a>

<!-- Icon + heading -->
<div class="mb-8">
  <div class="w-14 h-14 rounded-2xl bg-indigo-100 flex items-center justify-center mb-4">
    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586
           a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
    </svg>
  </div>
  <h1 class="text-2xl font-bold text-slate-900"><?= e(__t('forgot_title')) ?></h1>
  <p class="mt-1 text-sm text-slate-500"><?= e(__t('forgot_subtitle')) ?></p>
</div>

<!-- Success banner -->
<?php if ($success): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
  <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <p class="text-sm text-emerald-700"><?= e($success) ?></p>
</div>
<?php endif; ?>

<!-- Error banner -->
<?php if ($error): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
  <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <p class="text-sm text-red-700"><?= e($error) ?></p>
</div>
<?php endif; ?>

<form action="/forgot-password" method="POST" novalidate>
  <?= Csrf::field() ?>

  <div class="mb-6">
    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
      <?= e(__t('login_email')) ?>
    </label>
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
      </div>
      <input
        type="email"
        id="email"
        name="email"
        value="<?= old($old, 'email') ?>"
        autocomplete="email"
        placeholder="you@example.com"
        class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-slate-800 text-sm
               placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500
               focus:border-transparent transition-colors bg-white"
        required
      >
    </div>
    <p class="mt-2 text-xs text-slate-400"><?= e(__t('forgot_email')) ?></p>
  </div>

  <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white
                 font-semibold py-3 px-6 rounded-xl transition-colors focus:outline-none
                 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <?= e(__t('forgot_btn')) ?>
  </button>
</form>
