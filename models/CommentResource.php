<?php

namespace app\modules\v1\models;
use app\modules\v1\models\UserResource;

/**
 * Class FileResource
 * @package app\modules\v1\models
 *
 * @property string $hash
 * @property string $path
 * @@property string $name
 */
class CommentResource extends BaseResource
{
    protected $alias = 'comments';
    // public $excludedFields = ['id'];
    public $file;

    public function rules()
    {

        return [
            [['comment_user_id', 'comment_post_id', 'comment_content', 'comment_created_date', 'comment_updated_date'], 'required', 'on' => ['insert', 'update']],
            [['comment_content', 'comment_for'], 'string', 'max' => 400],
            [['comment_user_id', 'parent','has_child', 'comment_post_id', 'attachment_id', 'comment_dislike_count', 'comment_like_count'], 'integer'],
        ];


    }


    public static function tableName()
    {
        return 'comments';
    }


    public function getType()
    {
        return 'comment';
    }

    public function getUser(){
        return $this->hasOne(UserResource::class, ['id'=>'comment_user_id'])->select('id, user_name,user_last_name,user_photo,user_gender, username');
    }
}