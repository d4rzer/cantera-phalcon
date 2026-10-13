<?php
namespace App\Controllers;
class IndexController extends ApiController
{
    public function indexAction() { $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW); }
    public function healthAction() {
        $this->db->fetchOne('SELECT 1');
        return $this->json(['status' => 'ok', 'framework' => 'Phalcon ' . (new \Phalcon\Support\Version())->get()]);
    }
    public function notFoundAction() { return $this->json(['error' => 'Ruta no encontrada.'], 404); }
}
