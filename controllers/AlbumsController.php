<?php

namespace app\modules\v1\controllers;


use app\modules\v1\models\AlbumResource;
use app\modules\v1\models\FileResource;
use app\modules\v1\models\FriendResource;
use app\modules\v1\models\UserResource;
use yii\web\BadRequestHttpException;

class AlbumsController extends BaseController {

    public $modelClass = 'app\modules\v1\models\AlbumResource';
    public $excludedFields = ['id','author_id'];
    public $relationsWith = [];

    /**
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function actionView($id){
        $album = AlbumResource::find()->where(['id'=>$id])->with('attachmentwithcomments')->asArray()->one();
        if($album['author_id'] != $this->checkauthuser()){
            $can_see =  $album['can_see'];
            if($can_see == 1){
                return $album;
            }elseif ($can_see == 2){
                $albumAuthor = $album['author_id'];
                $isFriend = FriendResource::find()->where(['user_id'=>$this->checkauthuser(), 'friend_id'=>$albumAuthor, 'subscription'=>1])
                    ->orWhere(['user_id'=>$albumAuthor, 'friend_id'=>$this->checkauthuser(), 'subscription'=>1])->one();
                if(!empty($isFriend)){
                    return $album;
                }
            }
        }else{
           return $album;
        }

    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionUserAlbums($username){
        $id = UserResource::find()->where(['username'=> $username ])->one()->id;

        if($id == $this->checkauthuser()){
            $album = AlbumResource::find()->where(['author_id'=>$id])->with('attachmentwithcomments')->asArray()->all();
            return $album;
        }else{
            $album = AlbumResource::find()->where(['author_id'=>$id, 'can_see' => 1])->with('attachmentwithcomments')->asArray()->all();
            return $album;
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
        $request = \Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $albumId = $data['album_id'];
        $files   = $data['files'];

        /*Checking Album Onwer*/
        $album = AlbumResource::find()->where(['id'=>$albumId])->with('attachmentwithcomments')->asArray()->one();
        $ownerId = $album['author_id'];

        if($ownerId == $this->checkauthuser()){
            foreach ($files as $file){
                $file = FileResource::findOne($file);
                $file->post_id   = $albumId;
                $file->post_type = 'album';
                $file->update();
            }
            $albumUpdate = AlbumResource::find()->where(['id'=>$albumId])->with('attachmentwithcomments')->asArray()->one();
            return $albumUpdate;
        }else{
            throw new BadRequestHttpException("Access denied, You arn't owner of this album", 402);
        }
    }
}

