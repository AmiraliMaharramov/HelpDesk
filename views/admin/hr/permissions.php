<div class="mb-6">
  <h2 class="text-xl font-bold text-slate-800"><?= e(__t('admin_perm_title')) ?></h2>
  <p class="text-sm text-slate-500 mt-1">Grant or revoke permissions per role. Changes take effect immediately.</p>
</div>

<form method="POST" action="/admin/staff/permissions">
  <?= \Csrf::field() ?>

  <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="bg-slate-50 border-b border-slate-200">
          <th class="px-4 py-3 font-semibold text-slate-700 text-left min-w-[220px]">Permission</th>
          <th class="px-4 py-3 font-semibold text-slate-600 text-xs text-center uppercase tracking-wide">Module</th>
          <?php foreach ($roles as $role): ?>
          <th class="px-4 py-3 font-semibold text-slate-700 text-center min-w-[110px]">
            <?= e($role['name']) ?>
            <span class="block text-xs font-normal text-slate-400"><?= e($role['slug']) ?></span>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php
        $currentModule = null;
        foreach ($perms as $perm):
          // Print module group header
          if ($currentModule !== $perm['module']):
            $currentModule = $perm['module'];
        ?>
        <tr class="bg-indigo-50/50">
          <td colspan="<?= 2 + count($roles) ?>" class="px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-indigo-600">
            <?= e(ucfirst($perm['module'])) ?>
          </td>
        </tr>
        <?php endif; ?>
        <tr class="hover:bg-slate-50/70">
          <td class="px-4 py-3 font-medium text-slate-800"><?= e($perm['name']) ?></td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded-full text-[11px] bg-slate-100 text-slate-500"><?= e($perm['module']) ?></span>
          </td>
          <?php foreach ($roles as $role): ?>
          <td class="px-4 py-3 text-center">
            <?php $checked = $grantedMap[$role['id']][$perm['id']] ?? false; ?>
            <label class="inline-flex items-center justify-center cursor-pointer">
              <input type="checkbox"
                     name="perm[<?= (int)$role['id'] ?>][<?= (int)$perm['id'] ?>]"
                     value="1"
                     <?= $checked ? 'checked' : '' ?>
                     class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                     <?= in_array($role['slug'], ['corporate','individual']) ? 'disabled title="Client roles cannot have staff permissions"' : '' ?>>
            </label>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="flex justify-end mt-4">
    <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-lg text-sm font-semibold transition shadow-sm">
      <i class="fas fa-floppy-disk mr-2"></i> <?= __t('btn_save') ?> Permissions
    </button>
  </div>
</form>

<div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
  <i class="fas fa-triangle-exclamation mr-1"></i>
  Client roles (Corporate, Individual) are greyed out — access for clients is controlled by subscription plan, not by this matrix.
</div>
