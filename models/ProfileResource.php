<?php

namespace app\modules\v1\models;


class ProfileResource extends BaseResource 
{

    protected $alias = 'profiles';
    public static function tableName()
    {
        return 'profiles';
    }
    public function getType()
    {
        return 'profile';
    }
}