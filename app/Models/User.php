<?php
namespace App\Models;
class User extends \Phalcon\Mvc\Model
{
    public $id;
    public $name;
    public $email;
    public $password_hash;
    public $role;
    public $created_at;
    public $updated_at;
    public function initialize(): void {
        $this->setSource('users');
        
    }
    public function beforeValidationOnCreate(): void { $this->created_at = $this->updated_at = date('Y-m-d H:i:s'); }
    public function beforeValidationOnUpdate(): void { $this->updated_at = date('Y-m-d H:i:s'); }
}
