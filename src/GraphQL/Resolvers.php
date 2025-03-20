<?php

namespace App\GraphQL;

use App\Config\Database;
use Exception;



class Resolvers
{
    public static function getProducts($root, $args)
    {
        $db = Database::getConnection();

        $query = "
    SELECT 
        p.id, p.name, p.category_id, p.in_stock, p.description, p.brand, 
        pp.amount AS price, pp.currency_label, pp.currency_symbol,
        pg.image_url,
        a.id AS attribute_id, a.name AS attribute_name, a.type AS attribute_type,
        pav.id AS value_id, pav.value, pav.display_value
    FROM products p
    LEFT JOIN product_prices pp ON p.id = pp.product_id
    LEFT JOIN product_gallery pg ON p.id = pg.product_id
    LEFT JOIN product_attribute_values pav ON p.id = pav.product_id
    LEFT JOIN attributes a ON pav.attribute_id = a.id
    ";

        if (!empty($args['categoryId']) && $args['categoryId'] !== 1) {
            $query .= " WHERE p.category_id = :categoryId";
        }

        $stmt = $db->prepare($query);

        if (!empty($args['categoryId']) && $args['categoryId'] !== 1) {
            $stmt->bindParam(':categoryId', $args['categoryId'], \PDO::PARAM_INT);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $products = [];
        foreach ($rows as $row) {
            $productId = $row['id'];

            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'categoryId' => $row['category_id'],
                    'inStock' => (bool)$row['in_stock'],
                    'description' => $row['description'],
                    'brand' => $row['brand'],
                    'prices' => [],
                    'attributes' => [],
                    'gallery' => [],
                ];
            }

            // ✅ Add price with `currency` object
            if ($row['price'] && empty($products[$productId]['prices'])) {
                $products[$productId]['prices'][] = [
                    'amount' => (float)$row['price'],
                    'currency' => [
                        'label' => $row['currency_label'],
                        'symbol' => $row['currency_symbol'],
                    ],
                ];
            }

            // ✅ Add product image if not already added
            if (!empty($row['image_url']) && !in_array($row['image_url'], $products[$productId]['gallery'])) {
                $products[$productId]['gallery'][] = $row['image_url'];
            }

            // ✅ Add attributes with `id`
            if ($row['attribute_name']) {
                $attributeExists = false;
                foreach ($products[$productId]['attributes'] as &$attribute) {
                    if ($attribute['name'] === $row['attribute_name']) {
                        $attributeExists = true;

                        // Check if attribute item already exists
                        $itemExists = false;
                        foreach ($attribute['items'] as $item) {
                            if ($item['value'] === $row['value']) {
                                $itemExists = true;
                                break;
                            }
                        }

                        // Add only if it doesn't exist
                        if (!$itemExists) {
                            $attribute['items'][] = [
                                'id' => $row['value_id'], // ✅ Include `id` here
                                'displayValue' => $row['display_value'],
                                'value' => $row['value'],
                            ];
                        }
                        break;
                    }
                }

                // If attribute doesn't exist, add it
                if (!$attributeExists) {
                    $products[$productId]['attributes'][] = [
                        'id' => $row['attribute_id'], // ✅ Include `id`
                        'name' => $row['attribute_name'],
                        'type' => $row['attribute_type'],
                        'items' => [
                            [
                                'id' => $row['value_id'], // ✅ Include `id`
                                'displayValue' => $row['display_value'],
                                'value' => $row['value'],
                            ],
                        ],
                    ];
                }
            }
        }

        return array_values($products);
    }

    public static function getProductById($root, $args)
    {
        try {
            $db = Database::getConnection();

            $query = "
            SELECT 
                p.id, p.name, p.category_id, p.in_stock, p.description, p.brand,
                pp.amount AS price, pp.currency_label, pp.currency_symbol,
                pg.image_url,
                a.name AS attribute_name, a.type AS attribute_type,
                pav.value, pav.display_value, pav.id AS attribute_id
            FROM products p
            LEFT JOIN product_prices pp ON p.id = pp.product_id
            LEFT JOIN product_gallery pg ON p.id = pg.product_id
            LEFT JOIN product_attribute_values pav ON p.id = pav.product_id
            LEFT JOIN attributes a ON pav.attribute_id = a.id
            WHERE p.id = :id
        ";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $args['id'], \PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null; // Product not found
            }

            $product = [
                'id' => $rows[0]['id'],
                'name' => $rows[0]['name'],
                'categoryId' => $rows[0]['category_id'],
                'inStock' => (bool)$rows[0]['in_stock'],
                'description' => $rows[0]['description'],
                'brand' => $rows[0]['brand'],
                'prices' => [],
                'attributes' => [],
                'gallery' => [],
            ];

            foreach ($rows as $row) {
                // ✅ Add price if not already added
                if ($row['price'] && empty($product['prices'])) {
                    $product['prices'][] = [
                        'amount' => (float)$row['price'],
                        'currency' => [
                            'label' => $row['currency_label'],
                            'symbol' => $row['currency_symbol'],
                        ],
                    ];
                }

                // ✅ Add gallery images if not already added
                if (!empty($row['image_url']) && !in_array($row['image_url'], $product['gallery'])) {
                    $product['gallery'][] = $row['image_url'];
                }

                // ✅ Ensure attributes don't get duplicated
                if ($row['attribute_name']) {
                    $attributeKey = $row['attribute_name']; // Use attribute name as key

                    if (!isset($product['attributes'][$attributeKey])) {
                        $product['attributes'][$attributeKey] = [
                            'name' => $row['attribute_name'],
                            'type' => $row['attribute_type'],
                            'items' => [],
                        ];
                    }

                    // ✅ Check if value already exists before adding
                    $existingValues = array_column($product['attributes'][$attributeKey]['items'], 'value');
                    if (!in_array($row['value'], $existingValues)) {
                        $product['attributes'][$attributeKey]['items'][] = [
                            'id' => $row['attribute_id'],
                            'displayValue' => $row['display_value'],
                            'value' => $row['value'],
                        ];
                    }
                }
            }

            // ✅ Convert associative array back to indexed array for attributes
            $product['attributes'] = array_values($product['attributes']);

            return $product;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }





    public static function getCategoriesFromDatabase()
    {
        $db = Database::getConnection();

        $query = "SELECT id, name FROM categories";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
            ];
        }

        return $categories;
    }


    public static function placeOrder($root, $args)
    {
        try {
            $db = Database::getConnection();
            $items = $args['items']; // Array of product IDs

            if (empty($items)) {
                return ['success' => false, 'message' => 'No items to order'];
            }

            // Convert items array to JSON format for storage
            $itemsJson = json_encode($items);

            // Insert Order into Database
            $stmt = $db->prepare("INSERT INTO orders (items, created_at) VALUES (:items, NOW())");
            $stmt->bindParam(":items", $itemsJson);
            $stmt->execute();

            return ['success' => true, 'message' => 'Order placed successfully'];
        } catch (Exception $e) { // ✅ Catch Exception
            return ['success' => false, 'message' => 'Error placing order: ' . $e->getMessage()];
        }
    }
}
