<?php

namespace app\modules\v1\models;


class LikeResource extends BaseResource
{
    protected $alias = 'likes';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['user_id', 'post_id'], 'required', 'on' => ['insert', 'update']],
            [['user_id', 'post_id'], 'integer']
        ];
    }


    public function getType()
    {
        return 'like';
    }


    public static function tableName()
    {
        return 'likes';
    }
     public function getUser(){
        return $this->hasOne(UserResource::class, ['id'=>'user_id'])
            ->select('id, user_name,user_last_name,user_photo,user_gender');
    }
}