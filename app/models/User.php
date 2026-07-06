<?php

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function findByEmail(string $email): mixed
    {
        $this->db->query("
            SELECT *
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $this->db->bind(':email', $email);

        return $this->db->single();
    }

    public function createUser(array $data): int
    {
        $this->db->query("
            INSERT INTO users 
                (full_name, email, phone, password_hash, role, status)
            VALUES 
                (:full_name, :email, :phone, :password_hash, :role, :status)
        ");

        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':password_hash', $data['password_hash']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':status', $data['status']);

        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    public function createPublicProfile(int $userId, array $data): bool
    {
        $this->db->query("
            INSERT INTO public_user_profiles
                (user_id, address_line1, address_line2, city, postal_code, area_id)
            VALUES
                (:user_id, :address_line1, :address_line2, :city, :postal_code, :area_id)
        ");

        $this->db->bind(':user_id', $userId);
        $this->db->bind(':address_line1', $data['address_line1']);
        $this->db->bind(':address_line2', $data['address_line2']);
        $this->db->bind(':city', $data['city']);
        $this->db->bind(':postal_code', $data['postal_code']);
        $this->db->bind(':area_id', $data['area_id']);

        return $this->db->execute();
    }

    public function createRecyclerProfile(int $userId, array $data): bool
    {
        $this->db->query("
            INSERT INTO recycler_profiles
                (user_id, company_name, license_no, license_expiry_date, address, verification_status)
            VALUES
                (:user_id, :company_name, :license_no, :license_expiry_date, :address, 'PENDING')
        ");

        $this->db->bind(':user_id', $userId);
        $this->db->bind(':company_name', $data['company_name']);
        $this->db->bind(':license_no', $data['license_no']);
        $this->db->bind(':license_expiry_date', $data['license_expiry_date']);
        $this->db->bind(':address', $data['address']);

        return $this->db->execute();
    }

    public function registerPublicUser(array $data): bool
    {
        try {
            $this->db->beginTransaction();

            $userId = $this->createUser([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => 'PUBLIC_USER',
                'status' => 'ACTIVE'
            ]);

            $this->createPublicProfile($userId, [
                'address_line1' => $data['address_line1'],
                'address_line2' => $data['address_line2'],
                'city' => $data['city'],
                'postal_code' => $data['postal_code'],
                'area_id' => $data['area_id']
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function registerRecycler(array $data): bool
    {
        try {
            $this->db->beginTransaction();

            $userId = $this->createUser([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => 'AUTHORIZED_RECYCLER',
                'status' => 'PENDING'
            ]);

            $this->createRecyclerProfile($userId, [
                'company_name' => $data['company_name'],
                'license_no' => $data['license_no'],
                'license_expiry_date' => $data['license_expiry_date'],
                'address' => $data['recycler_address']
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }
}