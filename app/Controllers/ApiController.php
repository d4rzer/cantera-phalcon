<?php
namespace App\Controllers;
use App\Exceptions\HttpException;
abstract class ApiController extends \Phalcon\Mvc\Controller
{
    protected function token(): string {
        if (!$this->session->has('csrf')) $this->session->set('csrf', $this->security->getToken());
        return $this->session->get('csrf');
    }
    protected function verifyCsrf(): void {
        $provided = $this->request->getHeader('X-CSRF-Token');
        if (!$provided || !hash_equals($this->token(), $provided)) throw new HttpException('La sesión venció. Recargue la página.', 419);
    }
    protected function requireAccess(bool $write = false): array {
        $user = $this->session->get('user');
        if (!$user) throw new HttpException('Inicie sesión para continuar.', 401);
        if (!$this->acl->isAllowed($user['role'], 'tournament', $write ? 'write' : 'read')) throw new HttpException('Su cuenta tiene permisos de consulta.', 403);
        if ($write) $this->verifyCsrf();
        return $user;
    }
    protected function input(): array {
        $data = $this->request->isJson() ? $this->request->getJsonRawBody(true) : $this->request->getPost();
        if (!is_array($data)) throw new HttpException('Formato de datos inválido.', 400);
        return $data;
    }
    protected function json(mixed $data, int $status = 200) {
        $this->view->disable();
        $this->response->setHeader('Cache-Control', 'no-store');
        return $this->response->setStatusCode($status)->setJsonContent($data, JSON_UNESCAPED_UNICODE);
    }
}
