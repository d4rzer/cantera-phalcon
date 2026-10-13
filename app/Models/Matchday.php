<?php
namespace App\Models;
class Matchday extends \Phalcon\Mvc\Model
{
    public $id;
    public $number;
    public $game_date;
    public $created_at;
    public $updated_at;
    public function initialize(): void {
        $this->setSource('matchdays');
        $this->hasMany('id', Goal::class, 'matchday_id', ['alias' => 'Goals']);
    }
    public function beforeValidationOnCreate(): void { $this->created_at = $this->updated_at = date('Y-m-d H:i:s'); }
    public function beforeValidationOnUpdate(): void { $this->updated_at = date('Y-m-d H:i:s'); }
}
