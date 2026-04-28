<div class="flex items-center justify-between mb-6">
  <div>
    <h2 class="text-xl font-bold text-slate-800"><?= e(__t('admin_staff_add')) ?></h2>
    <p class="text-sm text-slate-500 mt-1"><?= count($staff) ?> members</p>
  </div>
  <button onclick="document.getElementById('addModal').classList.remove('hidden')"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2">
    <i class="fas fa-user-plus"></i> <?= e(__t('admin_staff_add')) ?>
  </button>
</div>

<!-- Staff table -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-left">
        <th class="px-4 py-3 font-semibold">Staff Member</th>
        <th class="px-4 py-3 font-semibold"><?= __t('admin_staff_role') ?></th>
        <th class="px-4 py-3 font-semibold"><?= __t('admin_staff_dept') ?></th>
        <th class="px-4 py-3 font-semibold">Hours</th>
        <th class="px-4 py-3 font-semibold text-center">Status</th>
        <th class="px-4 py-3 font-semibold text-right">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($staff as $s): ?>
      <tr class="hover:bg-slate-50/60 <?= !$s['is_active'] ? 'opacity-60' : '' ?>">
        <td class="px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
              <?= strtoupper(substr($s['first_name'],0,1) . substr($s['last_name'],0,1)) ?>
            </div>
            <div>
              <p class="font-medium text-slate-800"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></p>
              <p class="text-xs text-slate-400"><?= e($s['email']) ?></p>
            </div>
          </div>
        </td>
        <td class="px-4 py-3 text-slate-600"><?= e($s['role_name']) ?></td>
        <td class="px-4 py-3 text-slate-500"><?= e($s['dept_name'] ?? '—') ?></td>
        <td class="px-4 py-3 text-xs text-slate-400">
          <?= e(substr($s['work_start'] ?? '09:00', 0, 5)) ?>–<?= e(substr($s['work_end'] ?? '18:00', 0, 5)) ?>
        </td>
        <td class="px-4 py-3 text-center">
          <?php if ($s['is_active']): ?>
            <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700 font-semibold">Active</span>
          <?php else: ?>
            <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-500 font-semibold">Inactive</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3 text-right">
          <div class="flex items-center justify-end gap-1">
            <!-- Toggle active -->
            <form method="POST" action="/admin/staff/<?= (int)$s['id'] ?>/toggle"
                  onsubmit="return confirm('<?= $s['is_active'] ? 'Deactivate this staff member? Their open tickets will be moved to the pool.' : 'Reactivate this staff member?' ?>')" class="inline">
              <?= \Csrf::field() ?>
              <button type="submit"
                      class="<?= $s['is_active'] ? 'text-amber-500 hover:text-amber-700' : 'text-emerald-500 hover:text-emerald-700' ?> text-xs px-2 py-1 rounded hover:bg-slate-100"
                      title="<?= $s['is_active'] ? 'Deactivate' : 'Reactivate' ?>">
                <i class="fas <?= $s['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
              </button>
            </form>
            <!-- Send rejection email -->
            <form method="POST" action="/admin/staff/<?= (int)$s['id'] ?>/reject"
                  onsubmit="return confirm('Send rejection email to <?= e(addslashes($s['first_name'])) ?>?')" class="inline">
              <?= \Csrf::field() ?>
              <button type="submit" class="text-red-400 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-slate-100"
                      title="<?= __t('admin_staff_rejection_sent') ?>">
                <i class="fas fa-envelope-circle-check"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($staff)): ?>
      <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No staff members yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Staff Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <h3 class="font-bold text-slate-800"><?= e(__t('admin_staff_add')) ?></h3>
      <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>
    <form method="POST" action="/admin/staff" class="p-6 space-y-4">
      <?= \Csrf::field() ?>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">First Name *</label>
          <input type="text" name="first_name" required
                 class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Last Name *</label>
          <input type="text" name="last_name" required
                 class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
        <input type="email" name="email" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_staff_role') ?></label>
          <select name="role_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="2">Technician</option>
            <option value="1">Administrator</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_staff_dept') ?></label>
          <select name="department_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="">— None —</option>
            <?php foreach ($depts as $dept): ?>
            <option value="<?= (int)$dept['id'] ?>"><?= e($dept['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_staff_work_start') ?></label>
          <input type="time" name="work_start" value="09:00"
                 class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"><?= __t('admin_staff_work_end') ?></label>
          <input type="time" name="work_end" value="18:00"
                 class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>

      <p class="text-xs text-slate-400 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
        <i class="fas fa-key mr-1 text-amber-500"></i>
        A temporary password will be generated and shown to you after saving.
      </p>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 font-medium"><?= __t('btn_cancel') ?></button>
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-semibold">
          <?= __t('btn_save') ?>
        </button>
      </div>
    </form>
  </div>
</div>
