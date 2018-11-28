<?php
namespace app\modules\v1\controllers;

use app\modules\v1\models\ChatResource;
use app\modules\v1\models\FileResource;
use app\modules\v1\models\FriendResource;
use app\modules\v1\models\MessageResource;
use app\modules\v1\models\NotificationResource;
use app\modules\v1\models\PrivacyResource;
use app\modules\v1\models\UserResource;
use consik\yii2websocket\events\WSClientEvent;
use consik\yii2websocket\events\WSClientMessageEvent;
use consik\yii2websocket\WebSocketServer;
use Lcobucci\JWT\Parser;
use Ratchet\ConnectionInterface;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;

class EchoServer extends WebSocketServer
{

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_CLIENT_MESSAGE, function (WSClientEvent $e) {
            $e->client->name = null;
        });
    }

    public function userExist($id, $model){
        $user = $model::findOne($id);
        if(!empty($user)){
            return $user->id;
        }else{
            return 0;
        }

    }

    public function commandGlobeRergisterUser(ConnectionInterface $client, $msg) {
        $request = json_decode($msg, true);
        $token = $request['token'];
        $auth = (new Parser())->parse((string) $token);
        $authID = $auth->getClaim('uid');
        if($authID) {
            $client->user_id = $authID;
        } else {
//            $client->close();
        }
    }

    public function commandGlobSendMessage(ConnectionInterface $client, $msg) {
        $request = json_decode($msg, true);

        foreach ($this->clients as $chatClient) {
            if($chatClient->user_id == $request['for_id']) {
                $chatClient->send( json_encode($msg) );
                $this->createmessage($request, $request['for_id'], $client->user_id);
            }
        }
    }

    public function commandChat(ConnectionInterface $client, $msg)
    {
        $request = json_decode($msg, true);
        $result = ['message' => ''];

        if (!$client->name) {
            $result['message'] = 'Set your name';
        } elseif (!empty($request['message']) && $message = trim($request['message']) ) {
            foreach ($this->clients as $chatClient) {
                $chatClient->send( json_encode([
                    'type' => 'chat',
                    'from' => $client->name,
                    'message' => $message
                ]) );
            }
        } else {
            $result['message'] = 'Enter message';
        }

        $client->send( json_encode($result) );
    }

    protected function getCommand(ConnectionInterface $from, $msg)
    {
        $request = json_decode($msg, true);
        return !empty($request['action']) ? $request['action'] : parent::getCommand($from, $msg);
    }

    public function createmessage($request, $for_id, $from_id){

        // TODO check if both users exists in db


        $data = $request;
        $newMessage = new MessageResource();
        $acceptableFields = ['from_id','for_id','content', 'chat_id','author_id'];

        if($from_id){
            if($this->userExist($for_id, UserResource::class)) {
//                /*Check if permission is open for private message*/
                $privacy = PrivacyResource::find()->where(['author_id'=>$for_id])->one();
                $privacyMessage = $privacy['write_messages'];
//
//                /*Checking is user exist in auth friends list*/
                $friendList = FriendResource::find()->where(['user_id'=>$from_id, 'friend_id'=>$for_id])->one();
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
                }

                $chat = ChatResource::find()
                    ->orWhere([
                        'from_id'=>$from_id, 'for_id'=>$for_id
                    ])
                    ->orWhere([
                        'from_id'=>$for_id, 'for_id'=>$from_id
                    ])->one();



                if(!empty($chat)){
                    $chat_id = $chat->id;
                }else{
                    $newChat = new ChatResource();
                    $newChat->author_id = $from_id;
                    $newChat->from_id = $from_id;
                    $newChat->for_id  = $for_id;
                    $newChat->save();
                    $chat_id = $newChat->id;
                }

                $data['chat_id'] = $chat_id;
                $data['from_id'] = $from_id;
                $data['for_id'] =  $for_id;


                foreach ($data as $key => $value) {
                    if ($newMessage->hasProperty( $key ) && in_array( $key, $acceptableFields )) {
                        $newMessage->$key = $value;
                    }
                }
                $newMessage->author_id = $from_id;
                $newMessage->save();

                if(!empty($data['attachments'])){
                    foreach ($data['attachments'] as $id){
                        $file = FileResource::find()->where(['id'=>$id])->one();
                        $file->post_type = 'message';
                        $file->post_id = $newMessage->id;
                        $file->save();
                    }
                }


                //Adding record in notification table
                $messageTo = UserResource::find()->where(['id'=> $from_id ])->one();

                $notification = new NotificationResource();

                $notification->from_id      = $from_id;
                $notification->author_id    = $from_id;
                $notification->for_id       = $for_id;
                $notification->post_id      = $newMessage->id;
                $notification->type         = 'invitation_from_message';
                $notification->post_content = $messageTo['user_name'] . ' '.$messageTo['user_last_name'] .' sent you a message';
                $notification->read_status  = 0;
                $notification->created_at   = date('Y-m-d H:i:s');
                $notification->save();

            }
        }else{
            throw new UnauthorizedHttpException();
        }
    }


}