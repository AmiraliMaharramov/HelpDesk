<?php
/**
 * Auth view — Registration form (Individual & Corporate)
 * Variables: $pageTitle, $error, $success, $errors (field-level), $old (array)
 */

// Determine which tab was active last time (for validation repopulation)
$activeType = $old['type'] ?? 'individual';
?>

<!-- Page heading -->
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900"><?= e(__t('register_title')) ?></h1>
  <p class="mt-1 text-sm text-slate-500"><?= e(__t('register_subtitle')) ?></p>
</div>

<!-- Flash success -->
<?php if ($success): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
  <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <p class="text-sm text-emerald-700"><?= e($success) ?></p>
</div>
<?php endif; ?>

<!-- Flash error (top-level) -->
<?php if ($error): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
  <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <p class="text-sm text-red-700"><?= e($error) ?></p>
</div>
<?php endif; ?>

<form action="/register" method="POST" novalidate id="register-form">
  <?= Csrf::field() ?>
  <input type="hidden" name="type" id="input-type" value="<?= e($activeType) ?>">

  <!-- ── Account-type tab toggle ─────────────────────────────────────────── -->
  <div class="mb-6">
    <p class="text-sm font-medium text-slate-700 mb-2"><?= e(__t('register_type')) ?></p>
    <div class="inline-flex rounded-xl border border-slate-200 overflow-hidden bg-slate-50 p-1 gap-1">
      <button type="button" id="tab-individual"
              onclick="switchTab('individual')"
              class="tab-btn px-5 py-2 text-sm font-medium rounded-lg transition-all">
        <?= e(__t('register_individual')) ?>
      </button>
      <button type="button" id="tab-corporate"
              onclick="switchTab('corporate')"
              class="tab-btn px-5 py-2 text-sm font-medium rounded-lg transition-all">
        <?= e(__t('register_corporate')) ?>
      </button>
    </div>
  </div>

  <!-- ── Corporate-only fields ──────────────────────────────────────────── -->
  <div id="section-corporate" class="mb-6 space-y-4">
    <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-xl">
      <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wider mb-4">
        <?= e(__t('register_corporate')) ?> Information
      </p>

      <!-- Company Name -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">
          <?= e(__t('register_company_name')) ?> <span class="text-red-500">*</span>
        </label>
        <input type="text" name="company_name" id="company_name"
               value="<?= old($old, 'company_name') ?>"
               placeholder="Acme Corp Ltd."
               class="w-full px-4 py-2.5 border <?= hasError($errors, 'company_name') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white corp-required">
        <?php if (hasError($errors, 'company_name')): ?>
          <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'company_name') ?></p>
        <?php endif; ?>
      </div>

      <!-- Tax ID + Tax Office (2-col) -->
      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">
            <?= e(__t('register_tax_id')) ?> <span class="text-red-500">*</span>
          </label>
          <input type="text" name="tax_id" id="tax_id"
                 value="<?= old($old, 'tax_id') ?>"
                 placeholder="1234567890"
                 class="w-full px-4 py-2.5 border <?= hasError($errors, 'tax_id') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white corp-required">
          <?php if (hasError($errors, 'tax_id')): ?>
            <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'tax_id') ?></p>
          <?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">
            <?= e(__t('register_tax_office')) ?> <span class="text-red-500">*</span>
          </label>
          <input type="text" name="tax_office" id="tax_office"
                 value="<?= old($old, 'tax_office') ?>"
                 placeholder="Kadıköy VD"
                 class="w-full px-4 py-2.5 border <?= hasError($errors, 'tax_office') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white corp-required">
          <?php if (hasError($errors, 'tax_office')): ?>
            <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'tax_office') ?></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Authorized Person -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">
          <?= e(__t('register_authorized_person')) ?> <span class="text-red-500">*</span>
        </label>
        <input type="text" name="authorized_person" id="authorized_person"
               value="<?= old($old, 'authorized_person') ?>"
               placeholder="Jane Smith"
               class="w-full px-4 py-2.5 border <?= hasError($errors, 'authorized_person') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white corp-required">
        <?php if (hasError($errors, 'authorized_person')): ?>
          <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'authorized_person') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Common fields ──────────────────────────────────────────────────── -->

  <!-- First name + Last name (2-col) -->
  <div class="grid grid-cols-2 gap-3 mb-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5">
        <?= e(__t('register_first_name')) ?> <span class="text-red-500">*</span>
      </label>
      <input type="text" name="first_name"
             value="<?= old($old, 'first_name') ?>"
             placeholder="Jane"
             class="w-full px-4 py-2.5 border <?= hasError($errors, 'first_name') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white"
             required>
      <?php if (hasError($errors, 'first_name')): ?>
        <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'first_name') ?></p>
      <?php endif; ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5">
        <?= e(__t('register_last_name')) ?> <span class="text-red-500">*</span>
      </label>
      <input type="text" name="last_name"
             value="<?= old($old, 'last_name') ?>"
             placeholder="Doe"
             class="w-full px-4 py-2.5 border <?= hasError($errors, 'last_name') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white"
             required>
      <?php if (hasError($errors, 'last_name')): ?>
        <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'last_name') ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Email -->
  <div class="mb-4">
    <label class="block text-sm font-medium text-slate-700 mb-1.5">
      <?= e(__t('register_email')) ?> <span class="text-red-500">*</span>
    </label>
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
      </div>
      <input type="email" name="email"
             value="<?= old($old, 'email') ?>"
             autocomplete="email"
             placeholder="you@example.com"
             class="w-full pl-10 pr-4 py-2.5 border <?= hasError($errors, 'email') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white"
             required>
    </div>
    <?php if (hasError($errors, 'email')): ?>
      <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'email') ?></p>
    <?php endif; ?>
  </div>

  <!-- Phone -->
  <div class="mb-4">
    <label class="block text-sm font-medium text-slate-700 mb-1.5">
      <?= e(__t('register_phone')) ?>
    </label>
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
      </div>
      <input type="tel" name="phone"
             value="<?= old($old, 'phone') ?>"
             autocomplete="tel"
             placeholder="+90 555 123 4567"
             class="w-full pl-10 pr-4 py-2.5 border border-slate-200 focus:ring-indigo-500 rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white">
    </div>
  </div>

  <!-- Password + Confirm (2-col on md+) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5">
        <?= e(__t('register_password')) ?> <span class="text-red-500">*</span>
      </label>
      <div class="relative">
        <input type="password" name="password" id="reg-password"
               autocomplete="new-password"
               placeholder="Min. 8 characters"
               class="w-full px-4 pr-10 py-2.5 border <?= hasError($errors, 'password') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white"
               required minlength="8">
        <button type="button" onclick="togglePassword('reg-password', this)"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
        </button>
      </div>
      <?php if (hasError($errors, 'password')): ?>
        <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'password') ?></p>
      <?php endif; ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5">
        <?= e(__t('register_password_confirm')) ?> <span class="text-red-500">*</span>
      </label>
      <input type="password" name="password2"
             autocomplete="new-password"
             placeholder="Repeat password"
             class="w-full px-4 py-2.5 border <?= hasError($errors, 'password2') ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-indigo-500' ?> rounded-xl text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors bg-white"
             required>
      <?php if (hasError($errors, 'password2')): ?>
        <p class="mt-1 text-xs text-red-600"><?= fieldError($errors, 'password2') ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Agreements -->
  <div class="space-y-3 mb-6">
    <label class="flex items-start gap-3 cursor-pointer group">
      <input type="checkbox" name="terms" value="1"
             <?= !empty($old) && empty($old['terms']) ? '' : (empty($old) ? '' : 'checked') ?>
             class="mt-0.5 w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer flex-shrink-0">
      <span class="text-sm text-slate-600 group-hover:text-slate-800 transition-colors">
        <?= e(__t('register_terms')) ?>
        <a href="/terms" class="text-indigo-600 hover:underline" target="_blank">Terms</a>
      </span>
    </label>
    <?php if (hasError($errors, 'terms')): ?>
      <p class="text-xs text-red-600 ml-7"><?= fieldError($errors, 'terms') ?></p>
    <?php endif; ?>

    <label class="flex items-start gap-3 cursor-pointer group">
      <input type="checkbox" name="data_agreement" value="1"
             class="mt-0.5 w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer flex-shrink-0">
      <span class="text-sm text-slate-600 group-hover:text-slate-800 transition-colors">
        <?= e(__t('register_data_agreement')) ?>
      </span>
    </label>
    <?php if (hasError($errors, 'data_agreement')): ?>
      <p class="text-xs text-red-600 ml-7"><?= fieldError($errors, 'data_agreement') ?></p>
    <?php endif; ?>
  </div>

  <!-- Submit -->
  <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white
                 font-semibold py-3 px-6 rounded-xl transition-colors focus:outline-none
                 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <?= e(__t('register_btn')) ?>
  </button>
