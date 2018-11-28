<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\NotificationResource;
use app\modules\v1\models\PrivacyResource;

class NotificationsController extends BaseController {

    public $modelClass = 'app\modules\v1\models\NotificationResource';
    public $excludedFields = ['id','author_id'];
    public $relationsWith = [];

    public function actions() {
        $actions = parent::actions();
        unset($actions['update']);
        return $actions;
    }

    public function actionGetMessages()
    {
        if($this->checkauthuser()) {
            $msgs = NotificationResource::find()->where(['for_id' => $this->checkauthuser(), 'read_status' => 0, 'type' => 'invitation_from_message'])->all();
            return $msgs;
        }
    }

    public function actionGetNotifications()
    {
        if($this->checkauthuser()) {
            $privacyNote = PrivacyResource::find()->where(['author_id'=>$this->checkauthuser(), 'notification'=>1])->one();
            if(!empty($privacyNote)) {
                $notifications = NotificationResource::find()
                    ->where(['for_id' => $this->checkauthuser()])
                    ->andWhere(['!=', 'type', 'invitation_from_message'])
                    ->with('user')->asArray()
                    ->orderBy(['id' => SORT_DESC])
                    ->all();
                return $notifications;
            }else{
                return [];
            }
        }
    }

    public function actionUpdate($id)
    {
        $request = \Yii::$app->getRequest();
        $data = $request->getBodyParams();
        if($this->checkauthuser()) {
            $notification = NotificationResource::findOne($id);
            $notification->read_status = $data['read_status'];
            $notification->update();
        }
    }


}

