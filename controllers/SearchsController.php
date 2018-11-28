<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\AudioResource;
use app\modules\v1\models\PostResource;
use app\modules\v1\models\UserResource;
use app\modules\v1\models\GroupResource;
use app\modules\v1\models\VideoResource;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use Yii;


class SearchsController extends BaseController {

    public $modelClass  = '';
    public $postType    = 'post';
    public $fieldType   = 'posttype';
    public $field1      = '';
    public $field2      = '';
    public $q;
    protected $excludedFields = ['user_password'];

    public function actionSearch()
    {
        $params = Yii::$app->request->queryParams;
        switch ($params['type']) {
            case 'user':
                $this->modelClass   = new UserResource();
                $this->postType     = '';
                $q                  = $params['q'];
                $relation           = [];
                if(strpos($q, ' ') !== false){
                    $this->field1 = 'username';
                    $this->field2 = 'username';
                }else{
                    $this->field1  = 'user_name';
                    $this->field2  = 'user_last_name';
                }
                break;
            case 'post':
                $this->modelClass   = new PostResource();
                $this->field1       = 'post_content';
                $q                  = $params['q'];
                $relation           = ['user','comments','likes_dislikes'];
                break;
            case 'note':
                $this->modelClass   = new PostResource();
                $this->postType     = 'note';
                $relation           = ['user','comments','likes_dislikes'];
                break;
            case 'video':
                $this->modelClass   = new PostResource();
                $this->field1       = 'post_content';
                $this->field2       = 'post_description';
                $this->postType     = 'video';
                $relation           = ['user','comments','likes_dislikes'];
                $q                  = $params['q'];
                break;
            case 'audio':
                $this->modelClass   = new PostResource();
                $this->postType     = 'audio';
                $relation           = ['user','comments','likes_dislikes'];
                $q                  = $params['q'];
                $this->field1       = 'post_content';
                $this->field2       = 'post_description';
                break;
            case 'group':
                $this->modelClass   = new GroupResource();
                $this->field1       = 'group_name';
                $q                  = $params['q'];
                $this->postType     = '';
                $relation           = [];
                break;
        }

        $query = $this->modelClass::find();

        if($q !=''){

            if(strpos($q, ' ') !== false && $params['type'] == 'user'){
                $queryStr = explode(' ', $q);
                $username = '';
                $username1 = '';
                foreach ($queryStr as $qStr){if(!empty($qStr)){$username .= lcfirst($qStr).'.';} }
                $q = rtrim($username, '.');


                $queryStr1 = array_reverse($queryStr);

                foreach ($queryStr1 as $qStr1){if(!empty($qStr1)){$username1 .= lcfirst($qStr1).'.';} }
                $q1 = rtrim($username1, '.');

                if($this->field1){
                    $query->andFilterWhere(['ilike', $this->field1, $q ])
                        ->orFilterWhere(['ilike', $this->field2, $q1])
                        ->with($relation)
                        ->asArray();
                }
            }else{
                if($this->field1){
                    $query->andFilterWhere(['ilike', $this->field1, $q ])->with($relation)->asArray();
                }

                if($this->field2){
                    $query->orFilterWhere(['ilike', $this->field2, $q])->with($relation)->asArray();
                }
            }
        }

        if($this->postType){$query->andFilterWhere([$this->fieldType => $this->postType ])->with($relation)->asArray();}

        $dataProvider = new ActiveDataProvider(['query' => $query,]);

        return $dataProvider;

    }

}

