<?php

namespace app\modules\v1\models;

use yii\base\Arrayable;

/**
 * Class ChatResource
 * @package app\modules\v1\models
 */
class FollowerResource extends BaseResource
{
    protected $alias = 'followers';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'user_id', 'follow_to', 'created_date'], 'required', 'on' => ['insert', 'update']],
            [['author_id', 'user_id', 'follow_to'], 'integer'],
            [['author_id', 'user_id', 'follow_to', 'to'], 'unique', 'targetAttribute' => ['author_id', 'user_id', 'follow_to', 'to']]
        ];
    }


    public function getType()
    {
        return 'follower';
    }


    public static function tableName()
    {
        return 'followers';
    }

    public function getUser(){
        return $this->hasMany(UserResource::class, ['id'=>'user_id']);
    }
}