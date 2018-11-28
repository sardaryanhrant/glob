<?php

namespace app\modules\v1\models;

/**
 * Class TaxonomieResource
 * @package app\modules\v1\models
 */
class TaxonomieResource extends BaseResource
{
    protected $alias = 'taxonomies';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['tax_name', 'tax_slug', 'tax_for', 'created_date'], 'required', 'on' => ['insert', 'update']],
            [['tax_name', 'tax_slug', 'tax_for'], 'string']
        ];
    }


    public function getType()
    {
        return 'taxonomie';
    }

    public static function tableName()
    {
        return 'taxonomies';
    }



}