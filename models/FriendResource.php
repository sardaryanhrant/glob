<?php

namespace app\modules\v1\models;

use yii\base\Arrayable;


class FriendResource extends BaseResource 
{

    protected $alias = 'friends';
//    protected $relationships = ['user' => 'user'];

    public function getType()
    {
        return 'friend';
    }

    public static function tableName()
    {
        return 'friends';
    }

    public function rules()
    {
        return [
            [['user_id','friend_id'], 'required', 'on' => 'insert'],
            [['subscription', 'user_id', 'friend_id'], 'integer'],
//            ['user_id', 'exist', 'targetRelation' => 'user'],
            // ['tmc_class', 'in', 'range' => ['drug', 'equipment', 'vaccine']]
        ];
    }  

    public function getUserfriend(){
        return $this->hasMany(UserResource::class, ['id'=>'friend_id'])->select('id, user_name, user_last_name, user_photo,');
    }

    public function getUser(){
        return $this->hasOne(UserResource::class, ['id'=>'user_id'])->select('id, user_name, user_last_name, user_photo,username');
    }

}