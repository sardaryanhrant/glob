<?php
namespace app\modules\v1\controllers;


use yii\console\Controller;

class ServersController extends Controller
{
    private $server;

    public function actionStart()
    {
        $this->server = new EchoServer();
        $this->server->start();
    }

    public function actionStop()
    {
        $this->server->stop();
    }

    public function actionStopp()
    {
        return 'aaaaaa';
    }


}