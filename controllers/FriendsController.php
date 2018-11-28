<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\FollowerResource;
use app\modules\v1\models\FriendResource;
use app\modules\v1\models\UserResource;
use app\modules\v1\models\NotificationResource;
use Yii;
use yii\web\BadRequestHttpException;

class FriendsController extends BaseController {
    public $modelClass = 'app\modules\v1\models\FriendResource';
	public $excludedFields = ['id','author_id', 'user_id', 'friend_id'];
	public $deleteByField = 'friend_id';
    public $relationsWith = ['user', 'userfriend'];

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['delete']);
        unset($actions['view']);
        return $actions;
    }

    /**
     * @return FriendResource|mixed
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCreate()
    {
        $request = Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $acceptableFields = ['friend_id', 'subscription'];

        if($this->checkauthuser()){
            $checkRecord = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$data['friend_id']])->one();
            if(empty($checkRecord) && $this->userExist($data['friend_id'], UserResource::class) && $this->userExist($data['friend_id'],  UserResource::class) != $this->checkauthuser()){

                $newFriend = new FriendResource();

                foreach ($data as $key=>$value) {

                    if($newFriend->hasProperty($key) && in_array($key, $acceptableFields)){
                        $newFriend->$key = $value;
                    }else{
                        throw new BadRequestHttpException("FriendResource has not property named ".$key . " or you have not permission to add value in property named ".$key);
                    }
                }

                $newFriend->user_id     = $this->checkauthuser();
                $newFriend->author_id   = $this->checkauthuser();
                $newFriend->added_date  = date('Y-m-d H:i:s');
                $newFriend->save();

                $follow = new FollowerResource();
                $follow->author_id = $this->checkauthuser();
                $follow->user_id = $this->checkauthuser();
                $follow->follow_to = $data['friend_id'];
                $follow->to = 'user';
                $follow->save();
                
                /*Adding record in notification*/
                $inviteUser = UserResource::find()->where(['id'=>$this->checkauthuser()])->one();
                $notification = new NotificationResource();
                $notification->from_id = $this->checkauthuser();
                $notification->author_id = $this->checkauthuser();
                $notification->for_id = $data['friend_id'];
                $notification->read_status = 0;
                $notification->post_id = $follow->id;
                $notification->type = 'invitation_add_friend';
                $notification->post_content = $inviteUser->user_name . ' '. $inviteUser->user_last_name.' sent you a friend request';
                $notification->save();
                
                /*Updating user rate*/
                $userRate = UserResource::findOne($this->checkauthuser());
                $userRate->user_rate = $userRate->user_rate + 1;
                $userRate->update();

                return $newFriend;
            }elseif($this->userExist($data['friend_id'],  UserResource::class) == $this->checkauthuser()){
                throw new BadRequestHttpException("You cannot add yourself in to your friends list :)");
            }else{
                throw new BadRequestHttpException("You have already added this user in to your friends list OR user does not exist in our DB");
            }

        }
    }


    /**
     * @return array|null|\yii\db\ActiveRecord
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionConfirm()
    {
        $request = Yii::$app->getRequest();
        $data = $request->getBodyParams();
        if($this->checkauthuser()) {
            $unConfirmFriend = FriendResource::find()->where( ['friend_id' => $data['user_id'], 'user_id' => $data['friend_id'], 'subscription'=>0] )->one();

            if (!empty( $unConfirmFriend )) {

                $unConfirmFriend->subscription = 1;
                $unConfirmFriend->added_date = date( 'Y-m-d H:i:s' );
                $unConfirmFriend->update();

                return $unConfirmFriend;
            }
        }
    }

    /**
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionUnconfirm()
    {
        $request = Yii::$app->getRequest();
        $data = $request->getBodyParams();

        if($this->checkauthuser()) {
            $unConfirmFriend = FriendResource::find()->where( ['friend_id' => $data['user_id'], 'user_id' => $data['friend_id'], 'subscription' => 0] )->one();

            if (!empty( $unConfirmFriend )) {
                $unConfirmFriend->delete();
            }else{
                throw new BadRequestHttpException("Resource not found");
            }
        }
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionView($id){
        $friendsArr = FriendResource::find()->where(['user_id'=>$id, 'subscription'=>1])
                                            ->orWhere(['friend_id'=>$id, 'subscription'=>1])->all();

        $friendIds = [];
        foreach ($friendsArr as $f){
            if($f['user_id'] == $id){$friendIds[] = $f['friend_id'];}else{$friendIds[] = $f['user_id'];}
        }

        $friends = UserResource::find()->where(['in', 'id', $friendIds])->select('id, user_name,user_last_name,user_email,user_photo,username')->all();
        return $friends;
    }

    /**
     * @param $id
     * @return array
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id){
        $friend = FriendResource::find()->where(['user_id'=>$id, 'friend_id'=>$this->checkauthuser()])
                                        ->orWhere(['user_id'=>$this->checkauthuser(), 'friend_id'=>$id])
                                        ->one();
        $follow1 = FollowerResource::find()->where(['author_id'=>$this->checkauthuser(), 'follow_to'=>$id])->one();
        $follow2 = FollowerResource::find()->where(['author_id'=>$id, 'follow_to'=>$this->checkauthuser()])->one();
        if(!empty($friend)){

            if(!empty($follow1)){$follow1->delete();}
            if(!empty($follow2)){$follow2->delete();}

            $friend->delete();
            return ['status'=>'OK', 'message'=>'Friend successfully deleted from your list'];
        }else{
            throw new BadRequestHttpException("Resource not found");
        }

    }
}