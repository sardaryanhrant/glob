<?php
namespace app\modules\v1\controllers;


use app\common\components\entity\EntityException;
use Yii;
use app\common\components\entity\EntityManager;
use app\common\components\entity\EntityResourceFactory;
use app\modules\v1\models\EntityResource;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class EntityController extends BaseController
{
    /**
     * @param $action
     * @return bool
     * @throws NotFoundHttpException
     * @throws \app\common\components\entity\EntityException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $entity_name = $this->getEntityNameFromRequest(\Yii::$app->request);

        /** @var EntityManager $entityManager */
        $entityManager = \Yii::$container->get('entityManager');

        if (is_string($this->modelClass)) {
            if (!$entityManager->validate()) {
                throw new EntityException('Некорректный формат конфига');
            }

            if ($entityInstance = $entityManager->getEntity($entity_name)) {
                \Yii::$container->set('entityInstance', function($container) use ($entity_name) {
                    $manager = $container->get('entityManager');
                    $entity = $manager->getEntity($entity_name);
                    return $entity;
                });
                $this->modelClass = \Yii::$container->get('entityResource');
            }
            else {
                throw new NotFoundHttpException();            }
        }
        return parent::beforeAction($action);
    }

    /**
     * @param $entity
     * @param null $id
     * @return object|ActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function actionGetrel($entity, $id = null) {
        $currentResource = $id ? $this->modelClass::findOne($id) : $this->modelClass;
        EntityResourceFactory::getResource($entity);

        $dataProvider = \Yii::createObject([
            'class' => ActiveDataProvider::class,
            'query' => $currentResource->getActiveQueryForRelation($entity),
        ]);

        return $dataProvider;
    }

    /**
     * @param $id
     * @param $entity
     * @return EntityResource|mixed|null
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\di\NotInstantiableException
     */
    public function actionAddrel($id, string $entity)
    {
        $reltype = $this->modelClass->getRelationType($entity);

        if ($reltype == EntityResource::RELATION_PLURAL) {
            return $this->createRelatedAction($id, $entity);
        }
        $resource = EntityResourceFactory::getResource($entity);
        $this->modelClass = $resource;

        if (!$model = $this->findExistModel($resource, 'name')) {
            $model = $this->runAction('create');
            $data = $model['data'] ?? null;
        }
        else {
            $data = $model->getAttributes();
        }

        try {
            $parent_entity = $this->getEntityNameFromRequest(\Yii::$app->request);
            $parent_resource = EntityResourceFactory::getResource($parent_entity);
            $parent_resource->attachEntityByRelation($entity, $data, $id);
        } catch (Exception $e) {
            // ignore
        }

        return $model;
    }

    /**
     * @param $parent_id
     * @param $entity
     * @param $id
     * @return Response
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function actionDelrel($parent_id, $entity, $id) {
        $parent_entity = $this->getEntityNameFromRequest(\Yii::$app->request);
        $parent_resource = EntityResourceFactory::getResource($parent_entity);
        $parent_resource->detachEntityByRelation($entity, $id, $parent_id);

        return Yii::$app->getResponse()->setStatusCode(204);
    }

    /**
     * @param Request $req
     * @return null
     * @throws \yii\base\InvalidConfigException
     */
    protected function getEntityNameFromRequest(Request $req) {
        $module = \Yii::$app->controller->module;
        $module_id = $module->id;

        $path = $req->getPathInfo();
        preg_match('/' . $module_id . '\/([a-z-]+)/m', $path, $matches);

        $result = $matches[1] ?? null;

        return $result;
    }

    /**
     * @param EntityResource $entityResource
     * @param $field
     * @return null|EntityResource
     * @throws \yii\base\InvalidConfigException
     */
    protected function findExistModel(EntityResource $entityResource, $field) {
        $request = Yii::$app->getRequest();
        $entityResource->load($request->getBodyParams());

        $model = $entityResource::findOne([$field => $entityResource->$field]);

        return $model;
    }


    /**
     * @param $id
     * @param $relname
     * @return EntityResource|bool
     * @throws ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    protected function createRelatedAction($id, $relname)
    {
        $propname = $this->modelClass->getRelationLinkPropertyName($relname);
        $resource = EntityResourceFactory::getResource($relname);

        $attributes = $this->modelClass->getRelationAdditionalFields($relname);
        $params = Yii::$app->getRequest()->getBodyParams();
        try {
            $resource = $resource->saveRelation($id, $propname, $params, $attributes);
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } catch (\Exception $e) {
            throw new ServerErrorHttpException($e->getMessage());
        }

        return $resource;
    }

}