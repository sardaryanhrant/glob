<?php

namespace app\modules\v1\models;


class AudioResource extends BaseResource
{
    protected $alias = 'audios';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'audio_name','audio_link_url', 'created_date' ], 'required', 'on' => ['insert', 'update']],
            [['author_id', 'privacy'], 'integer'],
            [['audio_name', 'audio_link_url'], 'string'],
        ];
    }


    public function getType()
    {
        return 'audio';
    }


    public static function tableName()
    {
        return 'audios';
    }
}