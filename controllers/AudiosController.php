<?php

namespace app\modules\v1\controllers;
use app\modules\v1\models\AudioResource;
use app\modules\v1\models\PostResource;


class AudiosController extends BaseController {

    public $modelClass = 'app\modules\v1\models\AudioResource';
    public $excludedFields = ['id','author_id'];

     public function actions()
    {
        $actions = parent::actions();
        unset($actions['view']);
        unset($actions['create']);

        return $actions;
    }

    public function actionView($id){
        $audios = AudioResource::find()->where(['author_id'=>$id])->orderBy('id ASC')->all();
        return $audios;
    }

    public function actionCreate()
    {
        $request = \Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $audio = new AudioResource();

        $acceptableFields = ['audio_name', 'audio_link_url','privacy'];

        foreach ($data as $key=>$value) {if($audio->hasProperty($key) && in_array($key, $acceptableFields)){ $audio->$key = $value; }}
        $audio->author_id = $this->checkauthuser();
        $audio->created_date = date('Y-m-d H:i:s');
        $audio->save();

        $post = new PostResource();
        $post->author_id = $this->checkauthuser();
        $post->post_user_id = $this->checkauthuser();
        $post->posttype = 'audio';
        $post->post_attachment_url = $audio->audio_link_url;
        $post->post_wall_id = $this->checkauthuser();
        $post->post_wall_id = $this->checkauthuser();
        $post->post_content = $audio->audio_name;
        $post->save();

        return $audio;

    }

}

