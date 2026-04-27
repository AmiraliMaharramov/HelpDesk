<?php
/**
 * Auth view — Login form
 * Variables available: $pageTitle, $success, $error, $old (array)
 */
?>

<!-- Page heading -->
<div class="mb-8">
  <h1 class="text-2xl font-bold text-slate-900"><?= e(__t('login_title')) ?></h1>
  <p class="mt-1 text-sm text-slate-500"><?= e(__t('login_subtitle')) ?></p>
</div>

<!-- Flash: success -->
<?php if ($success): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
  <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <p class="text-sm text-emerald-700"><?= e($success) ?></p>
</div>
<?php endif; ?>

<!-- Flash: error -->
<?php if ($error): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
  <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <p class="text-sm text-red-700"><?= e($error) ?></p>
</div>
<?php endif; ?>

<!-- Login form -->
<form action="/login" method="POST" novalidate>
  <?= Csrf::field() ?>

  <!-- Email -->
  <div class="mb-4">
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
  </div>

  <!-- Password -->
  <div class="mb-4">
    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
      <?= e(__t('login_password')) ?>
    </label>
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <input
        type="password"
        id="password"
        name="password"
        autocomplete="current-password"
        placeholder="••••••••"
        class="w-full pl-10 pr-11 py-2.5 border border-slate-200 rounded-xl text-slate-800 text-sm
               placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500
               focus:border-transparent transition-colors bg-white"
        required
      >
      <!-- Show/hide toggle -->
      <button type="button" onclick="togglePassword('password', this)"
              class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
              aria-label="Toggle password visibility">
        <svg id="password-eye" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- Remember me + Forgot password -->
  <div class="flex items-center justify-between mb-6">
    <label class="flex items-center gap-2 cursor-pointer">
      <input type="checkbox" name="remember" value="1"
             class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
      <span class="text-sm text-slate-600"><?= e(__t('login_remember')) ?></span>
    </label>
    <a href="/forgot-password" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
      <?= e(__t('login_forgot')) ?>
    </a>
  </div>

  <!-- Submit -->
  <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white
                 font-semibold py-3 px-6 rounded-xl transition-colors focus:outline-none
                 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <?= e(__t('login_btn')) ?>
  </button>
</form>

<!-- Register link -->
<p class="mt-6 text-center text-sm text-slate-500">
  <?= e(__t('login_no_account')) ?>
  <a href="/register" class="ml-1 text-indigo-600 hover:text-indigo-700 font-medium">
    <?= e(__t('nav_register')) ?>
  </a>
</p>

<script>
function togglePassword(fieldId, btn) {
  const input = document.getElementById(fieldId);
  const icon  = btn.querySelector('svg');
  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7
           a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878
           l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59
           m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0
           01-4.132 5.411m0 0L21 21"/>`;
  } else {
    input.type = 'password';
    icon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
           -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
  }
}
</script>
