<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ProductType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Product',
            'fields' => function () { // ✅ Lazy-load fields to prevent circular references
                return [
                    'id' => Type::string(),
                    'name' => Type::string(),
                    'categoryId' => Type::int(),
                    'inStock' => Type::boolean(),
                    'description' => Type::string(),
                    'brand' => Type::string(),
                    'prices' => Type::listOf(new ObjectType([
                        'name' => 'Price',
                        'fields' => [
                            'amount' => Type::float(),
                            'currency' => new ObjectType([
                                'name' => 'Currency',
                                'fields' => [
                                    'label' => Type::string(),
                                    'symbol' => Type::string(),
                                ],
                            ]),
                        ],
                    ])),
                    'attributes' => Type::listOf(new ObjectType([
                        'name' => 'AttributeSet',
                        'fields' => [
                            'id' => Type::string(),
                            'name' => Type::string(),
                            'type' => Type::string(),
                            'items' => Type::listOf(new ObjectType([
                                'name' => 'Attribute',
                                'fields' => [
                                    'id' => Type::string(),
                                    'displayValue' => Type::string(),
                                    'value' => Type::string(),
                                ],
                            ])),
                        ],
                    ])),
                    'gallery' => Type::listOf(Type::string()),
                ];
            }
        ]);
    }
}