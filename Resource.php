<?php
/**
 * Created by PhpStorm.
 * User: nikolas
 * Date: 27.05.14
 * Time: 12:43
 */


class Resource {

    public $with;
    public $data;
    public $total;
    public $serialized_data;
    protected $serialized = false;

    public function __construct($data, $with=[], $total = null) {
        $this->with = $with;
        $this->data = $data;
        $this->total = $total;
    }

    public function serialize() {
        $this->serialized_data =  $this->fromObject($this->data);
        $this->serialized = true;
    }

    public function isSerialized() {
        return $this->serialized;
    }

    protected function fromObject(array $object) {
        if (is_array($object)) {
            return $object;
        }
        throw new Exception('Can not be serialized');
    }
}
