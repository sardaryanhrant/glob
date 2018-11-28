<?php

namespace app\modules\v1\models;

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
class MessageResource extends BaseResource  
{
    protected $alias = 'messages';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['read_status', 'created_at'], 'required', 'on' => ['insert', 'update']],
            [['content', 'attachment_src'], 'string', 'max' => 255],
            [['from_id', 'for_id','read_status','chat_id'], 'integer'],
            // ['chat_id', 'exist', 'targetRelation' => 'chats'],       
        ];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'message';
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'messages';
    }

    public function getAttachments()
    {
        return $this->hasMany(FileResource::class, ['post_id'=>'id'])->onCondition(['post_type'=>'message']);
    }

}