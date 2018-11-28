<?php

namespace app\modules\v1\models;
use app\common\components\entity\EntityInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;

/**
 * Class FileResource
 * @package app\modules\v1\models
 *
 * @property string $hash
 * @property string $path
 * @@property string $name
 */
class FileResource extends BaseResource
{
    protected $alias = 'files';
    protected $excludedFields = ['author_id'];
    public $file;


    public function rules()
    {
        /*
            TODO 
                1. 'maxSize'=>1024 * 1024 * 2
                2. At this moment txt, doc, xls files not sending on globstage. If it need we can add extensions in validate rule
        */
        return [
            [
                ['file'],
                'file','skipOnEmpty' => true,
                'mimeTypes' => 'image/jpeg, image/png, multipart/form-data,  application/json, application/vnd.api+json, audio/mpeg, application/vnd.ms-excel, text/plain, application/pdf, application/msword',
                'checkExtensionByMimeType'=>true,
                'extensions' => 'png, gif, jpg, jpeg, mp3, pdf, doc, xls, txt'
            ]
        ];
    }

    public function upload($filepath, $hash)
    {
        if ($this->validate()) {
            $this->file->saveAs( $filepath.'/'.$hash);
            return true;
        } else {
            return false;
        }
    }


    public static function tableName()
    {
        return 'files';
    }


    public function getType()
    {
        return 'file';
    }

    public function getComments(){
        return $this->hasMany(CommentResource::class, ['attachment_id'=>'id'])->with('user')->onCondition(['comment_for'=>'album']);
    }
}