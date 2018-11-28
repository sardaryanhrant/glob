<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\BlockUserResource;
use app\modules\v1\models\FileResource;
use app\modules\v1\models\FollowerResource;
use app\modules\v1\models\VideoResource;
use app\modules\v1\models\GroupResource;
use app\modules\v1\models\PostResource;
use app\modules\v1\models\UserResource;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class FollowersController extends BaseController {

    public $modelClass = 'app\modules\v1\models\FollowerResource';
    public $excludedFields = ['id','author_id'];

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['create']);
        unset($actions['view']);
        unset($actions['delete']);

        return $actions;
    }

    public function actionIndex(){
        if($this->checkauthuser()) {
            $chats = FollowerResource::find()
                ->where(['or', ['follow_to'=>$this->checkauthuser()]])
                ->all();
            return $chats;
        }else{
            throw new UnauthorizedHttpException();
        }
    }

    public function actionCreate(){
        $newFollower = new FollowerResource();

        $acceptableFields = ['follow_to','to'];

        $request = \Yii::$app->getRequest();
        $data = $request->getBodyParams();

        if($data['to'] == 'user'){ $model = new UserResource();
        }elseif($data['to'] == 'group'){
            $model = new GroupResource();
        }
        if($data['to'] == 'user' && $data['follow_to'] == $this->checkauthuser()) {
            throw new BadRequestHttpException( "You cannot follow yourself :) " );
        }


        if($data['to'] == 'group') {
            /*Checking if group exist*/
            if ($this->userExist( $data['follow_to'], new GroupResource() )) {
                /*Befor following group check if user is not blocked by group*/
                $ifUserIsBlockedByGroup = FollowerResource::find()->where( ['author_id' => $this->checkauthuser(), 'follow_to' => $data['follow_to'], 'to' => 'group'] )->one();
                if (empty( $ifUserIsBlockedByGroup )) {
                    foreach ($data as $key => $value) {
                        if ($newFollower->hasProperty( $key ) && in_array( $key, $acceptableFields )) {
                            $newFollower->$key = $value;
                        }
                    }
                    $newFollower->author_id = $this->checkauthuser();
                    $newFollower->user_id = $this->checkauthuser();
                    $newFollower->save();
                    return $newFollower;
                }
            } else {
                throw new NotFoundHttpException( 'Group does not exist by id=' . $data['follow_to'] );
            }
        }

        if ($this->userExist( $data['follow_to'], $model )) {
            foreach ($data as $key => $value) {
                if ($newFollower->hasProperty( $key ) && in_array( $key, $acceptableFields )) {$newFollower->$key = $value;}
            }

            $newFollower->author_id = $this->checkauthuser();
            $newFollower->user_id = $this->checkauthuser();
            $newFollower->save();
            return $newFollower;
        } else {
            throw new BadRequestHttpException( "Resourse not found.");
        }
    }

    public function actionDelete($id){
        if($this->checkauthuser()) {
            $request = \Yii::$app->getRequest();
            $data = $request->getBodyParams();

            $to = $data['to'];

            $follow = FollowerResource::find()->where( ['follow_to' => $id, 'to' => $to, 'user_id' => $this->checkauthuser()] )->one();
            
            if(!empty($follow )){
                $follow->delete();
                return ['status'=>'OK', 'message'=>'You have unfollowed from '. $to];
            }else{
                throw new BadRequestHttpException("Resource not found");
            }

        }
    }



    /**
     * @return array
     */
    public function actionNews()
    {

        $offset = \Yii::$app->request->get('offset');

        if($this->checkauthuser()){

            /*Getting all posts for news. Data will include all posts which was create user friends all or all user/group posts which was follow user*/

            $news = array();

            $friendList = FollowerResource::find()->where(['user_id'=>$this->checkauthuser(), 'to'=>'user'])->all();
            $frendIds = array();

            foreach ($friendList as $value) { $frendIds[] = $value['follow_to'];}

            $friendPosts = PostResource::find()->where(['in', 'post_user_id', $frendIds])
                ->with('user')->asArray()
                ->with('likes_dislikes')->asArray()
                ->with('attachments')->asArray()
                ->limit(20)
                ->orderBy('id DESC')
                ->offset($offset)
                ->all();

            if(!empty($friendPosts)){
                foreach ($friendPosts as $post){
                    $news[] = $post;
                }
            }

            $groupList = FollowerResource::find()->where(['user_id'=>$this->checkauthuser(), 'to'=>'group'])->all();
            $groupIds = array();

            foreach ($groupList as $value) { $groupIds[] = $value['follow_to'];}

            $groupPosts = PostResource::find()->where(['in', 'post_group_id', $groupIds])
                ->andWhere([ '!=', 'author_id', $this->checkauthuser()])
                ->with('user')->asArray()
                ->with('likes_dislikes')->asArray()
                ->with('attachments')->asArray()
                ->limit(20)
                ->offset($offset)
                ->orderBy('id DESC')
                ->all();

            if(!empty($groupPosts)){
                foreach ($groupPosts as $post){
                    $news[] = $post;
                }
            }

            $userInterest = UserResource::findOne($this->checkauthuser());

            if(!empty($userInterest->user_interests['interests'])) {

                $q = '';
                if(!empty($userInterest->user_interests['country'])){
                    $q = 'country='.$userInterest->user_interests['country'].'&';
                }
                if(!empty($userInterest->user_interests['interests'])){
                    $q .= 'category='.$userInterest->user_interests['interests'];
                }

                if ($q !== '') {
                    $googleNews = json_decode(file_get_contents("https://newsapi.org/v2/top-headlines?".$q."&apiKey=9085260422a840588b5f8b30044f4edd"));

                    foreach ($googleNews->articles as $gnews) {
                        $article = array();
                        $article['id'] = $gnews->source->id;
                        $article['id'] = $gnews->source->id;
                        $article['posttype'] = 'googlenews';
                        if(!empty($gnews->url)){
                            $url = '<a target="_blank" href="'.$gnews->url.'">'.$gnews->title.'</a>';
                        }
                        if(!empty($gnews->description)){
                            $article['post_content'] = '<div>'.$url.'</div>' . $gnews->description;
                        }elseif(!empty($gnews->content)){
                            $article['post_content'] = '<div>'.$url.'</div>' . $gnews->content;
                        }else{
                            $article['post_content'] = '<div>'.$url.'</div>' . $gnews->title;
                        }

                        $article['comments'] = [];
                        $article['post_comment_count'] = 0;
                        $article['post_dislike_count'] = 0;
                        $article['post_like_count'] = 0;
                        $article['hide_from_wall'] = 1;
                        $article['post_wall_id'] = null;
                        $article['post_created_date'] = str_replace('Z', '', str_replace('T', ' ', $gnews->publishedAt));
                        $article['attachments'][] = ['path' => $gnews->urlToImage];
                        $article['user'] = ['user_name' => $gnews->author, 'user_last_name' => '', 'user_photo' => ''];
                        $news[] = $article;
                        $article = array();
                    }
                }
            }

            usort($news, function ($item1, $item2) {
                return $item2['post_created_date'] <=> $item1['post_created_date'];
            });

            return  $news;
        }
    }
}

