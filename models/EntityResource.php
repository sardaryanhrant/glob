<?php

namespace app\modules\v1\models;

use app\common\components\entity\EntityRules;
use Yii;
use app\common\components\entity\EntityInstance;
use app\common\components\entity\EntityResourceFactory;
use yii\base\UnknownMethodException;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\helpers\BaseInflector;
use yii\helpers\Url;
use yii\web\Link;

class EntityResource extends BaseResource
{
    const RELATION_PLURAL = 'plural';
    const RELATION_SINGLE = 'single';
    const RELATION_MANY   = 'many';

    /**
     * @var EntityInstance
     */
    public $entityInstance;

    /**
     * @var EntityResource[]
     */
    protected $relationships = [];

    /**
     * @var EntityRules
     */
    protected $entityRules;
    protected $relationshipsMap = [];

    protected $extraFields = [];

    /**
     * @var EntityInstance
     */
    protected $injectedInstance;

    /**
     * EntityResource constructor.
     * @param array $config
     * @param null $injectedInstance
     * @param EntityRules $entityRules
     */
    public function __construct(array $config = [], $injectedInstance = null)
    {
        $this->entityRules = Yii::$container->get('entityRules');
        $this->injectedInstance = $injectedInstance;
        parent::__construct($config);
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public static function tableName()
    {
        $instance = Yii::$container->get('entityInstance');
        return $instance->getTableName();
    }

    /**
     * @param string $name
     * @return EntityResource|mixed|null
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function __get($name)
    {
        if ($rel = $this->getRelationByPropName($name)) {
            return $rel;
        }

        return parent::__get($name);
    }

    public function __call($name, $params)
    {
        $pattern = '/get([a-z]+)/i';
        $res = null;

        try {
            $res = parent::__call($name, $params);
        }
        catch (UnknownMethodException $e) {
            if (preg_match($pattern, $name, $matches)) {
                $relname = strtolower($matches[1]);
                $activeQuery = $this->getActiveQueryForSingleRelation($relname);

                if ($activeQuery !== false) {
                    $res = $activeQuery;
                }
            }
        }

        return $res;
    }


    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function init()
    {
        parent::init();

        $this->entityInstance = $this->injectedInstance ?? \Yii::$container->get('entityInstance');
//        $this->initRelationships();
        $this->extraFields = $this->entityInstance->getRelationships();
    }

    /**
     * @param EntityInstance $entityInstance
     */
    public function setEntityInstance(EntityInstance $entityInstance): void
    {
        $this->entityInstance = $entityInstance;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->entityInstance->getTypeName();
    }

    /**
     * @return array
     */
    public function rules()
    {
        $this->entityRules->init($this->entityInstance);
        $rules = $this->entityRules->getRules();

        return $rules;
    }

    /**
     * @param array $linked
     * @return EntityResource[]|array|ResourceIdentifierInterface[]
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function getResourceRelationships(array $linked = [])
    {
        $this->initRelationships();

        return $this->relationships;
    }

    /**
     * @param array $linked
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function initRelationships(array $linked = [])
    {
        $entityManager = \Yii::$container->get('entityManager');

        $relationships = $this->entityInstance->getRelationships();
        $nestedCollections = $this->entityInstance->getAllNestedCollections();
        $this->relationshipsMap = $relationships;

        if (count($this->relationships)) {
            return true;
        }

        foreach ($relationships as $relationship) {
            $method = 'get' . BaseInflector::camelize($relationship['link']);
            if (method_exists($this, $method)) {
                $this->relationships[$relationship['link']] = $this->$method($this->{$relationship['property']});
            } else {
                $instance = $entityManager->getEntity($relationship['link']);
                \Yii::$container->set('entityInstance', $instance);
                $resource = \Yii::$container->get('entityResource', [[], $instance]);

                $this->relationships[$resource->getType()] = $resource::findOne($this->{$relationship['property']});
            }
        }

        foreach ($nestedCollections as $collection) {
            $activeQuery = $this->getActiveQueryForPluralRelation($collection->getEntityCollectionName());
            $this->relationships[$collection->getEntityCollectionName()] = $activeQuery->all();
        }

        return true;
    }

    /**
     * @param $id
     * @return null|TmcResource
     */
    public function getTmc($id)
    {
        return TmcResource::findOne($id);
    }

    /**
     * @return array
     */
    public function extraFields()
    {
        return $this->extraFields;
    }

    /**
     * @return array
     */
    public function getLinks()
    {


        return [
          Link::REL_SELF => Url::to(Url::base(true) . '/v1/'.$this->entityInstance->getAliasName() .'/'. $this->getId()),
        ];
    }

    /**
     * @param $relation
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function getActiveQueryForPluralRelation($relation)
    {
        $result = null;
        $collection = $this->entityInstance->getNestedCollection($relation);

        if ($collection) {
            $relResource = EntityResourceFactory::getResource($relation);

            if ($collection->isPlural()) {
                $result = $this->hasMany($relResource::className(), $collection->getLinkFields());
            } else {
                $result = $this->hasMany($relResource::className(), $collection->getLinkFields())
                    ->viaTable($collection->getJunctionTableName(), $collection->getJunctionFields(),
                        $collection->getJunctionRelationDecorator());
            }
        }

        return $result;
    }

    public function getActiveQueryForSingleRelation($relname)
    {
        $result = null;
        $relation = $this->entityInstance->getRelationByField($relname, 'propname');

        if ($relation) {
            $relResource = EntityResourceFactory::getResource($relation['link']);

            $result = $this->hasOne($relResource::className(), ['id' => $relation['property']]);
        }

        return $result;
    }

    /**
     * @param $relation
     * @param $entity_data
     * @param $id
     * @throws \yii\db\Exception
     */
    public function attachEntityByRelation($relation, $entity_data, $id) {
        $relatedCollection = $this->entityInstance->getNestedCollection($relation);

        if ($relatedCollection) {
            $ins_data = [];

            $ins_data[$relatedCollection->getPrimaryKey()] = $entity_data['id'];
            $ins_data[$relatedCollection->getForeignKey()] = $id;

            self::getDb()->createCommand()->insert($relatedCollection->getJunctionTableName(), $ins_data)->execute();
        }
    }

    /**
     * @param $relation
     * @param $id
     * @param $parent_id
     * @throws \yii\db\Exception
     */
    public function detachEntityByRelation($relation, $id, $parent_id) {
        $relatedCollection = $this->entityInstance->getNestedCollection($relation);

        if ($relatedCollection) {
            $del_cond = [$relatedCollection->getPrimaryKey() => $id, $relatedCollection->getForeignKey() => $parent_id];
            self::getDb()->createCommand()->delete($relatedCollection->getJunctionTableName(), $del_cond)->execute();
        }
    }

    /**
     * @param $relname
     * @return null|string
     */
    public function getRelationType($relname)
    {
        $type = null;

        if ($col = $this->entityInstance->getNestedCollection($relname)) {
            $type = $col->isPlural() ? self::RELATION_PLURAL : self::RELATION_MANY;
        }
        else if ($this->entityInstance->getRelationByField($relname)) {
            $type = self::RELATION_SINGLE;
        }

        return $type;
    }

    /**
     * @param $relname
     * @return mixed|null
     */
    public function getRelationLinkPropertyName($relname)
    {
        $res = null;

        if ($col = $this->entityInstance->getNestedCollection($relname)) {
            $res = $col->getForeignKey();
        }

        return $res;
    }

    /**
     * @param $relname
     * @return array
     */
    public function getRelationAdditionalFields($relname) {
        $res = [];
        $rel = $this->entityInstance->getNestedCollection($relname);

        if ($rel) {
            $res = $rel->getAdditionalFields();
        }

        return $res;
    }

    /**
     * @param $name
     * @return EntityResource|null
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    protected function getRelationByPropName($name)
    {
        $res = false;

        $relationships = $this->entityInstance->getRelationships();
        $this->relationshipsMap = $relationships;

        foreach ($this->relationshipsMap as $rel) {
            if (isset($rel['propname']) && $rel['propname'] == $name) {
                $relname = $rel['link'];
                $alias = EntityInstance::inflectTypeName($relname);

                $this->initRelationships();

                if (array_key_exists($relname, $this->relationships)) {
                    $res = $this->relationships[$relname];
                    break;
                }

                if (array_key_exists($alias, $this->relationships)) {
                    $res = $this->relationships[$alias];
                    break;
                }
            }
        }

        return $res;
    }

    /**
     * @param $id
     * @param $field
     * @param $params
     * @param $attributes
     * @return bool
     */
    public function saveRelation($id, $field, $params, $attributes)
    {
        $this->setScenario('insert');
        $this->load($params);
        $this->$field = $id;
        $this->setAttributes($attributes);

        $this->save();
        return $this;
    }
}