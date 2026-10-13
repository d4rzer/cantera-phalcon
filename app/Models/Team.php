<?php
namespace App\Models;
class Team extends \Phalcon\Mvc\Model
{
    public $id;
    public $name;
    public $active;
    public $created_at;
    public $updated_at;
    public function initialize(): void {
        $this->setSource('teams');
        $this->hasMany('id', Player::class, 'team_id', ['alias' => 'Players']);
    }
    public function beforeValidationOnCreate(): void { $this->created_at = $this->updated_at = date('Y-m-d H:i:s'); }
    public function beforeValidationOnUpdate(): void { $this->updated_at = date('Y-m-d H:i:s'); }
}
