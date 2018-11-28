<?php

namespace app\modules\v1\models;


class VideoResource extends BaseResource
{
    protected $alias = 'videos';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'video_name','link_to_videos','privacy', 'created_date' ], 'required', 'on' => ['insert', 'update']],
            [['author_id', 'privacy'], 'integer'],
            [['video_name', 'link_to_videos'], 'string'],
        ];
    }


    public function getType()
    {
        return 'video';
    }


    public static function tableName()
    {
        return 'videos';
    }
}