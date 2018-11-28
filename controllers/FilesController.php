<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\FileResource;
use yii\web\UnauthorizedHttpException;
use yii\web\UploadedFile;


class FilesController extends BaseController
{
    public $modelClass = 'app\modules\v1\models\FileResource';
    public $excludedFields = ['id','author_id'];

    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                  'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['POST, DELETE'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Max-Age' => 3600,
                ],
            ],
        ];
    }



    public function actionUpload()
    {
        /*
            TODO Smiles. Need to have fixed smile icons on server

            Need to find out how we must save message if exist attached file
                1. upload file then save message
                2. to save it at once
        */
        if($this->checkauthuser()) {
            $request = \Yii::$app->getRequest();

            $data = $request->getBodyParams();
            $model = new FileResource();
            $model->file = UploadedFile::getInstanceByName( 'file' );
            $root = "/var/www/html"; //glob( $_SERVER["DOCUMENT_ROOT"] )[0];
            $folder = '';
            $type = '';
            

            if ($model->file) {
                $extension = $model->file->extension;
                switch ($extension) {
                    case 'jpg':
                    case 'jpeg':
                        $folder = 'images';
                        $type = 'image';
                        break;
                    case 'png':
                        $folder = 'images';
                        $type = 'image';
                        break;
                    case 'mp3':
                        $folder = 'audios';
                        $type = 'audio';
                        break;
                    case 'xls':
                        $folder = 'docs';
                        $type = 'xls';
                        break;
                    case 'pdf':
                        $folder = 'docs';
                        $type = 'pdf';
                        break;
                    case 'doc':
                        $folder = 'docs';
                        $type = 'doc';
                        break;
                    case 'txt':
                        $folder = 'docs';
                        $type = 'txt';
                        break;
                }

                $filepath = $root . '/upload/' . $folder . '/' . date( 'Y' ) . '/' . date( 'm' );

                if (!file_exists( $filepath )) {
                    mkdir( $root . '/upload/' . $folder . '/' . date( 'Y' ) . '/' . date( 'm' ), 0777, true );
                    $filepath = $root . '/upload/' . $folder . '/' . date( 'Y' ) . '/' . date( 'm' );
                }
                $fileName = md5( microtime() ) . '.' . $extension;


                $fileSrc = explode( 'html', $filepath . '/' . $fileName )[1];

                $protocol = stripos( $_SERVER['SERVER_PROTOCOL'], 'https' ) === true ? 'https://' : 'http://';

                $model->type = $type;
                $model->created = date( 'Y-m-d H:i:s' );
                $model->name = $fileName;
                $model->path = $protocol . $_SERVER['SERVER_NAME'] . $fileSrc;
                $model->author_id = $this->checkauthuser();

                if (!empty( $data['post_id'] ) && !empty( $data['post_type'] )) {
                    $model->post_type = $data['post_type'];
                    $model->post_id = $data['post_id'];
                }

                $model->save();
                $model->upload( $filepath, $fileName );

                return $model;
            } else {
                return ['status' => 'False'];
            }
        }else{
            throw new UnauthorizedHttpException();
        }

    }
}
