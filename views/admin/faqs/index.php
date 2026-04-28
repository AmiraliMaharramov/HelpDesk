<div class="flex items-center justify-between mb-6">
  <div>
    <h2 class="text-xl font-bold text-slate-800"><?= e(__t('admin_faq_title')) ?></h2>
    <p class="text-sm text-slate-500 mt-1">
      <?= __t('admin_faq_deflections') ?>:
      <span class="font-semibold text-indigo-700"><?= (int)$totalDeflect ?></span>
    </p>
  </div>
  <a href="/admin/cms/faqs/new"
     class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition">
    <i class="fas fa-plus"></i> <?= e(__t('admin_faq_add')) ?>
  </a>
</div>

<?php if (empty($faqs)): ?>
<div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-400">
  <i class="fas fa-circle-question text-4xl mb-3 text-slate-300"></i>
  <p>No FAQs yet. Click "<?= e(__t('admin_faq_add')) ?>" to create one.</p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-left">
        <th class="px-4 py-3 font-semibold">#</th>
        <th class="px-4 py-3 font-semibold"><?= __t('admin_faq_question') ?></th>
        <th class="px-4 py-3 font-semibold"><?= __t('admin_faq_category') ?></th>
        <th class="px-4 py-3 font-semibold"><?= __t('admin_faq_lang') ?></th>
        <th class="px-4 py-3 font-semibold text-center"><?= __t('admin_faq_sort') ?></th>
        <th class="px-4 py-3 font-semibold text-center"><?= __t('admin_faq_deflections') ?></th>
        <th class="px-4 py-3 font-semibold text-center"><?= __t('admin_faq_status') ?></th>
        <th class="px-4 py-3 font-semibold text-right">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($faqs as $faq): ?>
      <tr class="hover:bg-slate-50/60 transition-colors">
        <td class="px-4 py-3 text-slate-400"><?= (int)$faq['id'] ?></td>
        <td class="px-4 py-3">
          <p class="font-medium text-slate-800 line-clamp-1"><?= e($faq['question']) ?></p>
          <p class="text-xs text-slate-400 line-clamp-1 mt-0.5"><?= e(strip_tags($faq['answer'])) ?></p>
        </td>
        <td class="px-4 py-3 text-slate-500"><?= e($faq['category'] ?? '—') ?></td>
        <td class="px-4 py-3">
          <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
            <?= e(strtoupper($faq['lang'] ?? 'EN')) ?>
          </span>
        </td>
        <td class="px-4 py-3 text-center text-slate-500"><?= (int)$faq['sort_order'] ?></td>
        <td class="px-4 py-3 text-center">
          <?php $d = (int)($faq['deflections'] ?? 0); ?>
          <span class="font-semibold <?= $d > 0 ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $d ?></span>
        </td>
        <td class="px-4 py-3 text-center">
          <?php if ($faq['is_active']): ?>
            <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700 font-semibold">Active</span>
          <?php else: ?>
            <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-500 font-semibold">Inactive</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3 text-right">
          <div class="flex items-center justify-end gap-2">
            <a href="/admin/cms/faqs/<?= (int)$faq['id'] ?>/edit"
               class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold px-2 py-1 rounded hover:bg-indigo-50 transition">
              <i class="fas fa-pen-to-square"></i>
            </a>
            <form method="POST" action="/admin/cms/faqs/<?= (int)$faq['id'] ?>/delete"
                  onsubmit="return confirm('Delete this FAQ?')" class="inline">
              <?= \Csrf::field() ?>
              <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-1 rounded hover:bg-red-50 transition">
                <i class="fas fa-trash-can"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Inline "Add FAQ" form (collapsible) -->
<details class="mt-6 bg-white rounded-xl border border-slate-200">
  <summary class="px-6 py-4 cursor-pointer font-semibold text-slate-700 flex items-center gap-2">
    <i class="fas fa-plus-circle text-indigo-500"></i>
    <?= e(__t('admin_faq_add')) ?>
  </summary>
  <div class="px-6 pb-6">
    <form method="POST" action="/admin/cms/faqs" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
      <?= \Csrf::field() ?>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_faq_question') ?> *</label>
        <input type="text" name="question" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_faq_answer') ?> *</label>
        <textarea name="answer" rows="4" required
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none resize-y"></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_faq_category') ?></label>
        <input type="text" name="category" placeholder="e.g. Billing, Technical…"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_faq_lang') ?></label>
        <select name="lang" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
          <option value="en">English</option>
          <option value="tr">Türkçe</option>
          <option value="az">Azərbaycan</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_faq_sort') ?></label>
        <input type="number" name="sort_order" value="0" min="0"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
      </div>

      <div class="flex items-center gap-3 pt-6">
        <label class="flex items-center gap-2 cursor-pointer select-none">
          <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded border-slate-300 text-indigo-600">
          <span class="text-sm font-medium text-slate-700">Active</span>
        </label>
      </div>

      <div class="md:col-span-2 flex justify-end">
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-semibold transition">
          <?= __t('btn_save') ?>
        </button>
      </div>
    </form>
  </div>
</details>
