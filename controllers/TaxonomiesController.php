<?php

namespace app\modules\v1\controllers;

use app\modules\v1\models\TaxonomieResource;
use Yii;


class TaxonomiesController extends BaseController {

    public $modelClass = 'app\modules\v1\models\TaxonomieResource';
    public $excludedFields = ['id','tax_name','tax_slug', 'tax_for'];

    /**
     * @return TaxonomieResource
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionCreatetax()
    {
        $request = Yii::$app->getRequest();
        $data = $request->getBodyParams();

        $newTax = new TaxonomieResource();

        $acceptableFields = ['tax_name', 'tax_for','created_date'];

        if($this->checkauthuser()) {

            foreach ($data as $key=>$value) {

                if($newTax->hasProperty($key) && in_array($key, $acceptableFields)){
                    $newTax->$key = $value;
                }
            }
            $newTax->tax_slug = strtolower(preg_replace('/\s+/', '-', $data['tax_name']));
            $newTax->created_date = date('Y-m-d H:i:s');

            $newTax->save();
            return $newTax;
        }



    }
    
}

