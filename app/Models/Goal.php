<?php
namespace App\Models;
class Goal extends \Phalcon\Mvc\Model
{
    public $id;
    public $player_id;
    public $matchday_id;
    public $goals;
    public $created_at;
    public $updated_at;
    public function initialize(): void {
        $this->setSource('goals');
        $this->belongsTo('player_id', Player::class, 'id', ['alias' => 'Player']);
        $this->belongsTo('matchday_id', Matchday::class, 'id', ['alias' => 'Matchday']);
    }
    public function beforeValidationOnCreate(): void { $this->created_at = $this->updated_at = date('Y-m-d H:i:s'); }
    public function beforeValidationOnUpdate(): void { $this->updated_at = date('Y-m-d H:i:s'); }
}
