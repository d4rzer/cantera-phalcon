<?php
namespace App\Controllers;
use App\Services\PhotoStorage;
class RecordsController extends ApiController
{
    public function stateAction() {
        $this->requireAccess();
        return $this->json($this->tournament->state());
    }
    public function listAction() {
        $this->requireAccess();
        $entity = $this->dispatcher->getParam('entity');
        $rows = $this->tournament->state()[$entity];
        $search = mb_strtolower(trim((string)$this->request->getQuery('q')));
        $team = (int)$this->request->getQuery('team_id', 'int');
        $rows = array_values(array_filter($rows, function ($row) use ($search, $team) {
            if ($team && isset($row['team_id']) && (int)$row['team_id'] !== $team) return false;
            return !$search || str_contains(mb_strtolower(implode(' ', $row)), $search);
        }));
        $paginator = new \Phalcon\Paginator\Adapter\NativeArray(['data' => $rows, 'limit' => 10, 'page' => max(1, (int)$this->request->getQuery('page', 'int', 1))]);
        $page = $paginator->paginate();
        return $this->json(['items' => $page->getItems(), 'total' => $page->getTotalItems(), 'pages' => $page->getLast(), 'page' => $page->getCurrent()]);
    }
    public function saveAction() {
        $this->requireAccess(true);
        $entity = $this->dispatcher->getParam('entity');
        $id = (int)$this->dispatcher->getParam('id', 'int', 0);
        $data = $this->input();
        $photo = $entity === 'players' ? PhotoStorage::store($this->request) : null;
        if ($photo) $data['photo_path'] = $photo;
        try { $row = $this->tournament->save($entity, $data, $id, (bool)$photo); }
        catch (\Throwable $e) { if ($photo) PhotoStorage::remove($photo); throw $e; }
        return $this->json(['record' => $row, 'message' => 'Registro guardado.'], $id ? 200 : 201);
    }
    public function deleteAction() {
        $this->requireAccess(true);
        $this->tournament->delete($this->dispatcher->getParam('entity'), (int)$this->dispatcher->getParam('id'));
        return $this->json(['message' => 'Registro eliminado.']);
    }
}
