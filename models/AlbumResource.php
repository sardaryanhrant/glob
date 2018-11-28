<?php

namespace app\modules\v1\models;


/**
 * Class GroupResource
 * @package app\modules\v1\models
 *
 */
class AlbumResource extends BaseResource
{
    protected $alias = 'albums';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'name', 'can_see', 'can_comment', 'created_date', 'updated_date'], 'required', 'on' => ['insert', 'update']],
            [['name', 'description', 'cover'], 'string'],
            [['photo_num', 'comm_num', 'position', 'can_see', 'can_comment', 'editable'], 'integer'],
            [['author_id', 'name'], 'unique', 'targetAttribute' => ['author_id', 'name']]
        ];
    }


    public function getType()
    {
        return 'album';
    }


    public static function tableName()
    {
        return 'albums';
    }

    public function getComments(){
        return  $this->hasMany(CommentResource::class, ['comment_post_id'=>'id'])->andOnCondition(['comment_for' => 'album'])
            ->select('id, comment_user_id,comment_post_id,comment_content,comment_created_date')
            ->with('user');
    }

    public function getAttachmentwithcomments(){
        return $this->hasMany(FileResource::class, ['post_id'=>'id'])
            ->orderBy('id DESC')
            ->with('comments');
    }


}