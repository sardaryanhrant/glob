<?php

namespace app\modules\v1\models;


/**
 * Class GroupResource
 * @package app\modules\v1\models
 *
 */
class QuestionResource extends BaseResource
{
    protected $alias = 'questions';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['author_id', 'post_id', 'vote_count', 'title'], 'required', 'on' => ['insert', 'update']],
            [['title'], 'string'],
            [['author_id', 'post_id', 'vote_count'], 'integer']
        ];
    }


    public function getType()
    {
        return 'question';
    }


    public static function tableName()
    {
        return 'questions';
    }


}