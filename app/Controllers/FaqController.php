<?php
/**
 * QuickFixDesk — FaqController
 * JSON API endpoints for the FAQ / AI-support widget.
 */

require_once APP_PATH . '/Controllers/Controller.php';
require_once APP_PATH . '/Models/Faq.php';

class FaqController extends Controller
{
    /**
     * GET /api/faq/search?q=...&lang=...
     *
     * Returns JSON array of matching FAQs.
     * Public endpoint — no auth required.
     */
    public function search(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $q    = clean($_GET['q']    ?? '');
        $lang = clean($_GET['lang'] ?? Lang::current());

        if (strlen($q) < 3) {
            echo json_encode(['found' => false, 'count' => 0, 'faqs' => []]);
            return;
        }

        $results = Faq::search($q, $lang, 5);

        echo json_encode([
            'found' => !empty($results),
            'count' => count($results),
            'faqs'  => array_map(fn($r) => [
                'id'       => (int)$r['id'],
                'question' => $r['question'],
                'answer'   => $r['answer'],
                'category' => $r['category'],
            ], $results),
        ]);
    }

    /**
     * POST /api/faq/deflect
     *
     * Logs a "Deflection" in audit_logs when a user marks their
     * issue as resolved via an FAQ.
     *
     * Body: faq_id=<int>  (+ optional _csrf if called from an auth'd form)
     */
    public function deflect(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $faqId = (int)($_POST['faq_id'] ?? 0);

        if ($faqId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid FAQ ID.']);
            return;
        }

        $faq = Faq::find($faqId);
        if (!$faq) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'FAQ not found.']);
            return;
        }

        audit(
            action:     'faq_deflection',
            module:     'faq',
            entityType: 'faq',
            entityId:   $faqId,
            newValues:  ['question' => $faq['question']],
            userId:     $_SESSION['user_id'] ?? null
        );

        echo json_encode([
            'success' => true,
            'message' => __t('faq_deflect_success'),
        ]);
    }
}
