<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\PrivacyResource;
use app\modules\v1\models\UserResource;

class PrivacysController extends BaseController {

    public $modelClass = 'app\modules\v1\models\PrivacyResource';
    public $excludedFields = ['id','author_id'];

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['view']);
        unset($actions['update']);
        return $actions;
    }

    public function actionIndex(){
        $p = PrivacyResource::find()->where(['author_id'=>$this->checkauthuser()])->one();
        return $p;
    }

    public function actionView($id){
        return [];
    }


    public function actionUpdate($id)
    {
       if($this->checkauthuser()){
           $request = \Yii::$app->getRequest();
           $data = $request->getBodyParams();

           if($data['location'] == 0 || $data['location'] == 1){
               $user = UserResource::findOne($this->checkauthuser());
               $user->show_on_map = $data['location'];
               $user->update();
           }

           $privacy = PrivacyResource::find()->where(['author_id'=>$id])->one();
           $acceptableFields = ['write_messages', 'sees_other_records', 'can_post', 'location', 'can_comment','basic_info','sees_guests', 'notification'];

           foreach ($data as $key=>$value) {

               if($privacy->hasProperty($key) && in_array($key, $acceptableFields)){
                   $privacy->$key = $value;
               }
           }

           $privacy->update();
           return $privacy;
       }
    }





}

