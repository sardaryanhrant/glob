<?php

namespace app\modules\v1\models;

use yii\base\Model;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\web\Link;

class TokenResource extends Model 
{
    /**
     * @var string
     */
    public $token;

    /**
     * @var integer
     */
    public $expired;

    /**
     * @var array
     */
    protected $relationships = [];

    public function getLinks()
    {
        return [
            Link::REL_SELF => Url::to(Url::base(true).'/v1/users/token'),
        ];
    }

    public function getRelationshipLinks($name)
    {
        return [];
    }

    public function getId()
    {
        return '';
    }

    public function getType()
    {
        return 'token';
    }

    public function getResourceAttributes(array $fields = [])
    {
        $attributes = $this->fields();
        foreach ($attributes as $key => $attribute) {
            $attribute = Inflector::camel2id(Inflector::variablize($attribute), '_');

            if (!empty($fields) && !in_array($attribute, $fields, true)) {
                unset($attributes[$key]);
            } else {
                $attributes[$key] = $this->$attribute;
            }
        }

        return $attributes;
    }

    public function getResourceRelationships(array $linked = [])
    {
        return $this->relationships;
    }

    public function setResourceRelationship($name, $relationship)
    {
        $this->relationships[$name] = $relationship;

        return $this;
    }

}