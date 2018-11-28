<?php

namespace app\modules\v1\models;


/**
 * Class GroupResource
 * @package app\modules\v1\models
 *
 */
class NoteResource extends BaseResource
{
    protected $alias = 'notes';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'name', 'privacy', 'created_date'], 'required', 'on' => ['insert', 'update']],
            [['name', 'content', 'compliant'], 'string'],
            [['author_id', 'show_on_wall'], 'integer'],
        ];
    }


    public function getType()
    {
        return 'note';
    }


    public static function tableName()
    {
        return 'notes';
    }

}