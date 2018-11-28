<?php

namespace app\modules\v1\models;
use app\modules\v1\models\UserResource;


/**
 * Class GroupResource
 * @package app\modules\v1\models
 *
 */
class NotificationResource extends BaseResource
{
    protected $alias = 'notification';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['from_id', 'for_id', 'type', 'post_id', 'created_at', 'read_status'], 'required', 'on' => ['insert', 'update']],
            [['type', 'post_content'], 'string'],
            [['from_id', 'for_id',  'read_status'], 'integer']
        ];
    }


    public function getType()
    {
        return 'notification';
    }


    public static function tableName()
    {
        return 'notifications';
    }

    public function getUser(){
        return $this->hasOne(UserResource::class, ['id'=>'from_id'])
            ->select('id, user_name,user_last_name,user_photo,user_gender,username');
    }

}