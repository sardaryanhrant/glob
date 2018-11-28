<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\ChatResource;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;

class ChatsController extends BaseController {

    public $modelClass = 'app\modules\v1\models\ChatResource'; 
    public $excludedFields = ['id','author_id'];

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['view']);

        return $actions;
    }

    public function actionIndex(){
        if($this->checkauthuser()) {
            $chats = ChatResource::find()
                ->where(['or', ['author_id'=>$this->checkauthuser()], ['for_id'=>$this->checkauthuser()]])
                ->with( ['user', 'user1'] )->asArray()
                ->all();
            $usersArr = array();

            foreach ($chats as $chat){
                if($chat['user1']['id'] != $this->checkauthuser()){
                    $usersArr[] = $chat['user1'];
                }else{
                    $usersArr[] = $chat['user'];
                }
            }
            return $usersArr;
        }else{
            throw new UnauthorizedHttpException();
        }
    }
    
}

