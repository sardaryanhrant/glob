<?php

namespace app\modules\v1\models;

use yii\base\Arrayable;

/**
 * Class ChatResource
 * @package app\modules\v1\models
 */
class ChatResource extends BaseResource  
{
    protected $alias = 'chats';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['from_id', 'for_id'], 'required', 'on' => ['insert', 'update']],
            [['from_id', 'for_id'], 'integer'],
        ];
    }


    public function getType()
    {
        return 'chat';
    }


    public static function tableName()
    {
        return 'chats';
    }

    public function getUser(){
        return $this->hasOne(UserResource::class, ['id'=>'from_id'])->select('id,user_name, user_last_name, user_photo')->with('privacy');
    }
    public function getUser1(){
        return $this->hasOne(UserResource::class, ['id'=>'for_id'])->select('id,user_name, user_last_name, user_photo')->with('privacy');
    }


}