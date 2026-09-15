<?php
class AuthController {
    public function showLogin(): void {
        // Drop incomplete sessions left from older builds or interrupted logins
        if (isset($_SESSION['user_id']) && (empty($_SESSION['user_role']) || !is_string($_SESSION['user_role']))) {
            $this->clearSession();
        }
        if (isLoggedIn()) {
            $this->redirectByRole((string)$_SESSION['user_role']);
            return;
        }
        view('home/index', ['error' => flash('error')]);
    }

    public function login(): void {
        verifyCsrf();
        $u = (new UserModel())->findByEmail(trim($_POST['email']??''));
        if (!$u || !(new UserModel())->verify($_POST['password']??'', $u['password'])) {
            flash('error','Invalid email or password.');
            redirect('/');
        }
        session_regenerate_id(true);
        $_SESSION['user_id']   = $u['id'];
        $_SESSION['user_name'] = $u['name'];
        $_SESSION['user_role'] = $u['role'];
        $this->redirectByRole((string)$u['role']);
    }

    public function logout(): void {
        $this->clearSession();
        redirect('/');
    }

    public function unauthorized(): void {
        http_response_code(403);
        view('layouts/unauthorized');
    }

    private function redirectByRole(string $r): void {
        match ($r) {
            'owner' => redirect('/dashboard'),
            default => redirect('/pos'),
        };
    }

    private function clearSession(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        // Start a fresh empty session for flash/CSRF on next request
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
