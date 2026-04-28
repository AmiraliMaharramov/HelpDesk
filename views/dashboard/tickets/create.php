<?php
/**
 * QuickFixDesk — Multi-step Ticket Creation Form
 * Step 1: Type + Subject + Description (with FAQ widget)
 * Step 2: Type-specific details
 * Step 3: Review + Submit
 */
?>
<div class="max-w-3xl mx-auto">

  <!-- Page header -->
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800"><?= e(__t('ticket_new')) ?></h1>
    <p class="text-slate-500 text-sm mt-1"><?= __t('ticket_new_sub') ?></p>
  </div>

  <!-- Progress tracker -->
  <div class="flex items-center mb-8" id="progressBar">
    <?php $steps = [__t('ticket_step1'), __t('ticket_step2'), __t('ticket_step3')]; ?>
    <?php foreach ($steps as $i => $label): ?>
      <?php $n = $i + 1; ?>
      <div class="flex items-center <?= $i < count($steps) - 1 ? 'flex-1' : '' ?>">
        <div class="flex items-center gap-2">
          <div class="step-circle w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                      <?= $n === 1 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500' ?>"
               data-step="<?= $n ?>">
            <?= $n ?>
          </div>
          <span class="text-sm font-medium <?= $n === 1 ? 'text-indigo-700' : 'text-slate-400' ?> step-label hidden sm:block"
                data-step="<?= $n ?>"><?= e($label) ?></span>
        </div>
        <?php if ($i < count($steps) - 1): ?>
        <div class="flex-1 h-0.5 mx-3 step-bar bg-slate-200" data-after="<?= $n ?>"></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Validation errors (server-side) -->
  <?php if (!empty($errors)): ?>
  <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 text-sm text-red-700">
    <p class="font-semibold mb-1"><i class="fas fa-exclamation-circle mr-1"></i><?= __t('msg_error') ?></p>
    <?php foreach ($errors as $f => $msg): ?>
    <p class="ml-4">• <?= e($msg) ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="/tickets/new" id="ticketForm">
    <?= \Csrf::field() ?>

    <!-- ── Step 1 ────────────────────────────────────────────────────────── -->
    <div id="step-1" class="step-panel space-y-5">

      <!-- Type selection -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-3"><?= __t('ticket_type') ?> *</label>
        <div class="grid grid-cols-3 gap-3" id="typeCards">
          <?php
          $types = [
            'onsite' => ['icon'=>'fa-house-chimney','title'=>__t('ticket_type_onsite'),'desc'=>__t('ticket_type_onsite_desc'),'color'=>'indigo'],
            'remote' => ['icon'=>'fa-desktop','title'=>__t('ticket_type_remote'),'desc'=>__t('ticket_type_remote_desc'),'color'=>'purple'],
            'device' => ['icon'=>'fa-microchip','title'=>__t('ticket_type_device'),'desc'=>__t('ticket_type_device_desc'),'color'=>'orange'],
          ];
          foreach ($types as $val => $t):
            $selected = (isset($old['type']) && $old['type'] === $val);
          ?>
          <label class="type-card cursor-pointer rounded-xl border-2 p-4 text-center transition-all
                        <?= $selected ? "border-{$t['color']}-500 bg-{$t['color']}-50" : 'border-slate-200 hover:border-slate-300 bg-white' ?>"
                 for="type_<?= $val ?>">
            <input type="radio" name="type" id="type_<?= $val ?>" value="<?= $val ?>"
                   <?= $selected ? 'checked' : '' ?> class="sr-only">
            <i class="fas <?= $t['icon'] ?> text-2xl text-<?= $t['color'] ?>-500 mb-2 block"></i>
            <p class="font-semibold text-sm text-slate-800"><?= e($t['title']) ?></p>
            <p class="text-xs text-slate-400 mt-0.5"><?= e($t['desc']) ?></p>
          </label>
          <?php endforeach; ?>
        </div>
        <?php if (hasError($errors, 'type')): ?>
        <p class="text-xs text-red-600 mt-1"><?= fieldError($errors, 'type') ?></p>
        <?php endif; ?>
      </div>

      <!-- Category -->
      <?php if (!empty($categories)): ?>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_category') ?></label>
        <select name="category_id" class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="">— <?= __t('ticket_category_none') ?> —</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>" <?= (($old['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <!-- Subject -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_subject') ?> *</label>
        <input type="text" name="subject" required
               value="<?= old($old, 'subject') ?>"
               placeholder="<?= e(__t('ticket_subject_placeholder')) ?>"
               class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400
                      <?= hasError($errors, 'subject') ? 'border-red-400' : '' ?>">
        <?php if (hasError($errors, 'subject')): ?>
        <p class="text-xs text-red-600 mt-1"><?= fieldError($errors, 'subject') ?></p>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_description') ?> *</label>
        <textarea name="description" id="descField" required rows="5"
                  placeholder="<?= e(__t('ticket_description_placeholder')) ?>"
                  class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400 resize-y
                         <?= hasError($errors, 'description') ? 'border-red-400' : '' ?>"><?= old($old, 'description') ?></textarea>
        <?php if (hasError($errors, 'description')): ?>
        <p class="text-xs text-red-600 mt-1"><?= fieldError($errors, 'description') ?></p>
        <?php endif; ?>
      </div>

      <!-- Priority -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_priority') ?></label>
        <div class="flex gap-3 flex-wrap">
          <?php foreach (['low'=>'slate','medium'=>'amber','high'=>'orange','urgent'=>'red'] as $p=>$pc): ?>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="priority" value="<?= $p ?>"
                   <?= (($old['priority'] ?? 'medium') === $p) ? 'checked' : '' ?>
                   class="text-indigo-600">
            <span class="text-sm font-medium text-<?= $pc ?>-600"><?= ucfirst($p) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- FAQ Widget (auto-searches as user types description) -->
      <?php require VIEW_PATH . '/partials/faq-widget.php'; ?>

      <!-- Next button -->
      <div class="flex justify-end">
        <button type="button" id="toStep2Btn"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">
          <?= __t('btn_next') ?> <i class="fas fa-arrow-right ml-1"></i>
        </button>
      </div>
    </div>

    <!-- ── Step 2 ────────────────────────────────────────────────────────── -->
    <div id="step-2" class="step-panel hidden space-y-5">

      <!-- On-site fields -->
      <div id="fields-onsite" class="type-fields hidden space-y-4">
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-sm text-indigo-700">
          <i class="fas fa-map-marker-alt mr-1"></i> <?= __t('ticket_onsite_hint') ?>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_address') ?> *</label>
          <?php if (!empty($addresses)): ?>
          <select name="address_id" class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="">— <?= __t('ticket_select_address') ?> —</option>
            <?php foreach ($addresses as $addr): ?>
            <option value="<?= (int)$addr['id'] ?>" <?= (($old['address_id'] ?? '') == $addr['id']) ? 'selected' : '' ?>>
              <?= e($addr['label'] . ': ' . $addr['address_line1'] . ', ' . $addr['city']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <a href="/dashboard/addresses/new" target="_blank"
             class="mt-1 inline-block text-xs text-indigo-600 hover:underline">
            <i class="fas fa-plus mr-1"></i><?= __t('ticket_add_address') ?>
          </a>
          <?php else: ?>
          <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-700">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            <?= __t('ticket_no_address') ?>
            <a href="/dashboard/addresses/new" class="underline ml-1"><?= __t('ticket_add_address') ?></a>
          </div>
          <?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_device_brand') ?></label>
          <input type="text" name="device_brand_onsite"
                 value="<?= old($old, 'device_brand_onsite') ?>"
                 placeholder="e.g. Dell, HP, Cisco…"
                 class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>

      <!-- Remote fields -->
      <div id="fields-remote" class="type-fields hidden space-y-4">
        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-sm text-purple-700">
          <i class="fas fa-desktop mr-1"></i> <?= __t('ticket_remote_hint') ?>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_remote_tool') ?> *</label>
          <select name="remote_tool" class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="">— <?= __t('ticket_select_tool') ?> —</option>
            <?php foreach (['AnyDesk','TeamViewer','RustDesk','Other'] as $tool): ?>
            <option value="<?= $tool ?>" <?= (($old['remote_tool'] ?? '') === $tool) ? 'selected' : '' ?>><?= $tool ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_remote_code') ?> *</label>
          <input type="text" name="remote_code"
                 value="<?= old($old, 'remote_code') ?>"
                 placeholder="e.g. 123456789"
                 class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono outline-none focus:ring-2 focus:ring-purple-400">
          <p class="text-xs text-slate-400 mt-1"><?= __t('ticket_remote_code_hint') ?></p>
        </div>
      </div>

      <!-- Device/Hardware fields -->
      <div id="fields-device" class="type-fields hidden space-y-4">
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-700">
          <i class="fas fa-microchip mr-1"></i> <?= __t('ticket_device_hint') ?>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_device_type') ?> *</label>
            <select name="device_type" class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
              <option value="">—</option>
              <?php foreach (['Laptop','Desktop','Phone','Tablet','Printer','Server','Other'] as $dt): ?>
              <option value="<?= $dt ?>" <?= (($old['device_type'] ?? '') === $dt) ? 'selected' : '' ?>><?= $dt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_device_brand') ?></label>
            <input type="text" name="brand" value="<?= old($old, 'brand') ?>"
                   placeholder="e.g. Apple, Samsung…"
                   class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_device_model') ?></label>
            <input type="text" name="model_name" value="<?= old($old, 'model_name') ?>"
                   placeholder="e.g. MacBook Pro 2022"
                   class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_serial') ?> *</label>
            <input type="text" name="serial_number" value="<?= old($old, 'serial_number') ?>"
                   placeholder="e.g. C02X…"
                   class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm font-mono outline-none focus:ring-2 focus:ring-indigo-400">
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_condition') ?></label>
          <textarea name="condition_notes" rows="3"
                    placeholder="<?= e(__t('ticket_condition_placeholder')) ?>"
                    class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400 resize-none"><?= old($old, 'condition_notes') ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_accessories') ?></label>
            <input type="text" name="accessories" value="<?= old($old, 'accessories') ?>"
                   placeholder="e.g. charger, bag…"
                   class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('ticket_warranty_days') ?></label>
            <input type="number" name="warranty_days" value="<?= (int)($old['warranty_days'] ?? 0) ?>" min="0"
                   class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between pt-2">
        <button type="button" id="backToStep1Btn"
                class="text-slate-500 hover:text-slate-800 text-sm font-medium flex items-center gap-1">
          <i class="fas fa-arrow-left"></i> <?= __t('btn_back') ?>
        </button>
        <button type="button" id="toStep3Btn"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">
          <?= __t('btn_next') ?> <i class="fas fa-arrow-right ml-1"></i>
        </button>
      </div>
    </div>

    <!-- ── Step 3 — Review ────────────────────────────────────────────────── -->
    <div id="step-3" class="step-panel hidden">
      <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
        <h3 class="font-semibold text-slate-700 mb-4"><?= __t('ticket_review') ?></h3>
        <dl class="space-y-2 text-sm" id="reviewSummary"></dl>
      </div>

      <div class="flex items-center justify-between">
        <button type="button" id="backToStep2Btn"
                class="text-slate-500 hover:text-slate-800 text-sm font-medium flex items-center gap-1">
          <i class="fas fa-arrow-left"></i> <?= __t('btn_back') ?>
        </button>
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-2.5 rounded-xl text-sm font-bold transition shadow-md">
          <i class="fas fa-paper-plane mr-2"></i><?= __t('ticket_submit') ?>
        </button>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  let currentStep    = 1;
  let selectedType   = '<?= old($old, 'type') ?>';

  // ── Type card selection ───────────────────────────────────────────────────
  document.querySelectorAll('.type-card input[type=radio]').forEach(radio => {
    radio.addEventListener('change', () => {
      selectedType = radio.value;
      document.querySelectorAll('.type-card').forEach(c => {
        c.classList.remove('border-indigo-500','bg-indigo-50','border-purple-500','bg-purple-50','border-orange-500','bg-orange-50');
        c.classList.add('border-slate-200');
      });
      const label = radio.closest('.type-card');
      const colors = {onsite:['border-indigo-500','bg-indigo-50'],remote:['border-purple-500','bg-purple-50'],device:['border-orange-500','bg-orange-50']};
      (colors[radio.value] || []).forEach(c => label.classList.add(c));
      label.classList.remove('border-slate-200');
    });
    if (radio.checked) radio.dispatchEvent(new Event('change'));
  });

  // ── Progress bar update ───────────────────────────────────────────────────
  function updateProgress(step) {
    document.querySelectorAll('.step-circle').forEach(el => {
      const n = parseInt(el.dataset.step);
      if (n < step) {
        el.className = el.className.replace(/bg-\S+|text-\S+/, '');
        el.classList.add('bg-indigo-200', 'text-indigo-700');
        el.innerHTML = '<i class="fas fa-check text-xs"></i>';
      } else if (n === step) {
        el.className = el.className.replace(/bg-\S+|text-\S+/, '');
        el.classList.add('bg-indigo-600', 'text-white');
        el.textContent = n;
      } else {
        el.className = el.className.replace(/bg-\S+|text-\S+/, '');
        el.classList.add('bg-slate-200', 'text-slate-500');
        el.textContent = n;
      }
    });
    document.querySelectorAll('.step-bar').forEach(el => {
      const after = parseInt(el.dataset.after);
      if (after < step) el.classList.add('bg-indigo-300');
      else el.classList.remove('bg-indigo-300');
    });
  }

  // ── Step navigation ───────────────────────────────────────────────────────
  function showStep(n) {
    document.querySelectorAll('.step-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById('step-' + n).classList.remove('hidden');
    currentStep = n;
    updateProgress(n);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function showTypeFields() {
    document.querySelectorAll('.type-fields').forEach(f => f.classList.add('hidden'));
    if (selectedType) {
      document.getElementById('fields-' + selectedType)?.classList.remove('hidden');
    }
  }

  document.getElementById('toStep2Btn').addEventListener('click', () => {
    if (!selectedType) {
      alert('<?= __t("ticket_type") ?>: <?= __t("validation_required") ?>');
      return;
    }
    const subject = document.querySelector('[name=subject]').value.trim();
    if (!subject) {
      document.querySelector('[name=subject]').focus();
      return;
    }
    const desc = document.getElementById('descField').value.trim();
    if (!desc) {
      document.getElementById('descField').focus();
      return;
    }
    showTypeFields();
    showStep(2);
  });

  document.getElementById('backToStep1Btn').addEventListener('click', () => showStep(1));

  document.getElementById('toStep3Btn').addEventListener('click', () => {
    buildReview();
    showStep(3);
  });

  document.getElementById('backToStep2Btn').addEventListener('click', () => {
    showTypeFields();
    showStep(2);
  });

  // ── Review summary builder ────────────────────────────────────────────────
  function buildReview() {
    const dl = document.getElementById('reviewSummary');
    dl.innerHTML = '';
    const fields = [
      { label: '<?= __t("ticket_type") ?>',        val: selectedType },
      { label: '<?= __t("ticket_subject") ?>',      val: document.querySelector('[name=subject]').value },
      { label: '<?= __t("ticket_priority") ?>',     val: document.querySelector('[name=priority]:checked')?.value },
      { label: '<?= __t("ticket_description") ?>', val: document.getElementById('descField').value.substring(0, 120) + '…' },
    ];
    if (selectedType === 'remote') {
      fields.push({label:'<?= __t("ticket_remote_tool") ?>', val: document.querySelector('[name=remote_tool]')?.value});
      fields.push({label:'<?= __t("ticket_remote_code") ?>', val: document.querySelector('[name=remote_code]')?.value});
    }
    if (selectedType === 'device') {
      fields.push({label:'<?= __t("ticket_device_type") ?>', val: document.querySelector('[name=device_type]')?.value});
      fields.push({label:'<?= __t("ticket_serial") ?>', val: document.querySelector('[name=serial_number]')?.value});
    }
    fields.forEach(f => {
      if (!f.val) return;
      dl.insertAdjacentHTML('beforeend', `
        <div class="flex gap-2">
          <dt class="w-36 flex-shrink-0 text-slate-500 font-medium">${f.label}</dt>
          <dd class="text-slate-800 font-semibold">${escapeHtml(f.val)}</dd>
        </div>`);
    });
  }

  function escapeHtml(s) {
    return (s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  }

  // ── FAQ widget integration ────────────────────────────────────────────────
  FaqWidget.init(() => {
    // "Still need help" → advance to step 2
    if (currentStep === 1) {
      document.getElementById('toStep2Btn').click();
    }
  });

  // Auto-search when description changes
  document.getElementById('descField')?.addEventListener('input', e => {
    const val = e.target.value;
    if (val.length > 10) FaqWidget.search(val);
  });

  // Init progress
  updateProgress(1);
})();
</script>
