<?php
declare(strict_types=1);

// Secure session
session_start([
    'name' => 'SecureSession',
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

require __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\UTCDateTime;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

// Verify CSRF token FIRST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

        error_log('CSRF validation failed. Session: ' . ($_SESSION['csrf_token'] ?? 'NULL') .
                 ' | Posted token: ' . ($_POST['csrf_token'] ?? 'NULL'));

        http_response_code(403);
        header('Location: /index.php?error=CSRF token validation failed');
        exit;
    }

    // Regenerate token after successful validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class AuthHandler {
    private $collection;

    public function __construct() {
        try {
            $client = new MongoClient(
                $_ENV['MONGODB_URI'],
                [
                    'tls' => true,
                    'retryWrites' => true,
                    'w' => 'majority'
                ]
            );
            $this->collection = $client->selectCollection('roomie13', 'users');
        } catch (Exception $e) {
            $this->handleError("Database connection failed", 500);
        }
    }

    public function handleRequest(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->handleError("Invalid request method", 405);
        }

        try {
            if (isset($_POST['signUp'])) {
                $this->handleSignUp();
            } elseif (isset($_POST['signIn'])) {
                $this->handleSignIn();
            } else {
                $this->handleError("Invalid action", 400);
            }
        } catch (Exception $e) {
            $this->handleError($e->getMessage(), 500);
        }
    }

    private function handleSignUp(): void {
        $data = $this->validateSignUpData();

        if ($this->collection->findOne(['email' => $data['email']])) {
            $this->handleError("Email already registered", 409);
        }

        $result = $this->collection->insertOne([
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'createdAt' => new UTCDateTime(),
            'timezone' => 'Africa/Johannesburg',
            'status' => 'active',
            'role' => 'user'
        ]);

        if ($result->getInsertedCount() === 1) {
            $this->startSession($data['email']);
            header('Location: /homepage.php');
            exit;
        }

        $this->handleError("Registration failed", 500);
    }

    private function handleSignIn(): void {
        $data = $this->validateSignInData();
        $user = $this->collection->findOne(['email' => $data['email']]);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            $this->handleError("Invalid credentials", 401);
        }

        $this->collection->updateOne(
            ['_id' => $user['_id']],
            ['$set' => ['lastLogin' => new UTCDateTime()]]
        );

        $this->startSession($user['email']);
        header('Location: /homepage.php');
        exit;
    }

    private function validateSignUpData(): array {
        $required = ['fName', 'lName', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->handleError("Missing $field", 400);
            }
        }

        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->handleError("Invalid email", 400);
        }

        if (strlen($_POST['password']) < 8) {
            $this->handleError("Password too short", 400);
        }

        return [
            'firstName' => $this->sanitizeInput($_POST['fName']),
            'lastName' => $this->sanitizeInput($_POST['lName']),
            'email' => $email,
            'password' => $_POST['password']
        ];
    }

    private function validateSignInData(): array {
        if (empty($_POST['email']) || empty($_POST['password'])) {
            $this->handleError("Email and password required", 400);
        }

        return [
            'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
            'password' => $_POST['password']
        ];
    }

    private function startSession(string $email): void {
        $_SESSION['user'] = [
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'last_active' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'logged_in' => true
        ];
    }

    private function sanitizeInput(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    private function handleError(string $message, int $code): void {
        http_response_code($code);
        header('Location: /index.php?error=' . urlencode($message));
        exit;
    }
}

// Process request
(new AuthHandler())->handleRequest();