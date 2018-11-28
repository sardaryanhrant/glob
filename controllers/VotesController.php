<?php

namespace app\modules\v1\controllers;


use app\modules\v1\models\PostResource;
use app\modules\v1\models\QuestionResource;
use app\modules\v1\models\VoteResource;
use yii\web\BadRequestHttpException;

class VotesController extends BaseController {

    public $modelClass = 'app\modules\v1\models\VoteResource';
    public $excludedFields = ['id','author_id'];
    public $relationsWith = [];

    public function actionCreate()
    {
        $request = \Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $acceptableFields = ['author_id','question_id'];

        $newVote = new VoteResource();
        $checkVote = VoteResource::find()->where(['author_id'=>$data['author_id'], 'post_id'=>$data['post_id']])->one();
        $checkPostExistance = PostResource::find()->where(['id'=>$data['post_id'], 'posttype'=>'vote'])->one();
        $checkQuestionExistance = QuestionResource::find()->where(['id'=>$data['question_id'], 'post_id'=>$data['post_id']])->one();
        if(empty($checkVote) && !empty($checkPostExistance) && !empty($checkQuestionExistance)){
            foreach ($data as $key=>$value) {
                if($newVote->hasProperty($key) && in_array($key, $acceptableFields)){
                    $newVote->$key = $value;
                }
            }

            $newVote->post_id = $data['post_id'];
            $newVote->save();
            $vote_count = $checkQuestionExistance->vote_count;
            $checkQuestionExistance->vote_count = $vote_count+1;
            $checkQuestionExistance->update();
            return $checkQuestionExistance;
            return $newVote;
        }else{
            throw new BadRequestHttpException("You have already voted for this post OR Question with this id does not exist", 402);
        }
    }


}

