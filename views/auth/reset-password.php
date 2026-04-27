<?php
/**
 * Auth view — Reset Password (step 3: set new password)
 * Variables: $pageTitle, $error, $success
 */
?>

<!-- Icon + heading -->
<div class="mb-8">
  <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center mb-4">
    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  </div>
  <h1 class="text-2xl font-bold text-slate-900"><?= e(__t('reset_password_title')) ?></h1>
  <p class="mt-1 text-sm text-slate-500"><?= e(__t('reset_password_subtitle')) ?></p>
</div>

<!-- Error banner -->
<?php if ($error): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
  <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <p class="text-sm text-red-700"><?= e($error) ?></p>
</div>
<?php endif; ?>

<form action="/reset-password" method="POST" novalidate>
  <?= Csrf::field() ?>

  <!-- New password -->
  <div class="mb-4">
    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
      <?= e(__t('reset_password_new')) ?>
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
        autocomplete="new-password"
        placeholder="••••••••"
        minlength="8"
        class="w-full pl-10 pr-11 py-2.5 border border-slate-200 rounded-xl text-slate-800 text-sm
               placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500
               focus:border-transparent transition-colors bg-white"
        required
        autofocus
        oninput="updateStrength(this.value)"
      >
      <button type="button" onclick="togglePassword('password', this)"
              class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
              aria-label="Toggle visibility">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
               -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
      </button>
    </div>

    <!-- Strength bar -->
    <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden">
      <div id="strength-bar" class="h-full rounded-full transition-all duration-300 bg-slate-200" style="width:0%"></div>
    </div>
    <p id="strength-label" class="mt-1 text-xs text-slate-400"></p>
  </div>

  <!-- Confirm password -->
  <div class="mb-6">
    <label for="password2" class="block text-sm font-medium text-slate-700 mb-1.5">
      <?= e(__t('reset_password_confirm')) ?>
    </label>
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <input
        type="password"
        id="password2"
        name="password2"
        autocomplete="new-password"
        placeholder="••••••••"
        minlength="8"
        class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-slate-800 text-sm
               placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500
               focus:border-transparent transition-colors bg-white"
        required
      >
    </div>
  </div>

  <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white
                 font-semibold py-3 px-6 rounded-xl transition-colors focus:outline-none
                 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <?= e(__t('reset_password_btn')) ?>
  </button>
</form>

<script>
function togglePassword(fieldId, btn) {
  const input = document.getElementById(fieldId);
  input.type = input.type === 'password' ? 'text' : 'password';
}

function updateStrength(val) {
  const bar   = document.getElementById('strength-bar');
  const label = document.getElementById('strength-label');
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { pct: '0%',   cls: 'bg-slate-200', lbl: '' },
    { pct: '25%',  cls: 'bg-red-400',   lbl: 'Weak' },
    { pct: '50%',  cls: 'bg-orange-400',lbl: 'Fair' },
    { pct: '75%',  cls: 'bg-yellow-400',lbl: 'Good' },
    { pct: '90%',  cls: 'bg-emerald-400',lbl: 'Strong' },
    { pct: '100%', cls: 'bg-emerald-500',lbl: 'Very strong' },
  ];
  const l = levels[score] || levels[0];
  bar.style.width  = l.pct;
  bar.className    = 'h-full rounded-full transition-all duration-300 ' + l.cls;
  label.textContent = l.lbl;
  label.className  = 'mt-1 text-xs ' + (score < 2 ? 'text-red-500' : score < 4 ? 'text-slate-400' : 'text-emerald-600');
}
</script>
