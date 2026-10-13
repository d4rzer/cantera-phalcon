<?php
namespace App\Controllers;
use App\Exceptions\HttpException;
use App\Models\User;
class AuthController extends ApiController
{
    public function sessionAction() {
        return $this->json(['user' => $this->session->get('user'), 'csrf' => $this->token(), 'settings' => [
            'age_min' => $this->settings['age_min'], 'age_max' => $this->settings['age_max'],
            'age_reference_date' => $this->settings['age_reference_date'],
            'demo_accounts' => $this->settings['environment'] !== 'production',
        ]]);
    }
    public function loginAction() {
        $this->verifyCsrf();
        $data = $this->input();
        $key = hash('sha256', $this->request->getClientAddress() ?: 'local');
        $attempt = $this->db->fetchOne('SELECT * FROM login_attempts WHERE fingerprint = ?', \Phalcon\Db\Enum::FETCH_ASSOC, [$key]);
        if ($attempt && (int)$attempt['failures'] >= 10 && (int)$attempt['last_failure'] > time() - 600) throw new HttpException('Demasiados intentos. Espere 10 minutos.', 429);
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');
        if (strlen($email) > 200 || strlen($password) > 200) throw new HttpException('Credenciales inválidas.', 401);
        $user = User::findFirst(['conditions' => 'email = :email:', 'bind' => ['email' => $email]]);
        $valid = $user && $this->security->checkHash($password, $user->password_hash);
        if (!$user) $this->security->hash($password);
        if (!$valid) {
            $count = $attempt && (int)$attempt['last_failure'] > time() - 600 ? (int)$attempt['failures'] + 1 : 1;
            $this->db->execute('DELETE FROM login_attempts WHERE fingerprint = ?', [$key]);
            $this->db->insertAsDict('login_attempts', ['fingerprint' => $key, 'failures' => $count, 'last_failure' => time()]);
            throw new HttpException('Correo o contraseña incorrectos.', 401);
        }
        $this->db->execute('DELETE FROM login_attempts WHERE fingerprint = ?', [$key]);
        session_regenerate_id(true);
        $info = ['id' => (int)$user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role];
        $this->session->set('user', $info);
        $this->session->remove('csrf');
        return $this->json(['user' => $info, 'csrf' => $this->token()]);
    }
    public function logoutAction() {
        $this->verifyCsrf();
        $this->session->remove('user');
        $this->session->remove('csrf');
        session_regenerate_id(true);
        return $this->json(['user' => null, 'csrf' => $this->token()]);
    }
}
