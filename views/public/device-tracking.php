<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Device Repair Status</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800">

<header class="bg-white border-b border-slate-200 py-4 px-6 flex items-center gap-3">
  <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
    <i class="fas fa-bolt text-white text-sm"></i>
  </div>
  <span class="font-bold text-slate-800 text-lg">Device Repair Status</span>
</header>

<main class="max-w-2xl mx-auto px-4 py-10">

  <!-- Device info card -->
  <div class="bg-white rounded-2xl shadow-md border border-slate-200 overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-6 py-5 text-white">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-1">Ticket</p>
          <h1 class="text-2xl font-bold"><?= e($device['ticket_number']) ?></h1>
        </div>
        <?php
        $statusColors = [
          'received'               => 'bg-slate-400',
          'in_review'              => 'bg-amber-400',
          'awaiting_parts'         => 'bg-orange-500',
          'repaired'               => 'bg-emerald-500',
          'quality_check'          => 'bg-blue-500',
          'delivered'              => 'bg-green-600',
          'pending_price_approval' => 'bg-yellow-500',
          'cancelled'              => 'bg-red-500',
        ];
        $sc = $statusColors[$device['repair_status']] ?? 'bg-slate-400';
        ?>
        <span class="px-4 py-1.5 rounded-full text-sm font-bold bg-white/20">
          <?= e(ucwords(str_replace('_', ' ', $device['repair_status']))) ?>
        </span>
      </div>
    </div>

    <div class="px-6 py-5 grid grid-cols-2 gap-4 text-sm">
      <div>
        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Device</p>
        <p class="font-semibold"><?= e($device['device_type'] ?? '—') ?> <?= e($device['brand'] ?? '') ?></p>
        <p class="text-slate-500"><?= e($device['model'] ?? '') ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Serial Number</p>
        <p class="font-mono font-semibold"><?= e($device['serial_number'] ?? '—') ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Received</p>
        <p class="font-semibold"><?= e(date('M d, Y', strtotime($device['received_at']))) ?></p>
      </div>
      <?php if ($device['repaired_at']): ?>
      <div>
        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Repaired</p>
        <p class="font-semibold text-emerald-700"><?= e(date('M d, Y', strtotime($device['repaired_at']))) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Status timeline -->
  <?php
  $allStatuses = [
    'received'               => ['icon'=>'fa-box-open',        'label'=>'Received'],
    'in_review'              => ['icon'=>'fa-magnifying-glass', 'label'=>'In Review'],
    'awaiting_parts'         => ['icon'=>'fa-clock',            'label'=>'Awaiting Parts'],
    'repaired'               => ['icon'=>'fa-screwdriver-wrench','label'=>'Repaired'],
    'quality_check'          => ['icon'=>'fa-clipboard-check',  'label'=>'Quality Check'],
    'delivered'              => ['icon'=>'fa-handshake',         'label'=>'Ready for Pickup'],
  ];
  $currentRepairStatus = $device['repair_status'];
  $statusKeys = array_keys($allStatuses);
  $currentIdx = array_search($currentRepairStatus, $statusKeys);
  ?>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
    <h2 class="font-semibold text-slate-700 text-sm uppercase tracking-wider mb-6">Repair Progress</h2>
    <div class="space-y-0">
      <?php foreach ($allStatuses as $key => $s):
        $idx     = array_search($key, $statusKeys);
        $done    = $idx < $currentIdx;
        $current = $idx === $currentIdx;
        $pending = $idx > $currentIdx;
      ?>
      <div class="flex items-start gap-4 <?= !$pending ? '' : 'opacity-40' ?>">
        <div class="flex flex-col items-center">
          <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0
                      <?= $done ? 'bg-emerald-100 text-emerald-700' : ($current ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-slate-100 text-slate-400') ?>">
            <i class="fas <?= $s['icon'] ?> text-sm"></i>
          </div>
          <?php if ($key !== 'delivered'): ?>
          <div class="w-0.5 h-8 <?= $done ? 'bg-emerald-200' : 'bg-slate-200' ?> mt-1"></div>
          <?php endif; ?>
        </div>
        <div class="pb-8">
          <p class="font-semibold text-sm <?= $current ? 'text-indigo-700' : ($done ? 'text-slate-700' : 'text-slate-400') ?>">
            <?= e($s['label']) ?>
            <?php if ($current): ?>
            <span class="ml-2 inline-flex items-center gap-1 text-[11px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-semibold">
              <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-pulse"></span> Current
            </span>
            <?php endif; ?>
          </p>
          <?php if ($done || $current):
            // Find matching history entry
            foreach ($history as $h):
              if (str_contains(strtolower($h['new_status'] ?? ''), str_replace('_', ' ', $key))):
          ?>
          <p class="text-xs text-slate-400 mt-0.5"><?= e(date('M d, Y H:i', strtotime($h['created_at']))) ?></p>
          <?php break; endif; endforeach; endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Info note -->
  <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-sm text-indigo-700 text-center">
    <i class="fas fa-shield-halved mr-2"></i>
    This page is secured. Only someone with the device QR code can view this status.
  </div>
</main>
</body>
</html>
