<?php
/**
 * QuickFixDesk — AdminController
 *
 * Covers:
 *  § 1  FAQ Management     (cmsFaqs, saveFaq, editFaqForm, editFaq, deleteFaq)
 *  § 2  HR Departments     (departments, saveDepartment, deleteDepartment)
 *  § 3  Staff Lifecycle    (staff, addStaff, toggleStaff, rejectStaff)
 *  § 4  Permission Matrix  (permissions, savePermissions)
 *  § 5  Stubs / placeholders for all other admin routes
 */

require_once APP_PATH . '/Controllers/Controller.php';
require_once APP_PATH . '/Models/Faq.php';
require_once APP_PATH . '/Models/Department.php';
require_once APP_PATH . '/Models/Ticket.php';
require_once APP_PATH . '/Models/User.php';
require_once APP_PATH . '/Helpers/Csrf.php';
require_once APP_PATH . '/Helpers/Mailer.php';

class AdminController extends Controller
{
    // ─── § 0  Bootstrap ──────────────────────────────────────────────────────

    private function boot(): void
    {
        $this->requireAuth('admin');
    }

    // ─── § 1  FAQ Management ─────────────────────────────────────────────────

    /** GET /admin/cms/faqs */
    public function cmsFaqs(): void
    {
        $this->boot();
        $faqs       = Faq::all();
        $deflections = Faq::totalDeflectionsThisWeek();

        // Enrich each FAQ with per-item deflection count
        foreach ($faqs as &$faq) {
            $faq['deflections'] = Faq::deflectionCount((int)$faq['id']);
        }
        unset($faq);

        $this->view('admin/faqs/index', [
            'pageTitle'    => __t('admin_faq_title'),
            'faqs'         => $faqs,
            'totalDeflect' => $deflections,
            'success'      => $this->getFlash('success'),
            'error'        => $this->getFlash('error'),
        ], 'admin');
    }

    /** POST /admin/cms/faqs  (create new) */
    public function saveFaq(): void
    {
        $this->boot();
        Csrf::verify();

        $errors = $this->validateFaqInput($_POST);
        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/admin/cms/faqs');
            return;
        }

        $id = Faq::create([
            'question'   => clean($_POST['question']),
            'answer'     => clean($_POST['answer']),
            'category'   => clean($_POST['category'] ?? ''),
            'lang'       => clean($_POST['lang']     ?? 'en'),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ]);

