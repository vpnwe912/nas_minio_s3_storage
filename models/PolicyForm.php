<?php
namespace app\models;

use yii\base\Model;

class PolicyForm extends Model
{
    public $name;
    public $statements = [];

    public function init()
    {
        parent::init();
        // При создании сразу один пустой блок, чтобы «Добавить» работало
        if ($this->isNewRecord && empty($this->statements)) {
            $this->statements = [
                ['sid'=>'','comment'=>'','bucket'=>'','prefix'=>'','actions'=>[]]
            ];
        }
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['statements'], 'safe'],
        ];
    }

    public function getIsNewRecord(): bool
    {
        return $this->name === null;
    }
}
