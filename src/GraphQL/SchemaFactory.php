<?php
namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use App\GraphQL\Types\ProductType;
use App\GraphQL\Types\CategoryType;
use App\GraphQL\Types\OrderResponseType;
use App\GraphQL\Resolvers;

class SchemaFactory
{
    public static function create(): Schema
    {
        $productType = new ProductType();
        $categoryType = new CategoryType();
        $orderResponseType = new OrderResponseType();

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'products' => [
                    'type' => Type::listOf($productType),
                    'args' => ['categoryId' => ['type' => Type::int()]],
                    'resolve' => fn($root, $args) => (new Resolvers())->getProducts($root, $args),
                ],
                'product' => [
                    'type' => $productType,
                    'args' => ['id' => ['type' => Type::string()]],
                    'resolve' => fn($root, $args) => (new Resolvers())->getProductById($root, $args),
                ],
                'categories' => [
                    'type' => Type::listOf($categoryType),
                    'resolve' => fn() => (new Resolvers())->getCategoriesFromDatabase(),
                ],
            ],
        ]);

        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                'placeOrder' => [
                    'type' => $orderResponseType,
                    'args' => [
                        'items' => Type::listOf(Type::string()),
                    ],
                    'resolve' => fn($root, $args) => (new Resolvers())->placeOrder($root, $args),
                ],
            ],
        ]);

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType
        ]);
    }
}