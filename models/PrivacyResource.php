<?php

namespace app\modules\v1\models;


class PrivacyResource extends BaseResource
{
    protected $alias = 'privacys';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'created_date' ], 'required', 'on' => ['insert', 'update']],
            [['author_id'], 'unique'],
            [['author_id', 'write_messages', 'location', 'sees_other_records', 'can_post', 'can_comment', 'basic_info', 'see_guests'], 'integer'],
        ];
    }


    public function getType()
    {
        return 'privacy';
    }


    public static function tableName()
    {
        return 'privacy';
    }
}