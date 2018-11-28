<?php


namespace app\modules\v1\models;


use app\common\models\UserModel;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\Link;

/**
 * Class UserResource
 * @package app\modules\v1\models
 *
 * @property array $links
 * @property string $type
 */
class UserResource extends UserModel
{

    /**
     * @var array
     */
    protected $excludedFields = ['user_password'];
    
    protected $alias = 'users';
    protected $relationships = ['friend' => 'friend'];

    public function rules()
    {
        return [
            [['user_name', 'user_password','user_email'], 'required'],
            [['user_email'], 'unique', 'on'=>['insert','update']],  
            [
                [   'user_last_name', 'user_photo','user_status',
                    'user_location', 'user_date_of_birth', 'user_marital_status',
                    'user_country', 'user_city', 'user_city', 'user_privacy',
                    'user_last_visit', 'username',
                ], 
                'string', 'max' => 255
            ], 

            [['user_friends_num'], 'integer'],
              
        ];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'user';
    }



    /**
     * @return array
     */
    public function getLinks()
    {
        return [
            Link::REL_SELF => Url::to(Url::base(true).'/v1/users/'.$this->getId()),
        ];
    }

    public static function tableName()
    {
        return 'users';
    }



    public function extraFields()
    {
        return ['friend'];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFriends()
    {
        return $this->hasMany( FriendResource::class, ['user_id' => 'id']);
    }

    public function getPosts()
    {
        return $this->hasMany( PostResource::class, ['post_user_id' => 'id'])
            ->orderBy('id DESC')
            ->with(['attachments','comments']);
    }
    public function getGroups()
    {
        return $this->hasMany( GroupResource::class, ['group_author' => 'id']);
    }

    public function getBlocks(){
        return $this->hasMany(UserResource::class, ['id'=>'block_user']);
    }

    public function getPrivacy()
    {
        return $this->hasOne(PrivacyResource::class, ['author_id'=>'id']);
    }



}