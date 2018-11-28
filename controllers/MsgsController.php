<?php
namespace app\modules\v1\controllers;

use yii\rest\ActiveController;

class MsgsController extends ActiveController
{
    public $modelClass = 'app\modules\v1\models\PostResource';
    public function actionStart($port = null)
    {
        $server = new EchoServer();
        if ($port) {
            $server->port = $port;
        }


        return $server->start();
    }
}