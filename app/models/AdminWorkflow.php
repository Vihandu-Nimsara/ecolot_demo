<?php

class AdminWorkflow
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getDashboardStats(): mixed
    {
        $this->db->query("
            SELECT
                COUNT(*) AS total_users,
                SUM(role = 'PUBLIC_USER') AS public_users,
                SUM(role = 'MUNICIPAL_OFFICER') AS municipal_officers,
                SUM(role = 'COLLECTOR') AS collectors,
                SUM(role = 'AUTHORIZED_RECYCLER') AS recyclers,
                SUM(role = 'ADMIN') AS admins,
                SUM(status = 'ACTIVE') AS active_users,
                SUM(status = 'PENDING') AS pending_users,
                SUM(status = 'SUSPENDED') AS suspended_users
            FROM users
        ");

        $userStats = $this->db->single();

        $this->db->query("
            SELECT
                COUNT(*) AS total_recycler_profiles,
                SUM(verification_status = 'PENDING') AS pending_recyclers,
                SUM(verification_status = 'VERIFIED') AS verified_recyclers,
                SUM(verification_status = 'REJECTED') AS rejected_recyclers
            FROM recycler_profiles
        ");

        $recyclerStats = $this->db->single();

        $this->db->query("
            SELECT COUNT(*) AS total_categories
            FROM ewaste_categories
        ");

        $categoryStats = $this->db->single();

        $this->db->query("
            SELECT COUNT(*) AS total_items
            FROM ewaste_items
        ");

        $itemStats = $this->db->single();

        $this->db->query("
            SELECT COUNT(*) AS total_risk_rules
            FROM risk_rules
        ");

        $riskStats = $this->db->single();

        return (object) [
            'total_users' => $userStats->total_users ?? 0,
            'public_users' => $userStats->public_users ?? 0,
            'municipal_officers' => $userStats->municipal_officers ?? 0,
            'collectors' => $userStats->collectors ?? 0,
            'recyclers' => $userStats->recyclers ?? 0,
            'admins' => $userStats->admins ?? 0,
            'active_users' => $userStats->active_users ?? 0,
            'pending_users' => $userStats->pending_users ?? 0,
            'suspended_users' => $userStats->suspended_users ?? 0,

            'total_recycler_profiles' => $recyclerStats->total_recycler_profiles ?? 0,
            'pending_recyclers' => $recyclerStats->pending_recyclers ?? 0,
            'verified_recyclers' => $recyclerStats->verified_recyclers ?? 0,
            'rejected_recyclers' => $recyclerStats->rejected_recyclers ?? 0,

            'total_categories' => $categoryStats->total_categories ?? 0,
            'total_items' => $itemStats->total_items ?? 0,
            'total_risk_rules' => $riskStats->total_risk_rules ?? 0
        ];
    }

    public function getUsers(?string $role = null): array
    {
        $roleSql = '';

        if ($role !== null) {
            $roleSql = "WHERE role = :role";
        }

        $this->db->query("
            SELECT *
            FROM users
            {$roleSql}
            ORDER BY created_at DESC
        ");

        if ($role !== null) {
            $this->db->bind(':role', $role);
        }

        return $this->db->resultSet();
    }

    public function findUserByEmail(string $email): mixed
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

    public function updateUserStatus(int $userId, string $status, int $adminId): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                UPDATE users
                SET status = :status
                WHERE user_id = :user_id
            ");

            $this->db->bind(':status', $status);
            $this->db->bind(':user_id', $userId);
            $this->db->execute();

            $this->db->query("
                INSERT INTO audit_logs
                    (user_id, action, description)
                VALUES
                    (:user_id, 'UPDATE_USER_STATUS', :description)
            ");

            $this->db->bind(':user_id', $adminId);
            $this->db->bind(':description', 'Updated user ID ' . $userId . ' status to ' . $status);
            $this->db->execute();

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getCouncils(): array
    {
        $this->db->query("
            SELECT *
            FROM local_councils
            ORDER BY council_name ASC
        ");

        return $this->db->resultSet();
    }

    public function createPrivilegedUser(array $data): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                INSERT INTO users
                    (full_name, email, phone, password_hash, role, status)
                VALUES
                    (:full_name, :email, :phone, :password_hash, :role, 'ACTIVE')
            ");

            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':phone', $data['phone']);
            $this->db->bind(':password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
            $this->db->bind(':role', $data['role']);
            $this->db->execute();

            $userId = (int) $this->db->lastInsertId();

            if ($data['role'] === 'MUNICIPAL_OFFICER') {
                $this->db->query("
                    INSERT INTO municipal_officer_profiles
                        (user_id, council_id, employee_no, designation)
                    VALUES
                        (:user_id, :council_id, :employee_no, :designation)
                ");

                $this->db->bind(':user_id', $userId);
                $this->db->bind(':council_id', $data['council_id']);
                $this->db->bind(':employee_no', $data['employee_no']);
                $this->db->bind(':designation', $data['designation']);
                $this->db->execute();
            }

            if ($data['role'] === 'COLLECTOR') {
                $this->db->query("
                    INSERT INTO collector_profiles
                        (user_id, council_id, employee_no, availability_status)
                    VALUES
                        (:user_id, :council_id, :employee_no, 'AVAILABLE')
                ");

                $this->db->bind(':user_id', $userId);
                $this->db->bind(':council_id', $data['council_id']);
                $this->db->bind(':employee_no', $data['employee_no']);
                $this->db->execute();
            }

            $this->db->query("
                INSERT INTO audit_logs
                    (user_id, action, description)
                VALUES
                    (:user_id, 'CREATE_PRIVILEGED_USER', :description)
            ");

            $this->db->bind(':user_id', $data['created_by']);
            $this->db->bind(':description', 'Created ' . $data['role'] . ' account for ' . $data['email']);
            $this->db->execute();

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getRecyclerProfiles(?string $status = null): array
    {
        $statusSql = '';

        if ($status !== null) {
            $statusSql = "WHERE rp.verification_status = :status";
        }

        $this->db->query("
            SELECT
                rp.*,
                u.full_name,
                u.email,
                u.phone,
                verifier.full_name AS verified_by_name
            FROM recycler_profiles rp
            INNER JOIN users u ON rp.user_id = u.user_id
            LEFT JOIN users verifier ON rp.verified_by = verifier.user_id
            {$statusSql}
            ORDER BY rp.created_at DESC
        ");

        if ($status !== null) {
            $this->db->bind(':status', $status);
        }

        return $this->db->resultSet();
    }

    public function getRecyclerProfileById(int $profileId): mixed
    {
        $this->db->query("
            SELECT
                rp.*,
                u.full_name,
                u.email,
                u.phone,
                u.status AS user_status
            FROM recycler_profiles rp
            INNER JOIN users u ON rp.user_id = u.user_id
            WHERE rp.recycler_profile_id = :profile_id
            LIMIT 1
        ");

        $this->db->bind(':profile_id', $profileId);

        return $this->db->single();
    }

    public function getRecyclerCapabilities(int $profileId): array
    {
        $this->db->query("
            SELECT
                rc.*,
                c.category_name
            FROM recycler_capabilities rc
            INNER JOIN ewaste_categories c ON rc.category_id = c.category_id
            WHERE rc.recycler_profile_id = :profile_id
            ORDER BY c.category_name ASC
        ");

        $this->db->bind(':profile_id', $profileId);

        return $this->db->resultSet();
    }

    public function verifyRecycler(
        int $profileId,
        int $adminId,
        string $decision,
        array $categoryIds,
        array $highRiskMap
    ): bool {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                SELECT *
                FROM recycler_profiles
                WHERE recycler_profile_id = :profile_id
                LIMIT 1
                FOR UPDATE
            ");

            $this->db->bind(':profile_id', $profileId);
            $profile = $this->db->single();

            if (!$profile) {
                $this->db->rollBack();
                return false;
            }

            if ($decision === 'VERIFIED') {
                $this->db->query("
                    UPDATE recycler_profiles
                    SET verification_status = 'VERIFIED',
                        verified_by = :verified_by,
                        verified_at = NOW()
                    WHERE recycler_profile_id = :profile_id
                ");

                $this->db->bind(':verified_by', $adminId);
                $this->db->bind(':profile_id', $profileId);
                $this->db->execute();

                $this->db->query("
                    UPDATE users
                    SET status = 'ACTIVE'
                    WHERE user_id = :user_id
                ");

                $this->db->bind(':user_id', $profile->user_id);
                $this->db->execute();

                $this->db->query("
                    DELETE FROM recycler_capabilities
                    WHERE recycler_profile_id = :profile_id
                ");

                $this->db->bind(':profile_id', $profileId);
                $this->db->execute();

                foreach ($categoryIds as $categoryId) {
                    $categoryId = (int) $categoryId;
                    $canHighRisk = isset($highRiskMap[$categoryId]) ? 1 : 0;

                    $this->db->query("
                        INSERT INTO recycler_capabilities
                            (recycler_profile_id, category_id, can_handle_high_risk, status)
                        VALUES
                            (:profile_id, :category_id, :can_handle_high_risk, 'ACTIVE')
                    ");

                    $this->db->bind(':profile_id', $profileId);
                    $this->db->bind(':category_id', $categoryId);
                    $this->db->bind(':can_handle_high_risk', $canHighRisk);
                    $this->db->execute();
                }
            }

            if ($decision === 'REJECTED') {
                $this->db->query("
                    UPDATE recycler_profiles
                    SET verification_status = 'REJECTED',
                        verified_by = :verified_by,
                        verified_at = NOW()
                    WHERE recycler_profile_id = :profile_id
                ");

                $this->db->bind(':verified_by', $adminId);
                $this->db->bind(':profile_id', $profileId);
                $this->db->execute();

                $this->db->query("
                    UPDATE users
                    SET status = 'REJECTED'
                    WHERE user_id = :user_id
                ");

                $this->db->bind(':user_id', $profile->user_id);
                $this->db->execute();
            }

            $this->db->query("
                INSERT INTO audit_logs
                    (user_id, action, description)
                VALUES
                    (:user_id, 'VERIFY_RECYCLER', :description)
            ");

            $this->db->bind(':user_id', $adminId);
            $this->db->bind(':description', 'Recycler profile ID ' . $profileId . ' marked as ' . $decision);
            $this->db->execute();

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getCategories(): array
    {
        $this->db->query("
            SELECT
                c.*,
                COUNT(i.item_id) AS item_count
            FROM ewaste_categories c
            LEFT JOIN ewaste_items i ON c.category_id = i.category_id
            GROUP BY c.category_id, c.category_name, c.description, c.status, c.created_at
            ORDER BY c.category_name ASC
        ");

        return $this->db->resultSet();
    }

    public function createCategory(array $data): bool
    {
        $this->db->query("
            INSERT INTO ewaste_categories
                (category_name, description, status)
            VALUES
                (:category_name, :description, 'ACTIVE')
        ");

        $this->db->bind(':category_name', $data['category_name']);
        $this->db->bind(':description', $data['description']);

        return $this->db->execute();
    }

    public function updateCategoryStatus(int $categoryId, string $status): bool
    {
        $this->db->query("
            UPDATE ewaste_categories
            SET status = :status
            WHERE category_id = :category_id
        ");

        $this->db->bind(':status', $status);
        $this->db->bind(':category_id', $categoryId);

        return $this->db->execute();
    }

    public function getItems(): array
    {
        $this->db->query("
            SELECT
                i.*,
                c.category_name
            FROM ewaste_items i
            INNER JOIN ewaste_categories c ON i.category_id = c.category_id
            ORDER BY c.category_name ASC, i.item_name ASC
        ");

        return $this->db->resultSet();
    }

    public function createItem(array $data): bool
    {
        $this->db->query("
            INSERT INTO ewaste_items
                (category_id, item_name, collection_status, default_risk_level)
            VALUES
                (:category_id, :item_name, :collection_status, :default_risk_level)
        ");

        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':item_name', $data['item_name']);
        $this->db->bind(':collection_status', $data['collection_status']);
        $this->db->bind(':default_risk_level', $data['default_risk_level']);

        return $this->db->execute();
    }

    public function getRiskRules(): array
    {
        $this->db->query("
            SELECT
                rr.*,
                c.category_name,
                i.item_name
            FROM risk_rules rr
            LEFT JOIN ewaste_categories c ON rr.category_id = c.category_id
            LEFT JOIN ewaste_items i ON rr.item_id = i.item_id
            ORDER BY rr.risk_rule_id DESC
        ");

        return $this->db->resultSet();
    }

    public function createRiskRule(array $data): bool
    {
        $this->db->query("
            INSERT INTO risk_rules
                (
                    category_id,
                    item_id,
                    condition_status,
                    risk_level,
                    rule_description,
                    status
                )
            VALUES
                (
                    :category_id,
                    :item_id,
                    :condition_status,
                    :risk_level,
                    :rule_description,
                    'ACTIVE'
                )
        ");

        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':item_id', $data['item_id']);
        $this->db->bind(':condition_status', $data['condition_status']);
        $this->db->bind(':risk_level', $data['risk_level']);
        $this->db->bind(':rule_description', $data['rule_description']);

        return $this->db->execute();
    }

    public function updateRiskRuleStatus(int $ruleId, string $status): bool
    {
        $this->db->query("
            UPDATE risk_rules
            SET status = :status
            WHERE risk_rule_id = :rule_id
        ");

        $this->db->bind(':status', $status);
        $this->db->bind(':rule_id', $ruleId);

        return $this->db->execute();
    }
}