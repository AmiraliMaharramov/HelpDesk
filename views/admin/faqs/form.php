<div class="max-w-2xl">
  <div class="flex items-center gap-4 mb-6">
    <a href="/admin/cms/faqs" class="text-slate-400 hover:text-slate-700 text-sm">
      <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="text-xl font-bold text-slate-800"><?= e($pageTitle) ?></h2>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-sm text-red-700">
    <?php foreach ($errors as $err): ?><p><i class="fas fa-exclamation-circle mr-1"></i><?= e($err) ?></p><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="/admin/cms/faqs/<?= (int)($faq['id'] ?? 0) ?>/edit"
        class="bg-white rounded-xl border border-slate-200 p-6 space-y-5">
    <?= \Csrf::field() ?>

    <div>
      <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('admin_faq_question') ?> *</label>
      <input type="text" name="question" required
             value="<?= e($faq['question'] ?? '') ?>"
             class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
    </div>

    <div>
      <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('admin_faq_answer') ?> *</label>
      <textarea name="answer" rows="6" required
                class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none resize-y"><?= e($faq['answer'] ?? '') ?></textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('admin_faq_category') ?></label>
        <input type="text" name="category"
               value="<?= e($faq['category'] ?? '') ?>"
               placeholder="e.g. Billing, Technical…"
               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
      </div>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('admin_faq_lang') ?></label>
        <select name="lang" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
          <?php foreach (['en'=>'English','tr'=>'Türkçe','az'=>'Azərbaycan'] as $code=>$label): ?>
          <option value="<?= $code ?>" <?= ($faq['lang'] ?? 'en') === $code ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4 items-end">
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1"><?= __t('admin_faq_sort') ?></label>
        <input type="number" name="sort_order" min="0"
               value="<?= (int)($faq['sort_order'] ?? 0) ?>"
               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
      </div>
      <div>
        <label class="flex items-center gap-2 cursor-pointer select-none pb-2">
          <input type="checkbox" name="is_active" value="1" <?= ($faq['is_active'] ?? 1) ? 'checked' : '' ?>
                 class="w-4 h-4 rounded border-slate-300 text-indigo-600">
          <span class="text-sm font-semibold text-slate-700">Active</span>
        </label>
      </div>
    </div>

    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
      <a href="/admin/cms/faqs" class="text-sm text-slate-500 hover:text-slate-800 font-medium">
        <i class="fas fa-arrow-left mr-1"></i> <?= __t('btn_cancel') ?>
      </a>
      <button type="submit"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition">
        <?= __t('btn_save') ?>
      </button>
    </div>
  </form>
</div>
