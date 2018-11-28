<?php

namespace app\modules\v1\models;


use tuyakhov\jsonapi\LinksInterface;
use tuyakhov\jsonapi\ResourceInterface;
use tuyakhov\jsonapi\ResourceTrait;
use yii\base\Arrayable;

/**
 * Class VaccinesResource
 * @package app\modules\v1\models
 *
 * @property string $name
 * @property string $form
 * @property string $form_form_description
 * @property integer $unit
 * @property integer $active_substance_unit
 * @property string $excipients
 * @property string $packaging
 * @property string $basis
 *
 * @property TmcTypeResource $tmcType
 */
class VaccineResource extends BaseResource  implements LinksInterface, ResourceInterface
{
    use ResourceTrait;

    protected $alias = 'vaccines';
    protected $relationships = ['tmcType' => 'tmc_type'];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['name', 'form'], 'required'],
            [['name'], 'unique', 'on' => ['insert', 'update']],
            ['id_tmc_type', 'exist', 'targetRelation' => 'tmcType'],
            [
                [
                    'id_tmc_type', 'id_manufactured', 'id_representation', 'id_measure', 'id_active_substance',
                    'id_active_substance_measure', 'id_representation'
                ],
                'integer'
            ],
            //['id_manufactured', 'exist', 'targetRelation' => 'manufactured'],
            //['id_representation', 'exist', 'targetRelation' => 'representation'],
            //['id_measure', 'exist', 'targetRelation' => 'measure'],
            //['id_active_substance', 'exist', 'targetRelation' => 'activeSubstance'],
            //['id_active_substance_measure', 'exist', 'targetRelation' => 'activeSubstanceMeasure'],
            //['id_representation', 'exist', 'targetRelation' => 'filePackagingImage'],
            [['name', 'form'], 'string', 'max' => 255],
            ['form_description', 'string', 'max' => 255],
            ['unit', 'integer'],
            ['active_substance_unit', 'integer'],
            ['excipients', 'string', 'max' => 255],
            ['packaging', 'string', 'max' => 255],
            ['basis', 'string', 'max' => 255],
        ];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'vaccine';
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'vaccines';
    }

    /**
     * @return array
     */
    public static function getExactlySearchFields()
    {
        return ['id_tmc_type'];
    }

    public function extraFields()
    {
        return ['tmcType'];
    }

    /**
     * @param array $linked
     * @return array
     */
    public function getResourceRelationships(array $linked = [])
    {
        $fields = [];
        if ($this instanceof Arrayable) {
            $fields = $this->extraFields();
        }
        $resolvedFields = $this->resolveFields($fields);
        $keys = array_keys($resolvedFields);
        $relationships = array_fill_keys($keys, null);
        $linkedFields = array_intersect($keys, $this->relationships);

        foreach ($linkedFields as $name) {
            $definition = $resolvedFields[$name];
            $relationships[$name] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $name);
        }

        return $relationships;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTmcType()
    {
        return $this->hasOne( TmcTypeResource::class, ['id' => 'id_tmc_type']);
    }
}