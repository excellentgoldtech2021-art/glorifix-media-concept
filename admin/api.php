<?php
/**
 * Glorifix Admin JSON API Endpoint
 * Handles AJAX requests for saving pages, checking sessions, and uploading media
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/auth.php';

$response = ['success' => false, 'message' => 'Invalid action'];

// Require authentication for all API endpoints
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$pages = get_site_pages();

switch ($action) {
    case 'save_page':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $page_key = basename($_POST['page'] ?? '');
            $content = $_POST['content'] ?? '';

            if (isset($pages[$page_key]) && !empty($content)) {
                create_page_backup($page_key);
                if (file_put_contents($pages[$page_key]['file'], $content) !== false) {
                    $response = [
                        'success' => true,
                        'message' => "Page '{$page_key}' saved successfully!",
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to write file.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid page key or empty content.'];
            }
        }
        break;

    case 'get_page':
        $page_key = basename($_GET['page'] ?? '');
        if (isset($pages[$page_key]) && file_exists($pages[$page_key]['file'])) {
            $response = [
                'success' => true,
                'page' => $page_key,
                'content' => file_get_contents($pages[$page_key]['file'])
            ];
        } else {
            $response = ['success' => false, 'message' => 'Page not found.'];
        }
        break;

    case 'session_status':
        $config = load_config();
        $response = [
            'success' => true,
            'logged_in' => true,
            'user' => $_SESSION['admin_user'] ?? $config['username'],
            'time' => date('Y-m-d H:i:s')
        ];
        break;

    default:
        $response = ['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)];
        break;
}

echo json_encode($response);
exit;
