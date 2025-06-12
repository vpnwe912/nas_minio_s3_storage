<?php
namespace app\models;

use yii\db\ActiveRecord;

class PolicyMeta extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%policy_meta}}';
    }

    public function rules()
    {
        return [
            [['policy_name', 'sid'], 'required'],
            [['policy_name', 'sid'], 'string', 'max' => 255],
            ['comment', 'string'],
        ];
    }
    
    // public static function saveComment($policyName, $sid, $comment)
    // {
    //     if ($sid === null) return;
    //     $model = self::findOne(['policy_name' => $policyName, 'sid' => $sid]);
    //     if (!$model) {
    //         $model = new self();
    //         $model->policy_name = $policyName;
    //         $model->sid = $sid;
    //     }
    //     $model->comment = $comment;
    //     $model->save();
    // }
    
    // public static function getCommentsMap($policyName)
    // {
    //     return self::find()
    //         ->where(['policy_name' => $policyName])
    //         ->indexBy('sid')
    //         ->all();
    // }


    // Сохраняет/обновляет комментарий к политике
    public static function savePolicyComment($policy_name, $comment)
    {
        $model = self::findOne(['policy_name' => $policy_name, 'sid' => 'main']);
        if (!$model) {
            $model = new self();
            $model->policy_name = $policy_name;
            $model->sid = 'main';
        }
        $model->comment = $comment;

        $model->save(false);
    }
    
    // Получает комментарий по policy_name (sid = 'main')
    public static function getPolicyComment($policyName)
    {
        $model = self::findOne(['policy_name' => $policyName, 'sid' => 'main']);
        return $model ? $model->comment : '';
    }

}