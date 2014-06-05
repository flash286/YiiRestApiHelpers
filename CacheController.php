<?php
/**
 * Created by PhpStorm.
 * User: nikolas
 * Date: 26.05.14
 * Time: 15:35
 */

trait CachedController {

    public function actionGet(array $filter = array(), $start = 0, $limit = 100, array $sort = array(), array $with = array(), $scope = 'list') {

        $ids_for_cache = [];
        // Список айдишников получаем из базы по критериям запроса
        list($objects, $total) = $this->Model()->ids()->Get($filter, $start, $limit, $sort);
        $result_data = [];
        foreach($objects as $object) {
            $result_data[$object->{$this->Model()->getTableSchema()->primaryKey}] = false;
        }
        $cached_objects = Yii::app()->modelCache->getObjectsByPk(get_class($this->Model()), array_keys($result_data));
        // Определяем данные которые есть в кеше и которых нет
        foreach($result_data as $pk => $value) {
            if (array_key_exists($pk, $cached_objects)) {
                $result_data[$pk] = $cached_objects[$pk];
            } else {
                $ids_for_cache[] = $pk;
            }
        }
        // Для данных которых нет в кеше создаем место
        if (count($ids_for_cache) > 0) {
            $criteria = new CDbCriteria;
            $criteria->addInCondition($this->Model()->getTableSchema()->primaryKey, $ids_for_cache);
            $objects_for_cache = $this->Model()->{$scope}()->findAll($criteria);
            $resource = new ModelResource($objects_for_cache, $with, $total);
            $resource->serialize();
            $objects_for_cache = $resource->serialized_data;
            $objects_for_cache_by_pk = [];
            foreach ($objects_for_cache as $item) {
                $objects_for_cache_by_pk[$item[$this->Model()->getTableSchema()->primaryKey]] = $item;
            }
            $objects_for_cache = $objects_for_cache_by_pk;

            unset($objects_for_cache_by_pk);
            unset($objects);
            unset($cached_objects);
            // Добавляем закешированные данные в результируюший массив
            foreach($ids_for_cache as $pk) {
                $result_data[$pk] = $objects_for_cache[$pk];
                Yii::app()->modelCache->saveObjectByPk(get_class($this->Model()), $pk, $objects_for_cache[$pk]);
            }
        }
        $result_data = array_values($result_data);
        (new HttpJsonResponse(new Resource($result_data, [], $total)))->send();
    }
}