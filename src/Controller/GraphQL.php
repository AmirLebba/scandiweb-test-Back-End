<?php

namespace App\Controller;

use App\GraphQL\SchemaFactory;
use GraphQL\GraphQL as GraphQLBase;
use RuntimeException;
use Throwable;

class GraphQL
{
    public static function handle()
    {
        try {
            $schema = SchemaFactory::create();

            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) {
                throw new RuntimeException('Failed to read input');
            }

            $input = json_decode($rawInput, true);
            $query = $input['query'];
            $variables = $input['variables'] ?? null;

            $result = GraphQLBase::executeQuery($schema, $query, null, null, $variables);
            $output = $result->toArray();
        } catch (Throwable $e) {
            $output = ['error' => ['message' => $e->getMessage()]];
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($output);
    }
}