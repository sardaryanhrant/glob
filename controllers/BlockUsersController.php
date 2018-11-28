<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\BlockUserResource;
use app\modules\v1\models\ChatResource;
use app\modules\v1\models\FollowerResource;
use app\modules\v1\models\FriendResource;
use app\modules\v1\models\GroupResource;
use app\modules\v1\models\UserResource;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

class BlockUsersController extends BaseController {

    public $modelClass = 'app\modules\v1\models\BlockUserResource';
    public $excludedFields = ['id','author_id'];
    public $relationsWith = [];
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['delete']);
        unset($actions['create']);
        return $actions;
    }


    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionIndex(){
        if($this->checkauthuser()){
            $blockusers = BlockUserResource::find()->where(['author_id'=>$this->checkauthuser()])->all();
            $blockedIds = array();
            foreach ($blockusers as $user){
                $blockedIds[] = $user['block_user'];
            }
            $users = UserResource::find()->where(['in', 'id', $blockedIds])
                ->select('id,user_name,user_last_name,user_photo,user_gender')
                ->all();
            return $users;
        }
    }


    /**
     * @return array|mixed
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionCreate(){

        $request = \Yii::$app->getRequest();
        $data = $request->getBodyParams();

        if($this->checkauthuser()) {
            $model = new UserResource();
            if ( $this->userExist($data['block_user'], $model) && $data['block_user'] != $this->checkauthuser() ) {
                $blockedByGroup = new BlockUserResource();

                if(empty($data['blocked_by'])){$blockBy = 'user';}else{$blockBy = $data['blocked_by'];}

                /*Checks if auth user is followed by this user */

                if($blockBy == 'group'){
                    /*First checking if user is own to this group(group_id)*/
                    $ifIsGroupOwn = GroupResource::find()->where(['group_author'=>$this->checkauthuser(), 'id'=>$data['group_id']])->one();
                    if(empty($ifIsGroupOwn)){$ifIsGroupOwn = 0;}else{$ifIsGroupOwn = 1;}

                    if($ifIsGroupOwn){
                        /*Checking if user is blocked by this group*/
                        $ifUserIsBlockedByGroup = BlockUserResource::find()->where(['author_id'=>$data['group_id'], 'block_user'=>$data['block_user'], 'blocked_by'=>'group'])->one();
                        if(empty($ifUserIsBlockedByGroup->id)){
                            /*Blocking user  by this group*/
                            $blockedByGroup->author_id  = $data['group_id'];
                            $blockedByGroup->block_user = $data['block_user'];
                            $blockedByGroup->blocked_by   = $blockBy;
                            $blockedByGroup->save();

                            /*Also removing user from followers table if user is following to this group*/
                            $ifUserFollowedGroup = FollowerResource::find()->where(['user_id'=>$data['block_user'], 'to'=>'group', 'follow_to'=>$data['group_id']])->one();
                            if(!empty($ifUserFollowedGroup)){
                                $ifUserFollowedGroup->delete();
                            }

                            return $blockedByGroup;
                        }else{
                            throw new BadRequestHttpException( "User already blocked by this group" );
                        }
                    }
                }

                if($blockBy == 'user'){
                    $checkRecord = FriendResource::find()
                        ->where( ['user_id' => $this->checkauthuser(), 'friend_id' => $data['block_user'], 'subscription'=>1 ] )
                        ->orWhere( ['user_id' => $data['block_user'], 'friend_id' => $this->checkauthuser(), 'subscription'=>1])
                        ->one();
                }


                /*Checks if user is already blocked by this user*/

                $ifUserIsBlocked = BlockUserResource::find()->where(['blocked_by'=>$blockBy, 'block_user'=>$data['block_user'], 'author_id'=>$this->checkauthuser()])->one();

                if(empty($ifUserIsBlocked)){$ifUserIsBlocked = 0;}else{$ifUserIsBlocked = 1;}

                $checkRecordUnsubscribe = FriendResource::find()
                    ->where( ['user_id' => $this->checkauthuser(), 'friend_id' => $data['block_user'], 'subscription'=>0 ] )
                    ->orWhere( ['user_id' => $data['block_user'], 'friend_id' => $this->checkauthuser(), 'subscription'=>0])
                    ->one();



                if (empty( $checkRecord ) && !$ifUserIsBlocked) {
                    if(!empty($checkRecordUnsubscribe)){
                        $checkRecordUnsubscribe->delete();
                    }

                    $blockUser = new BlockUserResource();
                    $blockUser->author_id  = $this->checkauthuser();
                    $blockUser->block_user = $data['block_user'];
                    $blockUser->blocked_by   = $blockBy;
                    $blockUser->save();

                    /*Need to remove record from chats table*/
                    $ifUserInChats = ChatResource::find()
                        ->where(['from_id'=>$this->checkauthuser(), 'for_id'=>$data['block_user']])
                        ->orWhere(['from_id'=>$data['block_user'], 'for_id'=>$this->checkauthuser()])->one();
                    if(!empty($ifUserInChats)){
                        $ifUserInChats->delete();
                    }

                    return ['status' => 'OK', 'msg' => 'User added in to your block lists'];
                } else {
                    throw new BadRequestHttpException( "User is in your friends/groups list, First you must delete his/her then block" );
                }
            }elseif($this->userExist($data['block_user'], $model) && $data['block_user'] == $this->checkauthuser() ){
                throw new BadRequestHttpException( "You cannot add yourself in to your block list :)" );
            }else{
                throw new BadRequestHttpException( "User does not exist in our DB" );
            }
        }
    }


    /**
     * @param $id
     * @return array
     * @throws UnauthorizedHttpException
     */
    public function actionDelete($id){
        if($this->checkauthuser()) {
            $blocked = $this->modelClass::find()->where( ['block_user' => $id, 'blocked_by'=>'user', 'author_id' => $this->checkauthuser()] )->one();
            if(!empty($blocked)){
                $blocked->delete();
                return ['status' => 'OK', 'msg' => 'User has been deleted from your block list'];
            }else{
                return ['status' => 'false', 'msg' => 'User does not found in your block list'];
            }
        }else{
            throw new UnauthorizedHttpException();
        }
    }

}

