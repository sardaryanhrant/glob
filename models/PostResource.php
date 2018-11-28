<?php

namespace app\modules\v1\models;

use app\modules\v1\models\CommentResource;

/**
 * Class PostResource
 * @package app\modules\v1\models
 */
class PostResource extends BaseResource
{
    protected $alias = 'posts';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['post_user_id', 'author_id', 'post_wall_id', 'post_created_date', 'post_updated_date'], 'required', 'on' => ['insert', 'update']],
            [['post_user_id', 'author_id', 'can_see', 'post_like_count', 'post_comment_count', 'post_tax_id', 'post_group_id', 'post_poll' ], 'integer'],
            [['posttype', 'post_content', 'post_poll_title', 'post_title'], 'string']
        ];
    }


    public function getType()
    {
        return 'post';
    }

    public static function tableName()
    {
        return 'posts';
    }

    public function getComments(){
        return  $this->hasMany(CommentResource::class, ['comment_post_id'=>'id'])->andOnCondition(['comment_for' => 'post', 'parent' => 0])
            ->select('id, comment_user_id,comment_post_id,comment_content,comment_created_date,has_child,parent')
            ->orderBy('id DESC')
            ->limit(5)
            ->with('user');
    }

//    public function getAttachments(){
//        return $this->hasMany(FileResource::class, ['post_id'=>'id'])
////            ->onCondition(['post_type'=>'post'])
//            ->select('id, type,created,name,path,post_id,post_type');
//    }

    public function getLikes_dislikes(){
        return $this->hasMany(LikeResource::class, ['post_id'=>'id'])
            ->with('user')
            ->select('user_id,post_id,created_date, status');
    }

    public function getUser(){
        return $this->hasOne(UserResource::class, ['id'=>'author_id'])->select('id, user_name,user_last_name,user_photo,username, user_gender');
    }

    public function getTax(){
        return $this->hasOne(TaxonomieResource::class, ['id'=>'post_tax_id']);
    }

    public function getQuestions()
    {
        return $this->hasMany(QuestionResource::class, ['post_id'=>'id']);
    }

    public function getVideos()
    {
        return $this->hasMany(VideoResource::class, ['post_id'=>'id'])->select('id,video_name,video_image,link_to_videos,post_id,video_description');
    }

    public function getAttachments(){
        return $this->hasMany(FileResource::class, ['post_id'=>'id'])
            ->orderBy('id DESC')
            ->select('id,type,created,name,path,post_id,post_type')
            ->with('comments');
    }



}