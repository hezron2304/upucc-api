<?php
namespace App\Core;

class Paginator
{

    public static function paginate($conn, $table, $page = 1, $limit = 10, $where = '', $params = [], $types = '')
    {
        $page = max(1, (int) $page);
        $limit = max(1, (int) $limit);
        $offset = ($page - 1) * $limit;

        // Count Total
        $countQuery = "SELECT COUNT(*) as total FROM $table $where";
        $stmt = $conn->prepare($countQuery);
        if ($where && $params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalResult = $stmt->get_result()->fetch_assoc();
        $total = $totalResult['total'];
        $totalPages = ceil($total / $limit);

        // Fetch Data
        // Note: Logic for generic select * is simple. Specific Selects need custom handling.
        // This is a basic Helper.
        return [
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total
        ];
    }
}
?>