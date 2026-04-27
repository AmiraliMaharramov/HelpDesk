<?php
/**
 * Auth view — 2FA code verification (password reset step 2)
 * Variables: $pageTitle, $error, $success, $email (string)
 */
?>

<!-- Back link -->
<a href="/forgot-password" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-8 transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
  </svg>
  <?= e(__t('btn_back')) ?>
</a>

<!-- Icon + heading -->
<div class="mb-8">
  <div class="w-14 h-14 rounded-2xl bg-indigo-100 flex items-center justify-center mb-4">
    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0
           01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622
           5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
    </svg>
  </div>
  <h1 class="text-2xl font-bold text-slate-900"><?= e(__t('forgot_2fa_title')) ?></h1>
  <p class="mt-1 text-sm text-slate-500">
    <?= e(__t('auth_2fa_description')) ?>
    <?php if ($email): ?>
      <strong class="text-slate-700"><?= e($email) ?></strong>
    <?php endif; ?>
  </p>
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

<form action="/verify-2fa" method="POST" novalidate>
  <?= Csrf::field() ?>

  <div class="mb-6">
    <label for="code" class="block text-sm font-medium text-slate-700 mb-1.5">
      <?= e(__t('forgot_2fa_code')) ?>
    </label>

    <!-- Large centred code input -->
    <input
      type="text"
      id="code"
      name="code"
      maxlength="6"
      inputmode="numeric"
      pattern="[0-9]{6}"
      placeholder="000000"
      autocomplete="one-time-code"
      class="w-full text-center text-3xl font-mono tracking-[0.5em] py-4 border border-slate-200
             rounded-xl text-slate-900 placeholder-slate-300 focus:outline-none
             focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors bg-white"
      required
      autofocus
    >
    <p class="mt-2 text-xs text-slate-400 text-center"><?= e(__t('auth_2fa_expires')) ?></p>
  </div>

  <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white
                 font-semibold py-3 px-6 rounded-xl transition-colors focus:outline-none
                 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <?= e(__t('forgot_2fa_btn')) ?>
  </button>
</form>

<!-- Resend link (placeholder — requires email module) -->
<p class="mt-6 text-center text-sm text-slate-500">
  <?= e(__t('auth_2fa_no_code')) ?>
  <a href="/forgot-password" class="ml-1 text-indigo-600 hover:text-indigo-700 font-medium">
    <?= e(__t('auth_2fa_resend')) ?>
  </a>
</p>

<?php
// Development helper: show the code directly on screen when APP_DEBUG is on
if (defined('APP_DEBUG') && APP_DEBUG && !empty($_SESSION['_reset_debug_code'])):
?>
<div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
  <p class="text-xs text-amber-700 font-medium mb-1">⚠ DEV MODE — verification code:</p>
  <p class="text-2xl font-mono font-bold text-amber-800 tracking-widest">
    <?= e($_SESSION['_reset_debug_code']) ?>
  </p>
  <p class="text-xs text-amber-600 mt-1">This box is hidden in production.</p>
</div>
<?php endif; ?>

<script>
// Auto-advance: submit form when 6 digits are entered
document.getElementById('code').addEventListener('input', function() {
  this.value = this.value.replace(/\D/g, '').slice(0, 6);
  if (this.value.length === 6) {
    this.closest('form').submit();
  }
});
</script>
