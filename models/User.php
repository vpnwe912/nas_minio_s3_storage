<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\helpers\ArrayHelper;
use Yii;
use app\models\Permission;

/**
 * @property int    $id
 * @property string $username
 * @property string $email
 * @property string $salt
 * @property string $password_hash
 * @property string $auth_key
 * @property int    $created_at
 * @property int    $can_login
 *
 * @property string $password      // виртуальное поле для ввода пароля
 * @property int[]  $groupIds      // виртуальное поле для ввода групп
 *
 * @property Group[] $groups       // relation
 */

class User extends ActiveRecord implements IdentityInterface
{
    public $password;
    public $groupIds = [];

    public static function tableName()
    {
        return '{{%user}}';
    }

    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            ['email', 'email'],
            [['username','email'], 'string','max'=>255],
            [['username','email'], 'unique'],
            ['password', 'required', 'on' => 'create'],
            ['password', 'string', 'min' => 6],
            ['groupIds', 'each', 'rule' => ['integer']],
            ['can_login', 'boolean'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['username','email','password','groupIds'];
        $scenarios['update'] = ['username','email','password','groupIds'];
        return $scenarios;
    }

    /** После загрузки из БД — заполняем groupIds */
    public function afterFind()
    {
        parent::afterFind();
        $this->groupIds = ArrayHelper::getColumn($this->getGroups()->asArray()->all(), 'id');
    }

    /** После сохранения — сохраняем связи user_group */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        // очищаем старые
        Yii::$app->db
            ->createCommand()
            ->delete('{{%user_group}}', ['user_id' => $this->id])
            ->execute();
        // вставляем новые
        if (is_array($this->groupIds)) {
            foreach ($this->groupIds as $gid) {
                Yii::$app->db
                    ->createCommand()
                    ->insert('{{%user_group}}', [
                        'user_id'  => $this->id,
                        'group_id' => $gid,
                    ])->execute();
            }
        }
    }

    /** Перед сохранением — хэшируем пароль, генерим salt/auth_key */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->password) {
            // генерим соль
            $this->salt = base64_encode(random_bytes(16));
            // хэш от пароля+соль
            $this->password_hash = Yii::$app->security
                ->generatePasswordHash($this->password . $this->salt);
        }
        if ($this->isNewRecord) {
            // auth_key только для новых
            $this->auth_key = Yii::$app->security->generateRandomString();
            $this->created_at = time();
        }
        return true;
    }

    //--- IdentityInterface ---
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token]);
    }

    /** Поиск по username */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    /** Проверка пароля с солью */
    public function validatePassword($password)
    {
        return Yii::$app->security
            ->validatePassword($password . $this->salt, $this->password_hash);
    }

    /** Relation to Group via pivot */
    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'group_id'])
            ->viaTable('{{%user_group}}', ['user_id' => 'id']);
    }
        /**
     * Возвращает все права пользователя через группы
     * @return Permission[]
     */
    public function getPermissions()
    {
        return $this->hasMany(Permission::class,['id'=>'permission_id'])
            ->via('groups'); // groups() у нас уже есть
    }

    /**
     * Проверяет, есть ли у пользователя право $name
     * @param string $name
     * @return bool
     */
    public function hasPermission(string $name): bool
    {
        return Permission::find()
            ->alias('p')
            ->innerJoin('{{%group_permission}} gp',  'gp.permission_id = p.id')
            ->innerJoin('{{%user_group}} ug',        'ug.group_id      = gp.group_id')
            ->where([
                'ug.user_id' => $this->id,
                'p.name'     => $name,
            ])
            ->exists();
    }

}
