<!-- Admin dashboard stats grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
  <?php
  $cards = [
    ['label'=>'Open Tickets',       'value'=>$stats['open']      ?? 0,  'icon'=>'fa-ticket',        'color'=>'indigo'],
    ['label'=>'Unassigned (Pool)',   'value'=>$unassignedCount,          'icon'=>'fa-inbox',         'color'=>'amber'],
    ['label'=>'SLA Violations',      'value'=>$slaViolations,            'icon'=>'fa-triangle-exclamation','color'=>'red'],
    ['label'=>'FAQ Deflections (7d)','value'=>$faqDeflections,           'icon'=>'fa-circle-question','color'=>'emerald'],
  ];
  $colors = [
    'indigo'  => ['bg'=>'bg-indigo-50',  'text'=>'text-indigo-600',  'icon'=>'text-indigo-500'],
    'amber'   => ['bg'=>'bg-amber-50',   'text'=>'text-amber-700',   'icon'=>'text-amber-500'],
    'red'     => ['bg'=>'bg-red-50',     'text'=>'text-red-600',     'icon'=>'text-red-500'],
    'emerald' => ['bg'=>'bg-emerald-50', 'text'=>'text-emerald-700', 'icon'=>'text-emerald-500'],
  ];
  foreach ($cards as $card):
    $c = $colors[$card['color']];
  ?>
  <div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-center justify-between mb-3">
      <div class="w-10 h-10 rounded-lg <?= $c['bg'] ?> flex items-center justify-center">
        <i class="fas <?= $card['icon'] ?> <?= $c['icon'] ?>"></i>
      </div>
    </div>
    <p class="text-2xl font-bold <?= $c['text'] ?>"><?= (int)$card['value'] ?></p>
    <p class="text-sm text-slate-500 mt-1"><?= e($card['label']) ?></p>
  </div>
  <?php endforeach; ?>
</div>

<!-- Recent Tickets -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
    <h3 class="font-semibold text-slate-800">Recent Tickets</h3>
    <a href="/admin/tickets" class="text-sm text-indigo-600 hover:underline">View all →</a>
  </div>
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-slate-50 text-slate-500 text-left text-xs">
        <th class="px-5 py-2 font-semibold">Ticket #</th>
        <th class="px-5 py-2 font-semibold">Subject</th>
        <th class="px-5 py-2 font-semibold">Client</th>
        <th class="px-5 py-2 font-semibold">Type</th>
        <th class="px-5 py-2 font-semibold">Priority</th>
        <th class="px-5 py-2 font-semibold">Status</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($recentTickets as $t): ?>
      <tr class="hover:bg-slate-50/70">
        <td class="px-5 py-3 font-mono text-xs text-slate-500"><?= e($t['ticket_number']) ?></td>
        <td class="px-5 py-3 font-medium text-slate-800 max-w-[200px] truncate"><?= e($t['subject']) ?></td>
        <td class="px-5 py-3 text-slate-500"><?= e($t['first_name'] . ' ' . $t['last_name']) ?></td>
        <td class="px-5 py-3">
          <?php $typeColor = ['onsite'=>'blue','remote'=>'purple','device'=>'orange'][$t['type']] ?? 'slate'; ?>
          <span class="px-2 py-0.5 rounded-full text-[11px] bg-<?= $typeColor ?>-100 text-<?= $typeColor ?>-700 font-medium"><?= e(ucfirst($t['type'])) ?></span>
        </td>
        <td class="px-5 py-3">
          <?php $pColor = ['urgent'=>'red','high'=>'orange','medium'=>'amber','low'=>'slate'][$t['priority']] ?? 'slate'; ?>
          <span class="px-2 py-0.5 rounded-full text-[11px] bg-<?= $pColor ?>-100 text-<?= $pColor ?>-700 font-medium"><?= e(ucfirst($t['priority'])) ?></span>
        </td>
        <td class="px-5 py-3">
          <span class="px-2 py-0.5 rounded-full text-[11px] bg-slate-100 text-slate-600 font-medium"><?= e(ucfirst(str_replace('_',' ',$t['status']))) ?></span>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($recentTickets)): ?>
      <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No tickets yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
