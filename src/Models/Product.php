<?php

class Product {
    private $id;
    private $name;
    private $description;
    private $price;
    private $category_id;

    public function __construct($id, $name, $description, $price, $category_id) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->category_id = $category_id;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getCategoryId() {
        return $this->category_id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function setCategoryId($category_id) {
        $this->category_id = $category_id;
    }
}