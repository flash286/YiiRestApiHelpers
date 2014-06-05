<?php
/**
 * Created by PhpStorm.
 * User: nikolas
 * Date: 27.05.14
 * Time: 13:59
 */

class ModelResource extends Resource {

    public function __construct($data, array $with = [], $total = null) {
        parent::__construct($data, $with, $total);
    }

    public function serialize() {
        if (!$this->isSerialized()){
            if (!isset($this->data)) {
                throw new Exception('Data of Resource is empty');
            }
            if (is_array($this->data)) {
                $this->serialized_data = $this->fromModelList($this->data);
            }
            else {
                $this->serialized_data = $this->fromModel($this->data);
            }
            $this->serialized = true;
        }
    }

    public function getSerializedData() {
        if (!$this->isSerialized()) {
            $this->serialize();
            return $this->serialized_data;
        }
        else {
            return $this->serialized_data;
        }
    }

    protected function getRelationValue(CActiveRecord &$object, CBaseActiveRelation &$relation, $depth=0, $max_depth=2) {
        $result = [];
        if ($relation instanceof CStatRelation) {
            $result = $object->getRelated($relation->name);
        }
        elseif ($relation instanceof CHasManyRelation){
            $related_objects = $object->getRelated($relation->name);
            foreach($related_objects as $related_object) {
                $result[] = $this->fetchModel($related_object);
            }
        }
        else {
            $result = $this->fetchModel($object->getRelated($relation->name));
        }
        if ($depth <= $max_depth) {
            if (isset($relation->with) && !empty($relation->with)) {
                if (!is_array($relation->with)) {
                    throw new Exception('Relation with must be array');
                }
                foreach($relation->with as $withRelation) {

                    $related_objects_with = $object->{$relation->name};

                    if (!is_array($related_objects_with)) {
                        $result[$withRelation] = $this->getRelationValue(
                            $object->{$relation->name}, $object->{$relation->name}->getActiveRelation($withRelation), ++$depth
                        );
                    }
                    else {
                        for ($i = 0; $i < count($related_objects_with); $i++) {
                            $result[$i][$withRelation] = $this->getRelationValue(
                                $related_objects_with[$i], $related_objects_with[$i]->getActiveRelation($withRelation), ++$depth
                            );
                        }
                    }
                }
            }
        }
        return $result;
    }

    protected function fetchModel(CActiveRecord &$object) {
        $data = $object->getAttributes();
        foreach ($data as $property => $value) {
            if (is_null($value)) {
                unset($data[$property]);
                continue;
            }
        }
        return $data;
    }

    protected function fromModel(CActiveRecord $object) {

        $data = $this->fetchModel($object);
        $rel_list = array_merge($this->with, $object->DefaultWith());

        foreach($rel_list as $relationName) {

            if (is_callable([$object, $relationName]) && method_exists($object, $relationName)) {
                $data[$relationName] = $object->{$relationName}();
                continue;
            }
            if (array_key_exists($relationName, $object->relations()) && $relation = $object->getActiveRelation($relationName)) {
                $data[$relationName] = $this->getRelationValue($object, $relation);
            }
            else {
                throw new Exception(sprintf('Model %s has no relation %s', get_class($object), $relationName));
            }
        }
        return $data;
    }

    protected function fromModelList(array $objects) {
        $result = [];
        foreach($objects as $object) {
            $result[] = $this->fromModel($object);
        }
        return $result;
    }

}