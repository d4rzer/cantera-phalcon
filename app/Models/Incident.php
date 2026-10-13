<?php
namespace App\Models;
class Incident extends \Phalcon\Mvc\Model
{
    public $id;
    public $player_id;
    public $description;
    public $card_type;
    public $incident_date;
    public $suspension_date;
    public $created_at;
    public $updated_at;
    public function initialize(): void {
        $this->setSource('incidents');
        $this->belongsTo('player_id', Player::class, 'id', ['alias' => 'Player']);
    }
    public function beforeValidationOnCreate(): void { $this->created_at = $this->updated_at = date('Y-m-d H:i:s'); }
    public function beforeValidationOnUpdate(): void { $this->updated_at = date('Y-m-d H:i:s'); }
}