        audit('faq_created', 'cms', 'faq', $id, null, ['question' => clean($_POST['question'])]);
        $this->flash('success', __t('admin_faq_saved'));
        $this->redirect('/admin/cms/faqs');
    }

    /** GET /admin/cms/faqs/:id/edit */
    public function editFaqForm(int $id): void
    {
        $this->boot();
        $faq = Faq::find($id);
        if (!$faq) {
            $this->flash('error', __t('msg_not_found'));
            $this->redirect('/admin/cms/faqs');
            return;
        }

        $this->view('admin/faqs/form', [
            'pageTitle' => __t('admin_faq_edit'),
            'faq'       => $faq,
            'errors'    => [],
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
        ], 'admin');
    }

    /** POST /admin/cms/faqs/:id/edit */
    public function editFaq(int $id): void
    {
        $this->boot();
        Csrf::verify();

        $faq = Faq::find($id);
        if (!$faq) {
            $this->redirect('/admin/cms/faqs');
            return;
        }

        $errors = $this->validateFaqInput($_POST);
        if (!empty($errors)) {
            $this->view('admin/faqs/form', [
                'pageTitle' => __t('admin_faq_edit'),
                'faq'       => array_merge($faq, $_POST),
                'errors'    => $errors,
                'success'   => null,
                'error'     => null,
            ], 'admin');
            return;
        }

        $new = [
            'question'   => clean($_POST['question']),
            'answer'     => clean($_POST['answer']),
            'category'   => clean($_POST['category'] ?? ''),
            'lang'       => clean($_POST['lang']     ?? 'en'),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];

        Faq::update($id, $new);
        audit('faq_updated', 'cms', 'faq', $id, $faq, $new);
        $this->flash('success', __t('admin_faq_saved'));
        $this->redirect('/admin/cms/faqs');
    }

    /** POST /admin/cms/faqs/:id/delete */
    public function deleteFaq(int $id): void
    {
        $this->boot();
        Csrf::verify();
        $faq = Faq::find($id);
        Faq::delete($id);
        audit('faq_deleted', 'cms', 'faq', $id, $faq);
        $this->flash('success', __t('admin_faq_deleted'));
        $this->redirect('/admin/cms/faqs');
    }

    private function validateFaqInput(array $post): array
    {
        $errors = [];
        if (empty(trim($post['question'] ?? ''))) {
            $errors[] = __t('admin_faq_question') . ': ' . __t('validation_required');
        }
        if (empty(trim($post['answer'] ?? ''))) {
            $errors[] = __t('admin_faq_answer') . ': ' . __t('validation_required');
        }
        return $errors;
    }

    // ─── § 2  HR — Departments ───────────────────────────────────────────────

    /** GET /admin/hr/departments */
    public function departments(): void
    {
        $this->boot();
        $depts = Department::all();
        foreach ($depts as &$d) {
            $d['staff_count'] = Department::staffCount((int)$d['id']);
        }
        unset($d);

        $this->view('admin/hr/departments', [
            'pageTitle' => __t('admin_departments'),
            'depts'     => $depts,
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
        ], 'admin');
    }

    /** POST /admin/hr/departments  (create / update) */
    public function saveDepartment(): void
    {
        $this->boot();
        Csrf::verify();

        $id   = (int)($_POST['id'] ?? 0);
        $name = clean($_POST['name'] ?? '');

        if (empty($name)) {
            $this->flash('error', __t('admin_dept_name') . ': ' . __t('validation_required'));
            $this->redirect('/admin/hr/departments');
            return;
        }

        $data = [
            'name'        => $name,
            'description' => clean($_POST['description'] ?? ''),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id > 0) {
            $old = Department::find($id);
            Department::update($id, $data);
            audit('dept_updated', 'hr', 'department', $id, $old, $data);
        } else {
            $id = Department::create($data);
            audit('dept_created', 'hr', 'department', $id, null, $data);
        }

        $this->flash('success', __t('admin_dept_saved'));
        $this->redirect('/admin/hr/departments');
    }

    /** POST /admin/hr/departments/:id/delete */
    public function deleteDepartment(int $id): void
    {
        $this->boot();
        Csrf::verify();

        $ok = Department::delete($id);
        if (!$ok) {
            $this->flash('error', __t('admin_dept_has_staff'));
        } else {
            audit('dept_deleted', 'hr', 'department', $id);
            $this->flash('success', __t('admin_dept_deleted'));
        }
        $this->redirect('/admin/hr/departments');
    }

    // ─── § 3  Staff Lifecycle ────────────────────────────────────────────────

    /** GET /admin/staff */
    public function staff(): void
    {
        $this->boot();
        $staff = db()->query(
            "SELECT u.*, r.name AS role_name, d.name AS dept_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.role_id IN (1,2)
             ORDER BY u.is_active DESC, u.first_name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $depts = Department::allActive();

        $this->view('admin/hr/staff', [
            'pageTitle' => __t('admin_staff_add'),
            'staff'     => $staff,
            'depts'     => $depts,
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
        ], 'admin');
    }

    /** POST /admin/staff  (add new staff member) */
    public function addStaff(): void
    {
        $this->boot();
        Csrf::verify();

        $firstName = clean($_POST['first_name'] ?? '');
        $lastName  = clean($_POST['last_name']  ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $roleId    = (int)($_POST['role_id']    ?? 2);
        $deptId    = (int)($_POST['department_id'] ?? 0);
        $workStart = clean($_POST['work_start'] ?? '09:00:00');
        $workEnd   = clean($_POST['work_end']   ?? '18:00:00');

        if (!$firstName || !$lastName || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', __t('msg_error'));
            $this->redirect('/admin/staff');
            return;
        }

        // Check email uniqueness
        $dup = db()->prepare("SELECT id FROM users WHERE email=:e");
        $dup->execute([':e' => $email]);
        if ($dup->fetch()) {
            $this->flash('error', __t('auth_email_taken'));
            $this->redirect('/admin/staff');
            return;
        }

        // Generate temporary password
        $tmpPass = bin2hex(random_bytes(5));   // 10-char hex
        $hash    = password_hash($tmpPass, PASSWORD_BCRYPT);

        $db = db();
        $db->prepare(
            "INSERT INTO users
             (role_id, department_id, first_name, last_name, email,
              password_hash, work_start, work_end, is_active)
             VALUES
             (:rid, :did, :fn, :ln, :email,
              :hash, :ws, :we, 1)"
        )->execute([
            ':rid'   => in_array($roleId, [1, 2]) ? $roleId : 2,
            ':did'   => $deptId > 0 ? $deptId : null,
            ':fn'    => $firstName,
            ':ln'    => $lastName,
            ':email' => $email,
            ':hash'  => $hash,
            ':ws'    => $workStart,
            ':we'    => $workEnd,
        ]);
        $newId = (int)$db->lastInsertId();

        audit('staff_added', 'hr', 'user', $newId, null, ['email' => $email]);

        $this->flash('success', __t('admin_staff_added') . ' — ' . __t('admin_staff_temp_pass') . ": {$tmpPass}");
        $this->redirect('/admin/staff');
    }

    /** POST /admin/staff/:id/toggle  (activate / deactivate) */
    public function toggleStaff(int $id): void
    {
        $this->boot();
        Csrf::verify();

        $row = db()->prepare("SELECT * FROM users WHERE id=:id AND role_id IN (1,2)");
        $row->execute([':id' => $id]);
        $user = $row->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $this->redirect('/admin/staff');
            return;
        }

        $newActive = $user['is_active'] ? 0 : 1;
        db()->prepare("UPDATE users SET is_active=:a WHERE id=:id")
            ->execute([':a' => $newActive, ':id' => $id]);

        if (!$newActive) {
            // Auto-pool tickets
            $pooled = Ticket::poolTicketsOf($id);
            audit('staff_deactivated', 'hr', 'user', $id, ['is_active' => 1], ['is_active' => 0, 'tickets_pooled' => $pooled]);

            // Send departure email
            Mailer::template('departure', [
                'name'    => $user['first_name'] . ' ' . $user['last_name'],
                'company' => defined('APP_NAME') ? APP_NAME : 'QuickFixDesk',
            ], 'Your account has been deactivated', $user['email']);

            $this->flash('success', __t('admin_staff_deactivated'));
        } else {
            audit('staff_activated', 'hr', 'user', $id, ['is_active' => 0], ['is_active' => 1]);
            $this->flash('success', __t('admin_staff_activated'));
        }

        $this->redirect('/admin/staff');
    }

    /** POST /admin/staff/:id/reject  (send rejection email) */
    public function rejectStaff(int $id): void
    {
        $this->boot();
        Csrf::verify();

        $row = db()->prepare("SELECT * FROM users WHERE id=:id");
        $row->execute([':id' => $id]);
        $user = $row->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            Mailer::template('rejection', [
                'name'    => $user['first_name'] . ' ' . $user['last_name'],
                'company' => defined('APP_NAME') ? APP_NAME : 'QuickFixDesk',
            ], 'Application Status Update', $user['email']);

            audit('staff_rejected', 'hr', 'user', $id);
            $this->flash('success', __t('admin_staff_rejection_sent'));
        }

        $this->redirect('/admin/staff');
    }

    // ─── § 4  Permission Matrix ──────────────────────────────────────────────

    /** GET /admin/staff/permissions */
    public function permissions(): void
    {
        $this->boot();

        $roles = db()->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $perms = db()->query("SELECT * FROM permissions ORDER BY module ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Build granted map: [role_id][perm_id] => bool
        $grantedMap = [];
        $rows = db()->query("SELECT role_id, permission_id, granted FROM role_permissions")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $grantedMap[$r['role_id']][$r['permission_id']] = (bool)$r['granted'];
        }

        $this->view('admin/hr/permissions', [
            'pageTitle'  => __t('admin_perm_title'),
            'roles'      => $roles,
            'perms'      => $perms,
            'grantedMap' => $grantedMap,
            'success'    => $this->getFlash('success'),
            'error'      => $this->getFlash('error'),
        ], 'admin');
    }

    /** POST /admin/staff/permissions */
    public function savePermissions(): void
    {
        $this->boot();
        Csrf::verify();

        $db      = db();
        $roles   = $db->query("SELECT id FROM roles")->fetchAll(PDO::FETCH_COLUMN);
        $perms   = $db->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        $granted = $_POST['perm'] ?? [];  // perm[role_id][perm_id] = '1'

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO role_permissions (role_id, permission_id, granted)
                 VALUES (:rid, :pid, :g)
                 ON DUPLICATE KEY UPDATE granted=VALUES(granted)"
            );
            foreach ($roles as $roleId) {
                foreach ($perms as $permId) {
                    $stmt->execute([
                        ':rid' => $roleId,
                        ':pid' => $permId,
                        ':g'   => isset($granted[$roleId][$permId]) ? 1 : 0,
                    ]);
                }
            }
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            $this->flash('error', __t('msg_error'));
            $this->redirect('/admin/staff/permissions');
            return;
        }

        audit('permissions_saved', 'hr');
        $this->flash('success', __t('admin_perm_saved'));
        $this->redirect('/admin/staff/permissions');
    }

    // ─── § 5  Stub / placeholder methods ────────────────────────────────────

    private function stub(string $label): void
    {
        $this->boot();
        $this->view('admin/stub', ['pageTitle' => $label, 'label' => $label], 'admin');
    }

    public function dashboard(): void        { $this->adminDashboard(); }
    public function users(): void            { $this->stub('Users'); }
    public function viewUser(int $id): void  { $this->stub('View User #' . $id); }
    public function updateUser(int $id): void{ $this->redirect('/admin/users'); }
    public function toggleUser(int $id): void{ $this->redirect('/admin/users'); }
    public function tickets(): void          { $this->stub('Tickets'); }
    public function devices(): void          { $this->stub('Devices'); }
    public function products(): void         { $this->stub('Products'); }
    public function newProduct(): void       { $this->stub('New Product'); }
    public function createProduct(): void    { $this->redirect('/admin/products'); }
    public function editProduct(int $id): void   { $this->stub('Edit Product #' . $id); }
    public function updateProduct(int $id): void { $this->redirect('/admin/products'); }
    public function deleteProduct(int $id): void { $this->redirect('/admin/products'); }
    public function orders(): void           { $this->stub('Orders'); }
    public function viewOrder(int $id): void { $this->stub('Order #' . $id); }
    public function invoices(): void         { $this->stub('Invoices'); }
    public function viewInvoice(int $id): void { $this->stub('Invoice #' . $id); }
    public function campaigns(): void        { $this->stub('Campaigns'); }
    public function saveCampaign(): void     { $this->redirect('/admin/campaigns'); }
    public function cmsPages(): void         { $this->stub('CMS Pages'); }
    public function cmsSliders(): void       { $this->stub('CMS Sliders'); }
    public function cmsMenus(): void         { $this->stub('CMS Menus'); }
    public function cmsKb(): void            { $this->stub('Knowledge Base'); }
    public function routingRules(): void     { $this->stub('Routing Rules'); }
    public function cannedResponses(): void  { $this->stub('Canned Responses'); }
    public function reports(): void          { $this->stub('Reports'); }
    public function auditLogs(): void        { $this->stub('Audit Logs'); }
    public function settings(): void         { $this->stub('Settings'); }
    public function saveSettings(): void     { $this->redirect('/admin/settings'); }
    public function notifications(): void    { $this->stub('Notifications'); }

    // ─── Admin dashboard ─────────────────────────────────────────────────────

    private function adminDashboard(): void
    {
        $this->boot();

        $stats = Ticket::countByStatus();
        $slaVio = Ticket::slaViolations();
        $unassigned = count(Ticket::getUnassigned());
        $totalFaqDeflections = Faq::totalDeflectionsThisWeek();

        $recentTickets = db()->query(
            "SELECT t.*, u.first_name, u.last_name
             FROM tickets t
             JOIN users u ON u.id = t.client_id
             ORDER BY t.created_at DESC LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/dashboard', [
            'pageTitle'          => __t('admin_panel'),
            'stats'              => $stats,
            'slaViolations'      => $slaVio,
            'unassignedCount'    => $unassigned,
            'faqDeflections'     => $totalFaqDeflections,
            'recentTickets'      => $recentTickets,
        ], 'admin');
    }
}
