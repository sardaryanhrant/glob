<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\FriendResource;
use app\modules\v1\models\GroupResource;
use app\modules\v1\models\LikeResource;
use app\modules\v1\models\PostResource;
use app\modules\v1\models\NotificationResource;
use app\modules\v1\models\FileResource;
use app\modules\v1\models\FollowerResource;
use app\modules\v1\models\PrivacyResource;
use app\modules\v1\models\QuestionResource;
use app\modules\v1\models\UserResource;
use app\modules\v1\models\VideoResource;
use Yii;
use yii\rest\IndexAction;
use yii\web\BadRequestHttpException;


class PostsController extends BaseController {

    public $modelClass = 'app\modules\v1\models\PostResource';
    public $excludedFields = ['id','author_id','post_user_id'];
    public $relationsWith = ['user','comments','attachments','tax','likes_dislikes'];

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['view']);

        $actions['index'] = [
            'class' => 'yii\rest\IndexAction',
            'modelClass' => $this->modelClass,
            'prepareDataProvider' => function(IndexAction $action, $filter) {
                return $this->prepareDataProvider($action, null);
            }
        ];
        return $actions;
    }

    /**
     * @return PostResource
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionCreatepost()
    {

        /*
            TODO
            Check user existence before set "post_wall_id"
            Before attach post to group or other entity check their existance (ex. post_for = group id)
        */

        $request = Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $acceptableFields = ['post_user_id','posttype', 'post_created_date','post_like_count', 'post_content',
                                'post_community', 'post_poll','post_poll_title','post_poll_all_voted', 'post_comment_count', 'post_updated_date',
                                'post_wall_id', 'post_group_id', 'author_id', 'post_tax_id', 'post_attachments', 'post_title', 'post_for', 'post_description',
                                'post_link', 'post_image', 'post_privacy'
                            ];

        if($this->checkauthuser()){

            $newPost = new PostResource();
            $request = Yii::$app->getRequest();
            $data = $request->getBodyParams();


            if(!empty($data['posttype']) && !empty($data['questions'])){
                $newVote = new PostResource();
                $newVote->post_poll_title = $data['title'];
                $newVote->posttype = 'vote';
                $newVote->author_id = $this->checkauthuser();
                $newVote->post_wall_id = $this->checkauthuser();
                $newVote->post_user_id = $this->checkauthuser();
                $newVote->save();

                foreach ($data['questions'] as $key=>$value){
                    $queston = new QuestionResource();
                    $queston->author_id = $this->checkauthuser();
                    $queston->title = $value;
                    $queston->post_id = $newVote->id;
                    $queston->save();
                }

                $vote = PostResource::find()->where(['id'=>$newVote->id])
                    ->select('id,author_id,post_user_id,posttype,post_wall_id,post_poll_title,post_created_date')
                    ->with('questions')
                    ->asArray()
                    ->one();
                return $vote;
            }


            if(empty($data['post_wall_id'])){
                $data['post_wall_id'] = $this->checkauthuser();
            }

            foreach ($data as $key=>$value) {
                if($newPost->hasProperty($key) && in_array($key, $acceptableFields)){
                    $newPost->$key = $value;
                }
            }


            if(!empty($data['post_for'])){
                $group = GroupResource::findOne($data['post_for']);
                if(!empty($group) && $group->group_author == $this->checkauthuser()){
                    $newPost->post_for = $data['post_for'];
                    $newPost->posttype = 'group';
                }else{
                    throw new BadRequestHttpException("Group by id=".$data['post_for']." does not exist OR you have not access to add posts for this group");
                }
            }


            /*Checking permissions if user posts on another user wall*/
            if( !empty($data['post_wall_id']) &&  $data['post_wall_id'] != $this->checkauthuser() ){
                $canPost = PrivacyResource::find()->where(['author_id'=>$data['post_wall_id']])->one();
                if(!empty($canPost)){
                    $ifCanPost = $canPost->can_post;
                    $seesRecords = $canPost->sees_other_records;
                    if($ifCanPost == 3){
                        throw new BadRequestHttpException("Access denied, Only owner can create posts to his/her wall", 402);
                    }elseif($ifCanPost == 2){
                            /*Checking if user is friend*/
                            $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$data['post_wall_id'], 'subscription'=>1])
                            ->orWhere(['user_id'=>$data['post_wall_id'], 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();
                            if(empty($isFriend)){
                                throw new BadRequestHttpException("Access denied, Only friends can create posts on user wall", 402);
                            }   
                    }elseif($ifCanPost == 1 && $seesRecords == 3){
                        throw new BadRequestHttpException("Access denied. User wall is private, Nobody cannot see his/her wall", 402);
                    }
                }else{
                    throw new BadRequestHttpException("Wall id=".$data['post_wall_id']." not found");
                }
            }
            
             /* Invite friends to join group.
               Creating post on friends wall if user do not denied access
           */
            if(!empty($data['post_wall_ids']) && $this->checkauthuser()){
                $friendsIds     = $data['post_wall_ids'];
                $invitedGroupId = $data['groupId'];
                $invitedGroup   = GroupResource::findOne($invitedGroupId);
                $inviteFrom     = UserResource::findOne($this->checkauthuser());
                
                foreach ($friendsIds as $friendsId){
                    $ifIsSubscribed = FollowerResource::find()->where(['to' => 'group', 'author_id' => $friendsId, 'follow_to' => $invitedGroupId])->one();
                    $canInvite = PrivacyResource::find()->where(['author_id'=> $friendsId])->one()->can_post;
                    $isfriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$friendsId, 'subscription'=>1])
                        ->orWhere(['user_id'=>$friendsId, 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();
                    if(!empty($isfriend) && $canInvite !== 3 && empty($ifIsSubscribed)){
                        $invite_post   = new PostResource();
                        $invite_post->post_user_id      = $this->checkauthuser();
                        $invite_post->author_id         = $this->checkauthuser();
                        $invite_post->posttype          = 'groupinvitation';
                        $invite_post->post_content      = $invitedGroup->group_name;
                        $invite_post->post_wall_id      = $friendsId;
                        $invite_post->post_group_id     = '';
                        $invite_post->save();
                        
                        /* Also creating Records in Notification table */
                        $notification = new NotificationResource();
                        $notification->from_id      = $this->checkauthuser();
                        $notification->author_id    = $this->checkauthuser();
                        $notification->for_id       = $friendsId;
                        $notification->post_id      = $invitedGroupId;
                        $notification->type         = 'invitation_from_group';
                        $notification->post_content = $invitedGroup->group_name;
                        $notification->read_status  = 0;
                        $notification->save();
                        
                        //*Updating user rate*/
                        $userRate = UserResource::findOne($this->checkauthuser());
                        $userRate->user_rate = $userRate->user_rate + 1;
                        $userRate->update();
                    }
                }
                return;
            }
            

            if(empty($data['questions'])) {
                $newPost->post_user_id = $this->checkauthuser();
                $newPost->author_id = $this->checkauthuser();
                $newPost->post_created_date = date( 'Y-m-d H:i:s' );
                $newPost->post_updated_date = date( 'Y-m-d H:i:s' );
                $newPost->post_like_count = 0;
                $newPost->post_comment_count = 0;

                $newPost->save();
                
                /*Updating user rate*/
                $userRate = UserResource::findOne($this->checkauthuser());
                $userRate->user_rate = $userRate->user_rate + 1;
                $userRate->update();
            }

            /*Updating Files Model if request object consists attachment file ids*/

            /*TODO
             * Attache file(s) to post when it(s) not attached yet (check before attached)
             */
            if(!empty($data['post_attachments'])){
                $files = FileResource::find()->where(['in', 'id', $data['post_attachments']])->all();
                foreach ($files as $file){
                    $file->post_id = $newPost->id;
                    $file->update();
                }
            }
            
            if(!empty($data['post_videos'])){

                $videos = VideoResource::find()->where(['in', 'id', $data['post_videos']])->all();
                foreach ($videos as $video){
                    $video->post_id = $newPost->id;
                    $video->update();
                }
            }
            
            $lastPosts = PostResource::find()
                ->where(['id'=>$newPost->id])
                ->with('videos')->asArray()
                ->with('user')->asArray()
                ->with('comments')->asArray()
                ->with('attachments')->asArray()
                ->with('tax')->asArray()
                ->with('videos')->asArray()
                ->with('likes_dislikes')->asArray()
                ->orderBy('id DESC')
                ->one();

            return $lastPosts;
        }
    }

    public function actionView($id){
        if($this->checkauthuser()){
            $posts = PostResource::find()
                ->where(['id'=>$id])
                ->with('user')->asArray()
                ->with('comments')->asArray()
                ->with('attachments')->asArray()
                ->with('tax')->asArray()
                ->with('likes_dislikes')->asArray()
                ->one();

            /*Checking if user is friend*/
            $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$posts['author_id'], 'subscription'=>1])
                ->orWhere(['user_id'=>$posts['author_id'], 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();


//            ($posts['author_id'] == $this->checkauthuser() || $posts['can_see'] == 2) || (!empty($isFriend) && $posts['can_see'] == 1)

            if($posts['author_id'] == $this->checkauthuser() ){
                return $posts;
            }elseif($posts['author_id'] == $this->checkauthuser() || $posts['can_see'] == 2){
                return $posts;
            }elseif($posts['author_id'] == $this->checkauthuser() || ($posts['can_see'] == 1 && !empty($isFriend))){
                return $posts;
            }else{
                return ['status'=>'false', 'msg'=>'Post not found or is a not public'];
            }
        }else{
            throw new UnauthorizedHttpException();
        }
    }
    
    /**
     * @return PostResource
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSharePost()
    {
        $request = Yii::$app->getRequest();
        $data = $request->getBodyParams();
        $user = UserResource::findOne($this->checkauthuser());

        $sharePost = new PostResource();
        $sharePost->author_id = $this->checkauthuser();
        $sharePost->post_user_id = $this->checkauthuser();
        if($data['posttype'] !== 'googlenews'){
            $sharePost->posttype = $data['posttype'];
        }else{
            $sharePost->posttype = 'post';
        }
        $sharePost->post_wall_id = $this->checkauthuser();
        if(!empty($data['post_link'])){
            $sharePost->post_link = $data['post_link'];
        }

        if(!empty($data['user']) && $data['posttype'] !== 'googlenews'){
            $sharePost->post_content = '<div>'.$user->user_name . ' ' . $user->user_last_name .' 
        shared '.$data['user']['user_name'] . ' ' . $data['user']['user_last_name'] .'\'s post  </div><br>'. $data['post_content'];
        }else{
            $sharePost->post_content = $data['post_content'];
        }


        $sharePost->post_created_date = date( 'Y-m-d H:i:s' );
        $sharePost->post_updated_date = date( 'Y-m-d H:i:s' );
        $sharePost->save();


        if(!empty($data['attachments'])){
            foreach ($data['attachments'] as $attach){
                $file = new FileResource();
                $file->name = $attach['path'];
                $file->path = $attach['path'];
                $file->post_id = $sharePost->id;
                $file->post_type = 'post';
                $file->author_id = $this->checkauthuser();
                $file->type = 'image';
                $file->save();
            }
        }


        $post = PostResource::find()->where(['id' =>$sharePost->id ])
            ->with('user')->asArray()
            ->with('comments')->asArray()
            ->with('attachments')->asArray()
            ->with('tax')->asArray()
            ->with('likes_dislikes')->asArray()->one();

        return $post;
    }
    


    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionGetpostsbywallid($id)
    {

        if($this->checkauthuser()){

            $offset = Yii::$app->request->get('offset');

            /*Checking Post privacy*/
            $privacy = PrivacyResource::find()->where(['author_id'=>$id])->one()->sees_other_records;

            /*Checking if user is friend*/
            $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$id, 'subscription'=>1])
                                              ->orWhere(['user_id'=>$id, 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();

            $allPosts = PostResource::find()
                ->where(['post_wall_id'=>$id, 'hide_from_wall' => 1])
                ->joinWith('user')->asArray()
                ->joinWith('comments')->asArray()
                ->joinWith('attachments')->asArray()
                ->joinWith('tax')->asArray()
                ->joinWith('videos')->asArray()
                ->joinWith('questions')->asArray()
                ->joinWith('likes_dislikes')->asArray()
                ->orderBy('id DESC')
                ->limit(20)
                ->offset($offset)
                ->all();

            $myPosts = PostResource::find()
                ->where(['post_wall_id'=>$id, 'post_user_id'=>$id, 'hide_from_wall' => 1])
                ->joinWith('user')->asArray()
                ->joinWith('comments')->asArray()
                ->joinWith('attachments')->asArray()
                ->joinWith('tax')->asArray()
                ->joinWith('videos')->asArray()
                ->joinWith('likes_dislikes')->asArray()
                ->orderBy('id DESC')
                ->limit(20)
                ->offset($offset)
                ->all();


            if($privacy == 1){
                return $allPosts;
            }elseif($privacy == 2){
                if(!empty($isFriend) || $id == $this->checkauthuser()){
                    return $allPosts;
                }else{
                    return $myPosts;
                }
            }elseif($privacy == 3){
                if($id == $this->checkauthuser()){
                    return $allPosts;
                }else{
                    throw new BadRequestHttpException("Access denied, Posts can see only author", 402);
                }
            }
        }else{
            throw new UnauthorizedHttpException();
        }
    }

    /**
     * @param $id
     * @return array
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        if($this->checkauthuser()) {
            $post = PostResource::find()->where(['id' => $id])->one();
            if ($post->author_id == $this->checkauthuser() || $post->post_wall_id == $this->checkauthuser()) {
                $post->delete();
                $likes = LikeResource::deleteAll(['post_id' => $id]);
                return ['status' => 'OK', 'message' => 'Post successfully deleted'];
            } else {
                return ['status' => 'False', 'message' => 'You don\'t have permission to delete this post'];
            }
        }
    }


    /**
     * @return array|null|\yii\db\ActiveRecord
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionUpdateFiles()
    {
        if($this->checkauthuser()) {
            $request = \Yii::$app->getRequest();
            $data = $request->getBodyParams();

            $postId = $data['post_id'];
            $files = $data['files'];

            /*Checking Album Onwer*/
            $post = PostResource::find()->where(['id' => $postId, 'posttype' => 'album'])->with('attachments')->asArray()->one();
            $ownerId = $post['author_id'];

            if ($ownerId == $this->checkauthuser()) {
                foreach ($files as $file) {
                    $file = FileResource::findOne($file);
                    $file->post_id = $postId;
                    $file->post_type = 'album';
                    $file->update();
                }
                $albumUpdate = PostResource::find()->where(['id' => $postId, 'posttype' => 'album'])->with('attachments')->asArray()->one();
                return $albumUpdate;
            } else {
                throw new BadRequestHttpException("Access denied, You arn't owner of this album", 402);
            }
        }
    }


    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionAlbum($username){

        $id = $this->userIdByUserName($username);

        /*Checking if user is friend*/
        $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$id, 'subscription'=>1])
            ->orWhere(['user_id'=>$id, 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();

        if($this->checkauthuser() == $id) {

            $album = PostResource::find()->where(['author_id' => $id, 'posttype' => 'album'])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $album;
        }elseif($this->checkauthuser() != $id && !empty($isFriend)){
            $album = PostResource::find()->where(['author_id' => $id, 'posttype' => 'album', 'can_see'=> 1])
                ->orWhere(['author_id' => $id, 'posttype' => 'album', 'can_see'=>2])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $album;
        }elseif($this->checkauthuser() != $id && empty($isFriend)){
            $album = PostResource::find()->where(['author_id' => $id, 'posttype' => 'album', 'can_see'=> 2])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $album;
        }
    }


    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionAudio($username){

        $id = $this->userIdByUserName($username);

        /*Checking if user is friend*/
        $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$id, 'subscription'=>1])
            ->orWhere(['user_id'=>$id, 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();

        if($this->checkauthuser() == $id ){
            $audio = PostResource::find()->where(['author_id' => $id, 'posttype' => 'audio'])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $audio;
        }elseif($this->checkauthuser() != $id && !empty($isFriend)){
            $audio = PostResource::find()->where(['author_id' => $id, 'posttype' => 'audio', 'can_see'=> 1])
                ->orWhere(['author_id' => $id, 'posttype' => 'audio', 'can_see'=>2])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $audio;
        }elseif($this->checkauthuser() != $id && empty($isFriend)){
            $audio = PostResource::find()->where(['author_id' => $id, 'posttype' => 'audio', 'can_see'=> 2])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $audio;
        }
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionVideo($username){

        $id = $this->userIdByUserName($username);

        /*Checking if user is friend*/
        $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$id, 'subscription'=>1])
            ->orWhere(['user_id'=>$id, 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();

        if($this->checkauthuser() == $id ) {
            $video = PostResource::find()->where(['author_id' => $id, 'posttype' => 'video'])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $video;
        }elseif($this->checkauthuser() != $id && !empty($isFriend)){
            $video = PostResource::find()->where(['author_id' => $id, 'posttype' => 'video', 'can_see'=> 1])
                ->orWhere(['author_id' => $id, 'posttype' => 'video', 'can_see'=>2])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $video;
        }elseif($this->checkauthuser() != $id && empty($isFriend)){
            $video = PostResource::find()->where(['author_id' => $id, 'posttype' => 'video', 'can_see'=> 2])
                ->with('user')->asArray()
                ->with('attachments')->asArray()
                ->all();
            return $video;
        }
    }

}

