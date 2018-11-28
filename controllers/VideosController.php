<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\PostResource;
use app\modules\v1\models\VideoResource;

class VideosController extends BaseController {

    public $modelClass = 'app\modules\v1\models\VideoResource';
    public $excludedFields = ['id','author_id'];
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['view']);
        unset($actions['create']);

        return $actions;
    }

    public function actionView($id){
        $videos = VideoResource::find()->where(['author_id'=>$id])->all();
        return $videos;
    }

    public function actionCreate()
    {
        $request = \Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $acceptableFields = ['video_name', 'video_image', 'link_to_videos', 'video_description', 'privacy', 'post_id'];
        $video = new VideoResource();

        foreach ($data as $key=>$value) {if($video->hasProperty($key) && in_array($key, $acceptableFields)){ $video->$key = $value; }}

        $video->created_date = date('Y-m-d H:i:s');
        $video->author_id = $this->checkauthuser();
        $video->save();

        $embedLink = explode('watch?v=', $video->link_to_videos);

        $post = new PostResource();
        $post->author_id = $this->checkauthuser();
        $post->post_user_id = $this->checkauthuser();
        $post->posttype = 'video';
        $post->post_attachment_url = $video->link_to_videos;
        $post->post_wall_id = $this->checkauthuser();
        $post->post_wall_id = $this->checkauthuser();
        $post->post_content = $video->video_description;
        $post->save();
    }

}

