<?php

namespace app\modules\v1\controllers;


use app\modules\v1\models\VoteResource;

class QuestionsController extends BaseController {

    public $modelClass = 'app\modules\v1\models\QuestionResource';
    public $excludedFields = ['id','author_id'];
    public $relationsWith = [];



}

