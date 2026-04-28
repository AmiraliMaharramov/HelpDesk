<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Deactivation Notice</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#f1f5f9; margin:0; padding:32px 16px; color:#334155; }
  .wrap { max-width:600px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); }
  .hdr  { background:linear-gradient(135deg,#1e1b4b,#4338ca); padding:40px; text-align:center; }
  .hdr h1 { color:#fff; margin:0; font-size:24px; font-weight:700; letter-spacing:-.5px; }
  .hdr p  { color:#c7d2fe; margin:8px 0 0; font-size:14px; }
  .body { padding:40px; }
  .body p  { font-size:15px; line-height:1.7; margin:0 0 16px; }
  .label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin-bottom:4px; }
  .box  { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px 24px; margin:24px 0; }
  .ftr  { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
  .ftr a{ color:#6366f1; text-decoration:none; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1><?= e($company ?? 'QuickFixDesk') ?></h1>
    <p>Staff Account Notice</p>
  </div>
  <div class="body">
    <p>Dear <strong><?= e($name ?? 'Team Member') ?></strong>,</p>
    <p>We want to inform you that your staff account at <strong><?= e($company ?? 'QuickFixDesk') ?></strong> has been <strong>deactivated</strong> effective immediately.</p>
    <div class="box">
      <p class="label">What this means</p>
      <ul style="margin:0;padding-left:20px;font-size:14px;line-height:1.8;color:#475569;">
        <li>Your login credentials are now disabled</li>
        <li>All your active tickets have been reassigned</li>
        <li>You will no longer receive ticket notifications</li>
      </ul>
    </div>
    <p>If you believe this was done in error or need assistance, please contact your HR department or reply to this email.</p>
    <p>We appreciate your contributions to the team and wish you well in your future endeavours.</p>
    <p>Warm regards,<br><strong><?= e($company ?? 'QuickFixDesk') ?> HR Team</strong></p>
  </div>
  <div class="ftr">
    <p>This is an automated notification from <?= e($company ?? 'QuickFixDesk') ?>.</p>
    <p>Please do not reply to this email unless instructed above.</p>
  </div>
</div>
</body>
</html>
