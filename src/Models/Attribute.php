<?php

class Attribute {
    private $id;
    private $name;
    private $value;

    public function __construct($id, $name, $value) {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setValue($value) {
        $this->value = $value;
    }
}