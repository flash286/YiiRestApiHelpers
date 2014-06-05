<?php
/**
 * Created by PhpStorm.
 * User: nikolas
 * Date: 29.04.14
 * Time: 13:14
 */

class ModelCache {



    public function init() {

    }

    /**
     * @var int Время жизни кеша по умолчанию
     */
    static private $default_timeout = 604800; //Week


    /**
     * @param $key
     * @return mixed
     * @throws Exception если переданный ключ не пуст
     */
    public static function get($key) {
        if (!isset($key) or empty($key)) {
            throw new Exception("key can not be empty!");
        }
        $str_data = Yii::app()->redis->getClient()->get($key);
        $result = CJSON::decode($str_data);
        return $result;
    }

    /**
     * @param $key
     * @param mixed $value
     * @param int $time
     */
    public static function set($key, array $value, $time = 3600) {
        $str_data = CJSON::encode($value);
        Yii::app()->redis->getClient()->set($key, $str_data, $time);
    }

    /**
     * @param string $key for delete
     */
    public static function del($key) {
        Yii::app()->redis->getClient()->del($key);
    }

    /**
     * @param $keyStr
     * @return string
     */
    protected static function hashKey($keyStr) {
        return md5($keyStr);
    }

    /**
     * @param string $model model namee
     * @param int $pk primary key
     * @return string
     * @throws Exception if $model or $pk is empty
     */
    public static function getKeyForObject($model, $pk) {
        if (empty($model) || empty($pk)) {
            throw new Exception('Key can not be generated');
        }
        return self::getKey(sprintf("%s:%s", $model, $pk));
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function getKey($key) {
        return $key;
    }

    /**
     *
     * Get all keys by pattern
     *
     * @param string $pattern
     * @return array[]
     */
    public static function getKeys($pattern) {
        return Yii::app()->redis->getClient()->getKeys($pattern);
    }

    /**
     * Delete keys by pattern
     *
     * @param $pattern
     * @return mixed
     */
    public static function deleteByPattern($pattern) {
        $keys = self::getKeys($pattern);
        return Yii::app()->redis->getClient()->del($keys);
    }

    /**
     *
     * Save object to redis
     *
     * @param string $model model name
     * @param int $pk primary key
     * @param array $object serialized object
     */
    public static function saveObjectByPk($model, $pk, $object) {
        $key = self::getKeyForObject($model, $pk);
        self::set($key, $object, self::$default_timeout);
    }

    /**
     *
     * Save to redis list of objects
     *
     * @param string $model model name
     * @param array $data 'pk' => array
     */
    public static function saveObjectsByPk($model, array $data) {
        foreach ($data as $pk => $object) {
            self::saveObjectByPk($model, $pk, $object);
        }
    }

    /**
     * @param string $model model name
     * @param int $pk primary key
     * @return CActiveRecord
     */
    public static function getObjectByPk($model, $pk) {
        $key = self::getKeyForObject($model, $pk);
        return self::get($key);
    }

    /**
     * @param string $model model name
     * @param array $ids list of primary keys
     * @return CActiveRecord[]
     */
    public static function getObjectsByPk($model, array $ids) {
        $result = [];
        foreach($ids as $pk) {
            if ($cached_object = self::getObjectByPk($model, $pk)) {
                $result[$pk] = $cached_object;
            }
        }
        if (count($result) == 0) {
            return [];
        }
        return $result;
    }

    /**
     * @param string $model model name
     * @param string $key
     */
    public static function deleteObject($model, $key) {
        $key = self::getKeyForObject($model, $key);
        self::del($key);
    }

    public static function deleteObjects($model, $pks) {
        foreach($pks as $pk) {
            self::deleteObject($model, $pk);
        }
    }

    public static function generateKeyByCriteria($model, CDbCriteria $criteria) {
        $key  = sprintf("%s:", $model);
        $key .= sprintf("s:%s:l:%s:", $criteria->offset, $criteria->limit);
        $key .= sprintf("%s:", $criteria->condition);
        $key .= sprintf("%s", self::generateKeyByParams($model, $criteria->params));
        return $key;
    }

    public static function generateKeyByParams($model = null, $params) {
        $key = sprintf("%s:", $model);
        foreach($params as $param => $value) {
            $key .= sprintf("%s:%s:", $param, $value);
        }
        return $key;
    }
}