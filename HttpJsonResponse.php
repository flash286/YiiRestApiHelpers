<?php
/**
 * Created by PhpStorm.
 * User: nikolas
 * Date: 27.05.14
 * Time: 12:39
 */

class HttpJsonResponse extends CApplicationComponent {


    protected  $collectionContainer = 'data';
    protected  $totalCountKey = 'totalCount';
    public $resource;

    public function __construct(Resource $resource) {
        $this->resource = $resource;
        if (!$this->resource->isSerialized()) {
            $this->resource->serialize();
        }
    }

    protected function toJson($data) {
        return CJSON::encode($data);
    }

    public function send() {
        header('Content-type: application/json');
        $data = [
            $this->collectionContainer => $this->resource->serialized_data
        ];
        if ($this->resource->total) {
            $data[$this->totalCountKey] = $this->resource->total;
        }
        echo $this->toJson($data);
        Yii::app()->end();
    }
}
