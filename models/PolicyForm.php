<?php
namespace app\models;

use yii\base\Model;

class PolicyForm extends \yii\base\Model
{
    public $name;
    public $comment; // Комментарий ко всей политике
    public $bucket;
    public $folders = []; // Массив папок (prefix)
    public $actions = []; // Массив массивов actions (по индексам как папки)

    public function rules()
    {
        return [
            [['name', 'bucket', 'folders', 'actions'], 'required'],
            [['comment'], 'string'],
            ['folders', 'validateFoldersUnique'],
        ];
    }

    public function validateFolders($attribute)
    {
        if (count($this->folders) !== count(array_unique($this->folders))) {
            $this->addError($attribute, 'Папки не должны повторяться.');
        }
        foreach ($this->folders as $folder) {
            if (empty($folder)) {
                $this->addError($attribute, 'Папка не может быть пустой.');
            }
        }
    }
    public function validateFoldersUnique($attribute)
    {
        if (count($this->folders) !== count(array_unique($this->folders))) {
            $this->addError($attribute, 'Папки не должны повторяться.');
        }
    }
}
