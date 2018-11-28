<?php

namespace app\modules\v1\models;


/**
 * Class GroupResource
 * @package app\modules\v1\models
 *
 */
class VoteResource extends BaseResource
{
    protected $alias = 'votes';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'question_id'], 'required', 'on' => ['insert', 'update']],
            [['author_id', 'question_id'], 'integer']
        ];
    }


    public function getType()
    {
        return 'vote';
    }


    public static function tableName()
    {
        return 'votes';
    }


}