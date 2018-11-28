<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\FollowerResource;
use app\modules\v1\models\PostResource;
use app\modules\v1\models\FileResource;
use app\modules\v1\models\UserResource;
use app\modules\v1\models\GroupResource;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use Yii;


class GroupsController extends BaseController {

    public $modelClass = 'app\modules\v1\models\GroupResource';
    public $excludedFields = ['id','author_id'];
    public $relationsWith = ['groupposts', 'user'];

    public function actionGroupfollowers($id){
        $followerResource = FollowerResource::find()
            ->where(['to'=>'group', 'follow_to'=>$id])
            ->select('user_id')
            ->all();
        $followArr = array();
        foreach ($followerResource as $f){
            $followArr[] = $f->user_id;
        }

        $followUsers = UserResource::find()->where(['in', 'id', $followArr])->all();

        return $followUsers;
    }

    public function actionView($id)
    {

        $group = GroupResource::find()->where(['id'=>$id])->with('followers')->asArray()->one();

        return $group;
    }
}

