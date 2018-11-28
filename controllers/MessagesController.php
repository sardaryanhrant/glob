<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\FileResource;
use app\modules\v1\models\FriendResource;
use app\modules\v1\models\MessageResource;
use app\modules\v1\models\NotificationResource;
use app\modules\v1\models\PrivacyResource;
use app\modules\v1\models\UserResource;
use app\modules\v1\models\ChatResource;
use yii\web\BadRequestHttpException;
use Yii;
use yii\web\NotFoundHttpException;


class MessagesController extends BaseController {

    public $modelClass = 'app\modules\v1\models\MessageResource';
    public $excludedFields = ['id','author_id','for_id','from_id'];

  

    /**
     * @param $msg
     * @param $to
     */
    private function serverSendMessage($msg, $to){

        $messageList = MessageResource::find()->where(['for_id'=>$this->checkauthuser(), 'read_status'=>0])
        ->orderBy('id DESC')
        ->all();

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $messagesArr = array();
        if(count($messageList)){
            foreach($messageList as $key => $message) {
                $message->read_status = 1;
                $messagesArr[] = $message;
                $message->update();
                return $messagesArr;
            }
        }
        flush();
    }


    /**
     * @return MessageResource
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionCreatemessage(){

        // TODO check if both users exists in db

    	$request = Yii::$app->getRequest();
        $data = $request->getBodyParams();
        $newMessage = new MessageResource();
        $acceptableFields = ['from_id','for_id','content', 'chat_id','author_id'];

        if($this->checkauthuser()){
            if($this->userExist($data['for_id'], UserResource::class)) {
                /*Check if permission is open for private message*/
                $privacy = PrivacyResource::find()->where(['author_id'=>$data['for_id']])->one();
                $privacyMessage = $privacy->write_messages;

                /*Checking is user exist in auth friends list*/
                $friendList = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$data['for_id']])->one();
                if(!empty($friendList)){
                    $isFriend = 1;
                }else{
                    $isFriend = 0;
                }

                switch ($privacyMessage) {
                    case 3:
                        throw new BadRequestHttpException("Access denied for all users to write message to him/her");
                        break;
                    case 2:
                        if($isFriend == 0){
                            throw new BadRequestHttpException("Access denied. Only can write friends");
                        }
                        break;
                        /*TODO Need to have verifiy functionality then check this status */
    //               case 4:
    //                    if($isVerified == 0){
    //                        throw new BadRequestHttpException("Access denied. Only can write friends");
    //                    }
    //                    break;
                }

                $chat = ChatResource::find()
                ->orWhere([
                    'from_id'=>$this->checkauthuser(), 'for_id'=>$data['for_id']
                ])
                ->orWhere([
                    'from_id'=>$data['for_id'], 'for_id'=>$this->checkauthuser()
                ])->one();

                $chat_id;

                if(!empty($chat)){
                    $chat_id = $chat->id;
                }else{
                    $newChat = new ChatResource();
                    $newChat->author_id = $this->checkauthuser();
                    $newChat->from_id = $this->checkauthuser();
                    $newChat->for_id  = $data['for_id'];
                    $newChat->save();
                    $chat_id = $newChat->id;
                }

                $data['chat_id'] = $chat_id;
                $data['from_id'] = $this->checkauthuser();


                foreach ($data as $key => $value) {
                    if ($newMessage->hasProperty( $key ) && in_array( $key, $acceptableFields )) {
                        $newMessage->$key = $value;
                    }
                }
                $newMessage->author_id = $this->checkauthuser();
                $newMessage->save();

                if(!empty($data['attachments'])){
                    foreach ($data['attachments'] as $id){
                        $file = FileResource::find()->where(['id'=>$id])->one();
                        $file->post_type = 'message';
                        $file->post_id = $newMessage->id;
                        $file->save();
                    }
                }


                $message = MessageResource::find()->where(['id'=>$newMessage->id])
                    ->with('attachments')->asArray()
                    ->orderBy('id ASC')
                    ->one();
                    
                    
                 /*Adding record in notification table */
                $messageTo = UserResource::find()->where(['id'=>$data['for_id']])->one();
                $notification = new NotificationResource();
                $notification->from_id      = $this->checkauthuser();
                $notification->author_id    = $this->checkauthuser();
                $notification->for_id       = $data['for_id'];
                $notification->post_id      = $newMessage->id;
                $notification->type         = 'invitation_from_message';
                $notification->post_content = $messageTo->user_name . ' '.$messageTo->user_last_name.' sent you a message';
                $notification->read_status  = 0;
                $notification->save();

               // $this->serverSendMessage($newMessage, $data['for_id']);

                return $message;

            }else{
                throw new NotFoundHttpException('User does not exist with for_id='.$data['for_id']);
            }

//            // Adding User to Auth User Chat list
//            $me = UserResource::findOne($this->checkauthuser());
//            $chatList = $me->user_chat_list;
//
//            /*
//                TODO
//                    Refactor migration => set user_chat_list default value {"chatList":[]} instead of []
//            */
//
//            if(!in_array($data['for_id'], $chatList['chatList']) && $this->checkauthuser() != $data['for_id']){
//                $chatList['chatList'][] = $data['for_id'];
//                $me->user_chat_list     = $chatList;
//                $me->update();
//            }


        }else{
            throw new UnauthorizedHttpException();
        }
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionGetmessagebyuserid($id){
        // TODO Set Limit messages to 100
         
        if($this->checkauthuser()){
            $chat = ChatResource::find()
            ->orWhere(['from_id'=>$id, 'for_id' => $this->checkauthuser()])
            ->orWhere(['for_id' =>$id, 'from_id' => $this->checkauthuser()])
            ->one();
            if(!empty($chat)) {
                $chatId = $chat->id;
                $chatmessages = MessageResource::find()
                    ->where( ['chat_id' => $chatId] )
                    ->orderBy('id ASC')
                    ->with('attachments')->asArray()
                    ->all();

                return $chatmessages;
            }else{
                return [];
            }
        }
    }
    
}

