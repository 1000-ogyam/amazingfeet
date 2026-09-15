<?php
class HomeController {
    public function index(): void {
        // Same entry as login — keep one experience at /
        (new AuthController())->showLogin();
    }
}
