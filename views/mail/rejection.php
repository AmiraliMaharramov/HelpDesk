<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Status Update</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#f1f5f9; margin:0; padding:32px 16px; color:#334155; }
  .wrap { max-width:600px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); }
  .hdr  { background:linear-gradient(135deg,#0f172a,#334155); padding:40px; text-align:center; }
  .hdr h1 { color:#fff; margin:0; font-size:24px; font-weight:700; }
  .hdr p  { color:#cbd5e1; margin:8px 0 0; font-size:14px; }
  .body { padding:40px; }
  .body p { font-size:15px; line-height:1.7; margin:0 0 16px; }
  .box  { background:#fef9f0; border:1px solid #fed7aa; border-radius:10px; padding:20px 24px; margin:24px 0; }
  .box p { margin:0; font-size:14px; color:#92400e; }
  .ftr  { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1><?= e($company ?? 'QuickFixDesk') ?></h1>
    <p>Application Status Update</p>
  </div>
  <div class="body">
    <p>Dear <strong><?= e($name ?? 'Applicant') ?></strong>,</p>
    <p>Thank you very much for your interest in joining the team at <strong><?= e($company ?? 'QuickFixDesk') ?></strong> and for taking the time to go through our selection process.</p>
    <p>After careful consideration, we regret to inform you that we will not be moving forward with your application at this time. This decision was not easy, as we had many strong candidates.</p>
    <div class="box">
      <p><strong>Please note:</strong> This decision is based on our current organisational needs and does not reflect negatively on your qualifications or abilities.</p>
    </div>
    <p>We encourage you to apply for future openings that match your skills. We will keep your profile on file and may reach out should a suitable opportunity arise.</p>
    <p>We wish you every success in your job search and future career.</p>
    <p>Kind regards,<br><strong><?= e($company ?? 'QuickFixDesk') ?> Recruitment Team</strong></p>
  </div>
  <div class="ftr">
    <p>This is an automated notification. Please do not reply directly to this email.</p>
    <p>© <?= date('Y') ?> <?= e($company ?? 'QuickFixDesk') ?>. All rights reserved.</p>
  </div>
</div>
</body>
</html>
