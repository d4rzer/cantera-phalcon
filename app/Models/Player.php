<?php
namespace App\Models;
class Player extends \Phalcon\Mvc\Model
{
    public $id;
    public $first_name;
    public $last_name;
    public $birth_date;
    public $photo_path;
    public $team_id;
    public $shirt_number;
    public $position;
    public $created_at;
    public $updated_at;
    public function initialize(): void {
        $this->setSource('players');
        $this->belongsTo('team_id', Team::class, 'id', ['alias' => 'Team']);
        $this->hasMany('id', Goal::class, 'player_id', ['alias' => 'Goals']);
        $this->hasMany('id', Incident::class, 'player_id', ['alias' => 'Incidents']);
    }
    public function beforeValidationOnCreate(): void { $this->created_at = $this->updated_at = date('Y-m-d H:i:s'); }
    public function beforeValidationOnUpdate(): void { $this->updated_at = date('Y-m-d H:i:s'); }
}
