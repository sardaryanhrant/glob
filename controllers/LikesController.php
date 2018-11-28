<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\LikeResource;
use app\modules\v1\models\PostResource;
use app\modules\v1\models\CommentResource;
use app\modules\v1\models\UserResource;
use app\modules\v1\models\VideoResource;
use Yii;
use yii\web\BadRequestHttpException;


class LikesController extends BaseController {

    public $modelClass = 'app\modules\v1\models\LikeResource';
    public $excludedFields = ['id','user_id','post_id'];

    /**
     * @return LikeResource
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionCreatelike()
    {
        $request = Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $acceptableFields = ['post_id'];

        if($this->checkauthuser()){
            $postlike = LikeResource::find()
                ->where(['user_id'=>$this->checkauthuser(), 'post_id'=>$data['post_id'], 'type'=>'post'])
                ->orWhere(['user_id'=>$this->checkauthuser(), 'post_id'=>$data['post_id'], 'type'=>'video'])
                ->orWhere(['user_id'=>$this->checkauthuser(), 'post_id'=>$data['post_id'], 'type'=>'comment'])
                ->one();
            $commentlike = LikeResource::find()->where(['user_id'=>$this->checkauthuser(), 'post_id'=>$data['post_id'], 'type'=>'comment' ])->one();
            $post = PostResource::findOne($data['post_id']);
            $video = VideoResource::findOne($data['post_id']);
            $comment = CommentResource::findOne($data['post_id']);

            if(empty($postlike) && !empty($post) && $data['type']=='post'){
                $newLike = new LikeResource();

                foreach ($data as $key=>$value) {if($newLike->hasProperty($key) && in_array($key, $acceptableFields)){ $newLike->$key = $value; }}

                $newLike->user_id       = $this->checkauthuser();
                $newLike->author_id     = $this->checkauthuser();
                $newLike->created_date  = date('Y-m-d H:i:s');
                $newLike->status  = $data['action'];
                $newLike->type  = 'post';

                $newLike->save();
                
                /*Updating user rate*/
                $userRate = UserResource::findOne($this->checkauthuser());
                $userRate->user_rate = $userRate->user_rate + 1;
                $userRate->update();
                
                
                $count = $post->post_like_count;
                $discount = $post->post_dislike_count;
                if(!empty($data['action']) && $data['action']=='like'){
                    $post->post_like_count = $count +1;
                    $post->update();
                    return ['status'=>'OK', 'message'=>'liked'];
                }
                if(!empty($data['action']) && $data['action']=='dislike'){
                    $post->post_dislike_count = $discount +1;
                    $post->update();
                    return ['status'=>'OK', 'message'=>'disliked'];
                }
            }elseif(empty($postlike) && !empty($video) && $data['type']=='video'){
                $newLike = new LikeResource();
                $newLike->user_id       = $this->checkauthuser();
                $newLike->author_id     = $this->checkauthuser();
                $newLike->created_date  = date('Y-m-d H:i:s');
                $newLike->post_id       = $data['post_id'];
                $newLike->status  = $data['action'];
                $newLike->type  = 'video';
                $newLike->save();

                if(!empty($data['action']) && $data['action']=='like'){
                    return ['status'=>'OK', 'message'=>'liked'];
                }
                if(!empty($data['action']) && $data['action']=='dislike'){
                    return ['status'=>'OK', 'message'=>'disliked'];
                }

            }elseif(empty($commentlike) && !empty($comment) && $data['type']=='comment'){
                $newLike = new LikeResource();

                foreach ($data as $key=>$value) {if($newLike->hasProperty($key) && in_array($key, $acceptableFields)){ $newLike->$key = $value; }}

                $newLike->user_id       = $this->checkauthuser();
                $newLike->author_id     = $this->checkauthuser();
                $newLike->created_date  = date('Y-m-d H:i:s');
                $newLike->status  = $data['action'];
                $newLike->type  = 'comment';

                $newLike->save();
                
                /*Updating user rate*/
                $userRate = UserResource::findOne($this->checkauthuser());
                $userRate->user_rate = $userRate->user_rate + 1;
                $userRate->update();
                
                $count = $comment->comment_like_count;
                $discount = $comment->comment_dislike_count;
                if(!empty($data['action']) && $data['action']=='like'){
                    $comment->comment_like_count = $count +1;
                    $comment->update();
                    return ['status'=>'OK', 'message'=>'liked'];
                }
                if(!empty($data['action']) && $data['action']=='dislike'){
                    $comment->comment_dislike_count = $discount +1;
                    $comment->update();
                    return ['status'=>'OK', 'message'=>'disliked'];
                }
                
            }else{
                throw new BadRequestHttpException("Resourse not found");
            }
        }
    }
}

