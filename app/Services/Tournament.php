<?php
declare(strict_types=1);
namespace App\Services;
use App\Exceptions\HttpException;
use App\Models\{Team, Player, Matchday, Goal, Incident};
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\{PresenceOf, StringLength};

/** Business rules live here, independently of HTTP and the interface. */
class Tournament
{
    private const MODELS = ['teams' => Team::class, 'players' => Player::class, 'matchdays' => Matchday::class, 'goals' => Goal::class, 'incidents' => Incident::class];
    private const FIELDS = [
        'teams' => ['name', 'active'],
        'players' => ['first_name', 'last_name', 'birth_date', 'team_id', 'shirt_number', 'position'],
        'matchdays' => ['number', 'game_date'],
        'goals' => ['player_id', 'matchday_id', 'goals'],
        'incidents' => ['player_id', 'description', 'card_type', 'incident_date', 'suspension_date'],
    ];
    private $db;
    private array $settings;
    public function __construct(private \Phalcon\Di\DiInterface $di) {
        $this->db = $di->getShared('db');
        $this->settings = $di->getShared('settings');
    }
    private function model(string $entity): string {
        if (!isset(self::MODELS[$entity])) throw new HttpException('Catálogo no encontrado.', 404);
        return self::MODELS[$entity];
    }
    private function record(string $entity, int $id) {
        $class = $this->model($entity);
        $model = $class::findFirst($id);
        if (!$model) throw new HttpException('El registro no existe.', 404);
        return $model;
    }
    private function startWrite(): void {
        $this->db->begin();
        // Serializes mutations so a red card and a goal cannot race each other.
        if ($this->settings['database']['driver'] === 'mysql') $this->db->fetchOne('SELECT id FROM tournament_lock WHERE id = 1 FOR UPDATE');
    }
    public function state(): array {
        $result = [];
        foreach (self::MODELS as $entity => $class) {
            $result[$entity] = $class::find(['order' => $entity === 'matchdays' ? 'number ASC' : 'id ASC'])->toArray();
        }
        return $result;
    }
    private function required(array $data, array $fields, array $lengths = []): void {
        $validation = new Validation();
        foreach ($fields as $field) $validation->add($field, new PresenceOf(['message' => 'Este campo es obligatorio.']));
        foreach ($lengths as $field => $max) $validation->add($field, new StringLength(['max' => $max, 'messageMaximum' => "Máximo {$max} caracteres."]));
        $errors = [];
        foreach ($validation->validate($data) as $message) $errors[(string)$message->getField()] = (string)$message;
        if ($errors) throw new HttpException('Revise los campos del formulario.', 422, $errors);
    }
    private function date(string $value, string $field): \DateTimeImmutable {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new HttpException('Fecha inválida.', 422, [$field => 'Ingrese una fecha válida.']);
        return $date;
    }
    private function integer(mixed $value, string $field, int $min, int $max): int {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < $min || (int)$value > $max) throw new HttpException('Número inválido.', 422, [$field => "Ingrese un entero entre {$min} y {$max}."]);
        return (int)$value;
    }
    private function unique(string $entity, string $field, mixed $value, int $id): void {
        $class = $this->model($entity);
        foreach ($class::find() as $row) {
            if ((int)$row->id !== $id && mb_strtolower((string)$row->$field) === mb_strtolower((string)$value)) throw new HttpException('Ya existe un registro con ese valor.', 422, [$field => 'Este valor ya está registrado.']);
        }
    }
    public function isSuspended(int $playerId, string $date): bool {
        return Incident::count(['conditions' => 'player_id = :p: AND card_type = :card: AND suspension_date = :date:', 'bind' => ['p' => $playerId, 'card' => 'red', 'date' => $date]]) > 0;
    }
    public function save(string $entity, array $input, int $id = 0, bool $trustedPhoto = false): array {
        $class = $this->model($entity);
        $this->startWrite();
        try {
            $row = $id ? $this->record($entity, $id) : new $class();
            $data = array_intersect_key($input, array_flip(self::FIELDS[$entity]));
            foreach ($data as $key => $value) {
                if (!is_scalar($value) && $value !== null) throw new HttpException('Valor inválido en el formulario.', 422, [$key => 'Valor inválido.']);
                if (is_string($value)) $data[$key] = trim($value);
            }
            if ($entity === 'teams') {
                $this->required($data, ['name'], ['name' => 100]);
                $this->unique('teams', 'name', $data['name'], $id);
                $data['active'] = $this->integer($data['active'] ?? 1, 'active', 0, 1);
            } elseif ($entity === 'players') {
                $this->required($data, ['first_name', 'last_name', 'birth_date', 'team_id'], ['first_name' => 100, 'last_name' => 100, 'position' => 50]);
                foreach (['first_name', 'last_name'] as $name) {
                    if (!preg_match('/^[\p{L}\p{M} ]+$/u', $data[$name])) throw new HttpException('Use letras y espacios en el nombre.', 422, [$name => 'Solo letras y espacios.']);
                }
                $birth = $this->date($data['birth_date'], 'birth_date');
                $reference = $this->date($this->settings['age_reference_date'], 'age_reference_date');
                $age = $birth->diff($reference)->y;
                if ($birth > $reference || $age < $this->settings['age_min'] || $age > $this->settings['age_max']) throw new HttpException('La edad está fuera de la categoría infantil.', 422, ['birth_date' => "Edad permitida: {$this->settings['age_min']} a {$this->settings['age_max']} años al {$reference->format('d/m/Y')}."]);
                $data['team_id'] = $this->integer($data['team_id'], 'team_id', 1, PHP_INT_MAX);
                $team = $this->record('teams', $data['team_id']);
                if (!(int)$team->active && (!$id || (int)$row->team_id !== $data['team_id'])) throw new HttpException('El equipo está inactivo.');
                if ($id && (int)$row->team_id !== $data['team_id'] && (Goal::count(['player_id = :p:', 'bind' => ['p' => $id]]) || Incident::count(['player_id = :p:', 'bind' => ['p' => $id]]))) throw new HttpException('Un jugador con historial debe conservar su equipo durante este torneo.', 409);
                $data['shirt_number'] = isset($data['shirt_number']) && $data['shirt_number'] !== '' ? $this->integer($data['shirt_number'], 'shirt_number', 1, 99) : null;
                $data['position'] = $data['position'] ?? null;
                if ($trustedPhoto) $data['photo_path'] = $input['photo_path'];
                elseif (!$id) $data['photo_path'] = 'images/player-placeholder.svg';
            } elseif ($entity === 'matchdays') {
                $this->required($data, ['number', 'game_date']);
                $data['number'] = $this->integer($data['number'], 'number', 1, 100);
                $this->date($data['game_date'], 'game_date');
                $this->unique('matchdays', 'number', $data['number'], $id);
                $this->unique('matchdays', 'game_date', $data['game_date'], $id);
                if ($id) foreach (Goal::find(['matchday_id = :m:', 'bind' => ['m' => $id]]) as $goal) {
                    if ($this->isSuspended((int)$goal->player_id, $data['game_date'])) throw new HttpException('La nueva fecha entra en conflicto con goles de un jugador suspendido.', 409);
                }
                if ($id && $data['game_date'] !== $row->game_date && Incident::count(['suspension_date = :d:', 'bind' => ['d' => $row->game_date]])) throw new HttpException('Ajuste primero las suspensiones asociadas antes de cambiar la fecha de esta jornada.', 409);
            } elseif ($entity === 'goals') {
                $this->required($data, ['player_id', 'matchday_id', 'goals']);
                $data['player_id'] = $this->integer($data['player_id'], 'player_id', 1, PHP_INT_MAX);
                $data['matchday_id'] = $this->integer($data['matchday_id'], 'matchday_id', 1, PHP_INT_MAX);
                $data['goals'] = $this->integer($data['goals'], 'goals', 0, 20);
                $this->record('players', $data['player_id']);
                $matchday = $this->record('matchdays', $data['matchday_id']);
                if ($this->isSuspended($data['player_id'], $matchday->game_date)) throw new HttpException('El jugador está suspendido para esa jornada.', 409);
                $duplicate = Goal::findFirst(['conditions' => 'player_id = :p: AND matchday_id = :m: AND id != :id:', 'bind' => ['p' => $data['player_id'], 'm' => $data['matchday_id'], 'id' => $id]]);
                if ($duplicate) throw new HttpException('Ya hay goles registrados para este jugador y jornada. Edite el registro existente.', 409);
            } elseif ($entity === 'incidents') {
                $this->required($data, ['player_id', 'description', 'card_type', 'incident_date'], ['description' => 500]);
                $data['player_id'] = $this->integer($data['player_id'], 'player_id', 1, PHP_INT_MAX);
                $this->record('players', $data['player_id']);
                $this->date($data['incident_date'], 'incident_date');
                if (!in_array($data['card_type'], ['yellow', 'red'], true)) throw new HttpException('Tipo de tarjeta inválido.');
                if ($data['card_type'] === 'yellow') $data['suspension_date'] = null;
                else {
                    $this->required($data, ['suspension_date']);
                    $suspension = $this->date($data['suspension_date'], 'suspension_date');
                    if ($suspension < $this->date($data['incident_date'], 'incident_date')) throw new HttpException('La suspensión no puede ser anterior a la incidencia.');
                    foreach (Goal::find(['player_id = :p:', 'bind' => ['p' => $data['player_id']]]) as $goal) {
                        if ($this->record('matchdays', (int)$goal->matchday_id)->game_date === $data['suspension_date']) throw new HttpException('Ya hay goles registrados en la fecha de suspensión. Corrija ese registro primero.', 409);
                    }
                }
            }
            $oldPhoto = $entity === 'players' ? $row->photo_path : null;
            $row->assign($data);
            if (!$row->save()) throw new HttpException('No se pudo guardar: ' . implode('; ', array_map('strval', $row->getMessages())));
            $this->db->commit();
            if ($trustedPhoto && $oldPhoto !== $row->photo_path) PhotoStorage::remove($oldPhoto);
            return $row->toArray();
        } catch (\Throwable $e) {
            if ($this->db->isUnderTransaction()) $this->db->rollback();
            throw $e;
        }
    }
    public function delete(string $entity, int $id): void {
        $this->model($entity);
        $this->startWrite();
        try {
            $row = $this->record($entity, $id);
            $blocked = match($entity) {
                'teams' => Player::count(['team_id = :i:', 'bind' => ['i' => $id]]),
                'players' => Goal::count(['player_id = :i:', 'bind' => ['i' => $id]]) + Incident::count(['player_id = :i:', 'bind' => ['i' => $id]]),
                'matchdays' => Goal::count(['matchday_id = :i:', 'bind' => ['i' => $id]]) + Incident::count(['suspension_date = :d:', 'bind' => ['d' => $row->game_date]]),
                default => 0,
            };
            if ($blocked) throw new HttpException('Este registro tiene información asociada. Edite sus datos o retire primero las asociaciones.', 409);
            if (!$row->delete()) throw new HttpException('No se pudo eliminar el registro.', 409);
            $this->db->commit();
            if ($entity === 'players') PhotoStorage::remove($row->photo_path);
        } catch (\Throwable $e) { if ($this->db->isUnderTransaction()) $this->db->rollback(); throw $e; }
    }
    private function decoratePlayer(array $player, array $teams): array {
        $team = current(array_filter($teams, fn($t) => (int)$t['id'] === (int)$player['team_id']));
        $player['name'] = $player['first_name'] . ' ' . $player['last_name'];
        $player['team_name'] = $team ? $team['name'] : '';
        $player['age'] = $this->date($player['birth_date'], 'birth_date')->diff($this->date($this->settings['age_reference_date'], 'age_reference_date'))->y;
        return $player;
    }
    public function report(string $kind, array $filters): array {
        $state = $this->state();
        $team = (int)($filters['team_id'] ?? 0);
        if ($team) $this->record('teams', $team);
        $matchday = (int)($filters['matchday_id'] ?? 0);
        if ($matchday) $this->record('matchdays', $matchday);
        if ($kind === 'referee' && !$matchday) throw new HttpException('Seleccione una jornada para verificar suspensiones.');
        $players = array_values(array_filter($state['players'], fn($p) => !$team || (int)$p['team_id'] === $team));
        if ($kind === 'incidents') {
            $playerId = (int)($filters['player_id'] ?? 0);
            if ($playerId) $this->record('players', $playerId);
            $from = (string)($filters['date_from'] ?? ''); $to = (string)($filters['date_to'] ?? '');
            if ($from) $this->date($from, 'date_from'); if ($to) $this->date($to, 'date_to');
            if ($from && $to && $from > $to) throw new HttpException('La fecha inicial debe ser anterior a la final.');
            $card = (string)($filters['card_type'] ?? '');
            if ($card && !in_array($card, ['yellow', 'red'], true)) throw new HttpException('Filtro de tarjeta inválido.');
            $rows = [];
            foreach ($state['incidents'] as $incident) {
                $p = current(array_filter($players, fn($p) => (int)$p['id'] === (int)$incident['player_id']));
                if (!$p || ($playerId && (int)$incident['player_id'] !== $playerId) || ($card && $incident['card_type'] !== $card) || ($from && $incident['incident_date'] < $from) || ($to && $incident['incident_date'] > $to)) continue;
                $player = $this->decoratePlayer($p, $state['teams']);
                $rows[] = $incident + ['player_name' => $player['name'], 'team_name' => $player['team_name']];
            }
            usort($rows, fn($a, $b) => strcmp($b['incident_date'], $a['incident_date']) ?: ((int)$b['id'] <=> (int)$a['id']));
            return ['rows' => $rows, 'filters' => $filters];
        }
        $rows = [];
        foreach ($players as $p) {
            $p = $this->decoratePlayer($p, $state['teams']);
            if ($kind === 'referee') $p['suspended'] = $this->isSuspended((int)$p['id'], $this->record('matchdays', $matchday)->game_date);
            else $p['total_goals'] = array_sum(array_map(fn($g) => (int)$g['goals'], array_filter($state['goals'], fn($g) => (int)$g['player_id'] === (int)$p['id'] && (!$matchday || (int)$g['matchday_id'] === $matchday))));
            $rows[] = $p;
        }
        if ($kind === 'scorers') usort($rows, fn($a, $b) => ($b['total_goals'] <=> $a['total_goals']) ?: strcmp($a['name'], $b['name']));
        else usort($rows, fn($a, $b) => strcmp($a['team_name'], $b['team_name']) ?: strcmp($a['name'], $b['name']));
        return ['rows' => $rows, 'filters' => $filters];
    }
    public function player(int $id): array {
        $state = $this->state();
        $p = $this->decoratePlayer($this->record('players', $id)->toArray(), $state['teams']);
        $p['incidents'] = array_values(array_filter($state['incidents'], fn($i) => (int)$i['player_id'] === $id));
        $p['history'] = array_map(function($m) use($state, $id) {
            $m['goals'] = array_sum(array_map(fn($g) => (int)$g['goals'], array_filter($state['goals'], fn($g) => (int)$g['player_id'] === $id && (int)$g['matchday_id'] === (int)$m['id'])));
            $m['suspended'] = $this->isSuspended($id, $m['game_date']);
            return $m;
        }, $state['matchdays']);
        $p['total_goals'] = array_sum(array_column($p['history'], 'goals'));
        return $p;
    }
}
