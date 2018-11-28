<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\CommentResource;
use app\modules\v1\models\FriendResource;
use app\modules\v1\models\PostResource;
use app\modules\v1\models\PrivacyResource;
use yii\web\BadRequestHttpException;

class CommentsController extends BaseController
{
    public $modelClass = 'app\modules\v1\models\CommentResource';
    public $excludedFields = ['id','author_id', 'comment_post_id', 'comment_user_id'];

    /**
     * @return CommentResource
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCreatecomment()
    {
        if($this->checkauthuser()){
            $request = \Yii::$app->getRequest();
            $data = $request->getBodyParams();
            $newComment = new CommentResource();

            $acceptableFields = ['comment_post_id', 'comment_content', 'comment_for', 'parent'];

            /*If comments are going to posts
             * Checking post owner
             */
            $postOwner = PostResource::findOne($data['comment_post_id'])->post_user_id;
            $postComment = PostResource::findOne($data['comment_post_id']);
            if($postOwner != $this->checkauthuser() ){
                /*Checking privacy for comment*/
                $commentPrivacy = PrivacyResource::find()->where(['author_id'=>$postOwner])->one()->can_comment;
                if($commentPrivacy == 3){
                    throw new BadRequestHttpException("Access denied, Only owner can create comments on his/her wall", 402);
                }elseif($commentPrivacy == 2){
                    /*Checking if user is friend*/
                    $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$postOwner, 'subscription'=>1])
                        ->orWhere(['user_id'=>$postOwner, 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();
                    if(empty($isFriend)){
                        throw new BadRequestHttpException("Access denied, Only owner friends can create comments on his/her wall", 402);
                    }
                }
            }

            foreach ($data as $key=>$value) {
                if($newComment->hasProperty($key) && in_array($key, $acceptableFields)){
                    $newComment->$key = $value;
                }
            }

            if(empty($data['parent'])){
                $newComment->parent = 0;

                $postComment->post_comment_count = $postComment->post_comment_count + 1;
                $postComment->update();
            }else{
                $pComment = CommentResource::findOne($data['parent']);
                $pComment->has_child = 1;
                $pComment->update();
            }

            $newComment->comment_user_id = $this->checkauthuser();
            $newComment->comment_created_date = date('Y-m-d H:i:s');
            $newComment->comment_updated_date = date('Y-m-d H:i:s');
            $newComment->author_id = $this->checkauthuser();
            $newComment->save();

            return $newComment;
        }

    }

    public function actionGetCommentsByOffset()
    {

        $last_comment_id =  \Yii::$app->getRequest()->getQueryParam('last_comment_id');
        $comment_post_id =  \Yii::$app->getRequest()->getQueryParam('comment_post_id');
        $reply      =  \Yii::$app->getRequest()->getQueryParam('reply');

        if(empty($reply)){
            $comments = CommentResource::find()
                ->where([' > ', 'id', $last_comment_id ])
                ->andWhere(['comment_post_id' => $comment_post_id])
                ->limit(5)
                ->all();
            return $comments;
        }else{
            $comments = CommentResource::find()
                ->where([' > ', 'id', $last_comment_id ])
                ->andWhere(['comment_post_id' => $comment_post_id, 'comment_for'=>'comment', 'parent' => $reply])
                ->limit(10)
                ->all();
            return $comments;
        }

    }

    public function actionGetAllComments($id)
    {
        $comments = CommentResource::find()->where(['comment_post_id'=>$id, 'parent'=>0])->with('user')->asArray()->all();
        return $comments;
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionReply($id)
    {
        if($this->checkauthuser()) {
            $reply = CommentResource::find()->where(['parent' => $id])->with('user')->asArray()->all();
            return $reply;
        }
    }

    public function actionDelete($id)
    {
        $comment = CommentResource::findOne($id);

        if(!empty($comment)){
            $parentComment = CommentResource::findOne($comment['parent']);
            $commentWithParent = CommentResource::find()->where(['parent'=> $parentComment['id']])->all();

            if(empty($commentWithParent) && $parentComment['id']){
                $parentComment->has_child = 0;
                $parentComment->update();
            }

            if($comment->parent == 0){
                $postComment = PostResource::findOne($comment->comment_post_id);
                $postComment->post_comment_count = ($postComment->post_comment_count - 1);
                $postComment->update();
            }

            /*Removing all childs comments*/

            $allChildsComments = CommentResource::find()->where(['parent' => $comment['id']])->all();

            foreach ($allChildsComments as $child){
                $subChildComments = CommentResource::find()->where(['parent' => $child['id']])->all();
                foreach ($subChildComments as $subChildComment) {
                    $subsubChildComments = CommentResource::find()->where(['parent' => $subChildComment['id']])->all();
                    foreach ($subsubChildComments as $subsubComment){
                        CommentResource::deleteAll(['parent' => $subsubComment['id']]);
                    }
                    CommentResource::deleteAll(['parent' => $subChildComment['id']]);
                }
                CommentResource::deleteAll(['parent' => $child['id']]);
            }

            CommentResource::deleteAll(['parent' => $comment['id']]);

            $comment->delete();
            return ['status'=>'OK', 'message'=>'Post successfully deleted'];

        }else{
            return ['status'=>'false', 'msg'=>'Post not found'];
        }

    }
}
