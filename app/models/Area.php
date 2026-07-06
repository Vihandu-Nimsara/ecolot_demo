<?php

class Area
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getActiveAreas(): array
    {
        $this->db->query("
            SELECT 
                area_id,
                area_name,
                postal_code
            FROM collection_areas
            WHERE status = 'ACTIVE'
            ORDER BY postal_code ASC
        ");

        return $this->db->resultSet();
    }

    public function findById(int $areaId): mixed
    {
        $this->db->query("
            SELECT *
            FROM collection_areas
            WHERE area_id = :area_id
            AND status = 'ACTIVE'
            LIMIT 1
        ");

        $this->db->bind(':area_id', $areaId);

        return $this->db->single();
    }
}