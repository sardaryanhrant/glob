<?php

namespace app\modules\v1\models;

use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\web\Link;

class BaseResource extends ActiveRecord
{
    /**
     * @var array
     */
    protected $excludedFields = ['user_password', 'parent_id'];
    protected $relationships = [];

    public static function getExactlySearchFields()
    {
        return [];
    }

    protected $alias;

    public function getType() {}

    public function fields()
    {
        $fields = parent::fields();
        $attributes = array_diff($fields, $this->excludedFields);

        return $attributes;

    }

    public function formName()
    {
        return ucfirst($this->getType());
    }

    /**
     * @param $name
     * @return array
     */
    public function getRelationshipLinks($name)
    {
        return [];
    }

    /**
     * @param array $linked
     * @return array|\tuyakhov\jsonapi\ResourceIdentifierInterface[]
     */
    public function getResourceRelationships(array $linked = [])
    {
        return $this->relationships;
    }

    /**
     * @param $name
     * @param $relationship
     */
    public function setResourceRelationship($name, $relationship)
    {
        $this->relationships[$name] = $relationship;
        return $this;
    }

    /**
     * The "id" member of a resource object.
     * @return string an ID that in pair with type uniquely identifies the resource.
     */
    public function getId()
    {
        return (string) $this->attributes['id'];
    }

    public function getResourceAttributes(array $fields = [])
    {
        $attributes = array_diff($this->fields(), $this->excludedFields);

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

    /**
     * @return array
     */
    public function getLinks()
    {
        //$self_route = [$this->alias, 'id' => $this->getId()];

        return [
          Link::REL_SELF => Url::to(Url::base(true).'/v1/'.str_replace('_', '-', $this->alias).'/'.$this->getId())
        ];
    }

}