</form>

<!-- Login link -->
<p class="mt-6 text-center text-sm text-slate-500">
  <?= e(__t('register_have_account')) ?>
  <a href="/login" class="ml-1 text-indigo-600 hover:text-indigo-700 font-medium">
    <?= e(__t('nav_login')) ?>
  </a>
</p>

<style>
  .tab-btn {
    color: #64748b;
  }
  .tab-btn.active {
    background-color: #ffffff;
    color: #4f46e5;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
  }
</style>

<script>
function switchTab(type) {
  const individual = document.getElementById('tab-individual');
  const corporate  = document.getElementById('tab-corporate');
  const section    = document.getElementById('section-corporate');
  const input      = document.getElementById('input-type');

  individual.classList.toggle('active', type === 'individual');
  corporate.classList.toggle('active',  type === 'corporate');

  section.style.display = type === 'corporate' ? 'block' : 'none';
  input.value = type;

  // Update required attributes so HTML5 validation is correct
  document.querySelectorAll('.corp-required').forEach(function(el) {
    el.required = (type === 'corporate');
  });
}

function togglePassword(id, btn) {
  const input = document.getElementById(id);
  input.type = input.type === 'password' ? 'text' : 'password';
}

// Initialise tab from server-provided value (handles validation-redirect repopulation)
switchTab('<?= e($activeType) ?>');
</script>
