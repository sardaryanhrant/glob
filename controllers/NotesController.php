<?php

namespace app\modules\v1\controllers;


use app\modules\v1\models\NoteResource;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

class NotesController extends BaseController {

    public $modelClass = 'app\modules\v1\models\NoteResource';
    public $excludedFields = ['id','author_id'];
    public $relationsWith = [];

    public function actions() {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }

    public function actionIndex() {
        $activeData = new ActiveDataProvider([
            'query' => $this->modelClass::find()->where(['author_id'=>$this->checkauthuser()]),
            'pagination' => false
        ]);

        $dataNotes = array();

        foreach ($activeData as $activeDatum) {
            foreach ($activeDatum->all() as $d){
                $html = $d->content;
                $html_decode = htmlspecialchars_decode($html, ENT_NOQUOTES);
                unset($d['content']);
                $d->content = $html_decode;
                $dataNotes[] = $d;
            }
            return $dataNotes;
        }
    }

    public function actionView($id)
    {
        $activeData = new ActiveDataProvider([
            'query' => $this->modelClass::find()->where(['author_id'=>$this->checkauthuser(), 'id'=>$id]),
            'pagination' => false
        ]);

        $dataNotes = array();

        foreach ($activeData as $activeDatum) {
            foreach ($activeDatum->all() as $d){
                $html = $d->content;
                $html_decode = htmlspecialchars_decode($html, ENT_NOQUOTES);
                unset($d['content']);
                $d->content = $html_decode;
                $dataNotes[] = $d;
            }
            return $dataNotes;
        }
    }


    public function actionCreate()
    {
        if($this->checkauthuser()) {

            $newNote = new NoteResource();
            $acceptableFields = ['name', 'show_on_wall', 'compliant'];


            $request = \Yii::$app->getRequest();
            $data = $request->getBodyParams();

            foreach ($data as $key => $value) {
                if ($newNote->hasProperty( $key ) && in_array( $key, $acceptableFields )) {
                    $newNote->$key = $value;
                }
            }
            if (!empty( $data['content'] )) {
                $newNote->content = htmlspecialchars( $data['content'] );
            }

            $newNote->author_id = $this->checkauthuser();
            $newNote->save();

            $htmlschars = $newNote->content;

            unset($newNote['content']);

            $htmlspecialchars_decode = htmlspecialchars_decode($htmlschars, ENT_NOQUOTES);

            $newNote['content'] = $htmlspecialchars_decode;

            return $newNote;
        }else{
            throw new UnauthorizedHttpException();
        }
    }
}

