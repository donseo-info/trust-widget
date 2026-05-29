<?php
class AuthController extends Controller
{
    public function loginPage(array $params = []): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->renderPartial('auth/login', ['error' => $_GET['error'] ?? null]);
    }

    public function login(array $params = []): void
    {
        $email    = $this->post('email');
        $password = $this->post('password');

        if (Auth::attempt($email, $password)) {
            $this->redirect('/');
        } else {
            $this->redirect('/auth/login?error=1');
        }
    }

    public function logout(array $params = []): void
    {
        Auth::logout();
        $this->redirect('/auth/login');
    }
}
