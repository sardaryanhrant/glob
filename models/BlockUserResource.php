<?php

namespace app\modules\v1\models;


/**
 * Class GroupResource
 * @package app\modules\v1\models
 *
 */
class BlockUserResource extends BaseResource
{
    protected $alias = 'block_users';

    /**
     * @return array
     */
    public function rules()
    {
        return [

            [['block_user', 'blocked_by', 'author_id'], 'required', 'on' => ['insert', 'update']],
            [['block_user', 'author_id'], 'integer'],
            ['blocked_by', 'string'],
            [['author_id', 'block_user', 'blocked_by'], 'unique', 'targetAttribute' => ['author_id', 'block_user', 'blocked_by']],
            ['author_id',  'compare','compareAttribute'=>'block_user','operator'=>'!=','message'=>'You cannot block yourself.' ],
        ];
    }


    public function getType()
    {
        return 'blockuser';
    }


    public static function tableName()
    {
        return 'block_users';
    }


}