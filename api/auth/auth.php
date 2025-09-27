<?php
// auth.php
require __DIR__ . "/jwt_helper.php";
require __DIR__ . "/../config/connection.php";

/**
 * Attempt to authenticate the incoming request via the "token" cookie.
 * 
 * - If there is no token or it’s invalid/expired, this will send the appropriate
 *   HTTP response code + JSON error, then `exit`.
 * - Otherwise it returns the user’s database row (associative array), with the
 *   “password” field removed.
 *
 * @return array  The authenticated user record (without password field).
 */
function check_auth(): array
{
    // 1) Make sure the token cookie is present
    if (!isset($_COOKIE['token'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized: no token provided']);
        exit;
    }

    $jwt = $_COOKIE['token'];

    // 2) Decode and verify the token
    $payload = jwt_decode($jwt);
    if ($payload === false) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized: invalid or expired token']);
        exit;
    }

    // 3) The payload must have an "id" field
    if (!isset($payload['id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized: malformed token']);
        exit;
    }

    $userId = intval($payload['id']);

    // 4) Look up the user in the database
    //    (We use a prepared statement to avoid SQL injection.)
    global $conn;
    $stmt = $conn->prepare("SELECT id, email, first_name, last_name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // No such user
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized: user not found']);
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // 5) Return the user row (password is not even selected, so no need to unset)
    return $user;
}

/**
 * Helper to check if the currently authenticated user is an admin.
 * Returns true/false but also exits with 401/403 if not authenticated.
 *
 * @return bool
 */
function is_admin(): bool
{
    $user = check_auth();
    if (!isset($user['role']) || $user['role'] !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Forbidden: admin only']);
        exit;
    }
    return true;
}
