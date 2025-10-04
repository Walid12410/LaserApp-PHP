<?php
require __DIR__.'/bootstrap.php';

/* Demo login: returns a static token as long as email+password provided */
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($input['email'] ?? '');
$pass  = trim($input['password'] ?? '');
if ($email === '' || $pass === '') json_out(['error'=>'missing email or password'], 400);

json_out(['token' => AUTH_DEMO_TOKEN]);
