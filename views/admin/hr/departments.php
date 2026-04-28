<div class="flex items-center justify-between mb-6">
  <div>
    <h2 class="text-xl font-bold text-slate-800"><?= e(__t('admin_departments')) ?></h2>
    <p class="text-sm text-slate-500 mt-1"><?= count($depts) ?> departments configured</p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Department list -->
  <div class="lg:col-span-2 space-y-3">
    <?php if (empty($depts)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">
      <i class="fas fa-building text-4xl mb-3 text-slate-300"></i>
      <p>No departments yet.</p>
    </div>
    <?php endif; ?>

    <?php foreach ($depts as $dept): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 flex-shrink-0">
        <i class="fas fa-building"></i>
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-slate-800"><?= e($dept['name']) ?></p>
        <p class="text-xs text-slate-400 mt-0.5"><?= e($dept['description'] ?? '') ?></p>
        <div class="flex items-center gap-3 mt-1">
          <span class="text-xs text-slate-500"><i class="fas fa-users mr-1"></i><?= (int)$dept['staff_count'] ?> staff</span>
          <span class="text-xs <?= $dept['is_active'] ? 'text-emerald-600' : 'text-slate-400' ?>">
            <?= $dept['is_active'] ? 'Active' : 'Inactive' ?>
          </span>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-shrink-0">
        <button onclick="editDept(<?= htmlspecialchars(json_encode($dept)) ?>)"
                class="text-indigo-600 hover:text-indigo-800 text-sm px-2 py-1 rounded hover:bg-indigo-50">
          <i class="fas fa-pen-to-square"></i>
        </button>
        <?php if ($dept['staff_count'] == 0): ?>
        <form method="POST" action="/admin/hr/departments/<?= (int)$dept['id'] ?>/delete"
              onsubmit="return confirm('Delete department <?= e(addslashes($dept['name'])) ?>?')" class="inline">
          <?= \Csrf::field() ?>
          <button type="submit" class="text-red-400 hover:text-red-700 text-sm px-2 py-1 rounded hover:bg-red-50">
            <i class="fas fa-trash-can"></i>
          </button>
        </form>
        <?php else: ?>
        <span class="text-slate-300 text-sm px-2 py-1" title="Cannot delete — has staff">
          <i class="fas fa-trash-can"></i>
        </span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Add / Edit form -->
  <div class="bg-white rounded-xl border border-slate-200 p-5 h-fit">
    <h3 class="font-semibold text-slate-800 mb-4" id="formTitle"><?= e(__t('admin_dept_add')) ?></h3>

    <form method="POST" action="/admin/hr/departments" id="deptForm" class="space-y-4">
      <?= \Csrf::field() ?>
      <input type="hidden" name="id" id="deptId" value="">

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_dept_name') ?> *</label>
        <input type="text" name="name" id="deptName" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_dept_desc') ?></label>
        <textarea name="description" id="deptDesc" rows="3"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none resize-none"></textarea>
      </div>

      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" id="deptActive" value="1" checked
               class="w-4 h-4 rounded border-slate-300 text-indigo-600">
        <span class="text-sm font-medium text-slate-700">Active</span>
      </label>

      <button type="submit"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg text-sm font-semibold transition">
        <?= __t('btn_save') ?>
      </button>
      <button type="button" onclick="resetForm()"
              class="w-full text-slate-500 hover:text-slate-800 py-2 text-sm font-medium">
        <?= __t('btn_cancel') ?>
      </button>
    </form>
  </div>
</div>

<script>
function editDept(dept) {
  document.getElementById('formTitle').textContent = '<?= __t('btn_edit') ?>';
  document.getElementById('deptId').value      = dept.id;
  document.getElementById('deptName').value    = dept.name;
  document.getElementById('deptDesc').value    = dept.description ?? '';
  document.getElementById('deptActive').checked = !!parseInt(dept.is_active);
  document.getElementById('deptForm').scrollIntoView({ behavior: 'smooth' });
}
function resetForm() {
  document.getElementById('formTitle').textContent = '<?= __t('admin_dept_add') ?>';
  document.getElementById('deptId').value = '';
  document.getElementById('deptForm').reset();
}
</script>
