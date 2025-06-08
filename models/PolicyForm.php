<?php

namespace app\models;

use yii\base\Model;

/**
 * PolicyForm — модель для создания/редактирования политики через GUI.
 *
 * Поля:
 * - name       string           — имя политики
 * - statements array of arrays — набор блоков Statement, каждый с:
 *     - sid      string        — идентификатор (Sid)
 *     - comment  string|null   — комментарий
 *     - bucket   string        — имя бакета
 *     - prefix   string|null   — префикс в бакете (папка)
 *     - actions  array         — разрешённые действия, e.g. ['GetObject','PutObject']
 */
class PolicyForm extends Model
{
    /** @var string */
    public $name;

    /** @var array */
    public $statements = [];

    public function rules()
    {
        return [
            // Имя политики
            ['name', 'required'],
            ['name', 'match', [
                'pattern' => '/^[A-Za-z0-9\-_]+$/',
                'message' => 'Имя может содержать только латиницу, цифры, дефис и подчёркивание',
            ]],
            // Каждый элемент statements — ассоциативный массив
            ['statements', 'each', 'rule' => ['validateStatement']],
        ];
    }

    /**
     * Валидация отдельного блока Statement
     * @param string $attribute
     * @param array $params
     */
    public function validateStatement($attribute, $params)
    {
        foreach ($this->$attribute as $i => $stmt) {
            // Проверяем, что sid задан
            if (empty($stmt['sid']) || !is_string($stmt['sid'])) {
                $this->addError($attribute, "В блоке №" . ($i + 1) . " не указан Sid.");
            }
            // bucket обязателен
            if (empty($stmt['bucket']) || !is_string($stmt['bucket'])) {
                $this->addError($attribute, "В блоке №" . ($i + 1) . " не указан Bucket.");
            }
            // actions — непустой массив
            if (empty($stmt['actions']) || !is_array($stmt['actions'])) {
                $this->addError($attribute, "В блоке №" . ($i + 1) . " должны быть выбраны действия.");
            }
        }
    }

    public function attributeLabels()
    {
        return [
            'name'       => 'Имя политики',
            'statements' => 'Statements',
        ];
    }
}
