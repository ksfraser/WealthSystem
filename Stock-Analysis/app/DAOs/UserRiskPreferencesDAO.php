<?php

namespace App\DAOs;

use App\Core\Database;
use App\Models\UserRiskPreferences;
use PDO;

/**
 * User Risk Preferences Data Access Object
 * 
 * Manages persistence of user risk management preferences including:
 * - Loading user-specific risk settings
 * - Updating risk configuration
 * - Managing risk profile templates
 * - Strategy-specific overrides
 */
class UserRiskPreferencesDAO
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get risk preferences for a user
     * Creates default preferences if none exist
     * 
     * @param int $userId
     * @return UserRiskPreferences
     */
    public function getUserPreferences(int $userId): UserRiskPreferences
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_risk_preferences 
            WHERE user_id = :user_id
            LIMIT 1
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $preferences = new UserRiskPreferences();
            $preferences->fromArray($data);
            return $preferences;
        }
        
        // Create default preferences for user
        return $this->createDefaultPreferences($userId);
    }
    
    /**
     * Create default preferences for a new user
     * 
     * @param int $userId
     * @param string $riskProfile Default risk profile (balanced)
     * @return UserRiskPreferences
     */
    public function createDefaultPreferences(int $userId, string $riskProfile = 'balanced'): UserRiskPreferences
    {
        $preferences = new UserRiskPreferences();
        $preferences->setUserId($userId);
        $preferences->setRiskProfile($riskProfile);
        
        $this->save($preferences);
        
        return $preferences;
    }
    
    /**
     * Save or update user preferences
     * 
     * @param UserRiskPreferences $preferences
     * @return bool
     */
    public function save(UserRiskPreferences $preferences): bool
    {
        $data = $preferences->toArray();
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Check if exists
        $existing = $this->findByUserId($data['user_id']);
        
        if ($existing) {
            // Update
            return $this->update($data);
        } else {
            // Insert
            return $this->insert($data);
        }
    }
    
    /**
     * Insert new preferences
     */
    private function insert(array $data): bool
    {
        $sql = "INSERT INTO user_risk_preferences (
            user_id, default_position_size, max_positions, max_portfolio_exposure,
            default_stop_loss, default_take_profit, default_max_holding_days,
            enable_trailing_stop, trailing_stop_activation, trailing_stop_distance,
            enable_partial_profits, partial_profit_levels,
            risk_profile, commission_rate, slippage_rate, initial_capital,
            strategy_overrides, created_at, updated_at
        ) VALUES (
            :user_id, :default_position_size, :max_positions, :max_portfolio_exposure,
            :default_stop_loss, :default_take_profit, :default_max_holding_days,
            :enable_trailing_stop, :trailing_stop_activation, :trailing_stop_distance,
            :enable_partial_profits, :partial_profit_levels,
            :risk_profile, :commission_rate, :slippage_rate, :initial_capital,
            :strategy_overrides, :created_at, :updated_at
        )";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    /**
     * Update existing preferences
     */
    private function update(array $data): bool
    {
        $sql = "UPDATE user_risk_preferences SET
            default_position_size = :default_position_size,
            max_positions = :max_positions,
            max_portfolio_exposure = :max_portfolio_exposure,
            default_stop_loss = :default_stop_loss,
            default_take_profit = :default_take_profit,
            default_max_holding_days = :default_max_holding_days,
            enable_trailing_stop = :enable_trailing_stop,
            trailing_stop_activation = :trailing_stop_activation,
            trailing_stop_distance = :trailing_stop_distance,
            enable_partial_profits = :enable_partial_profits,
            partial_profit_levels = :partial_profit_levels,
            risk_profile = :risk_profile,
            commission_rate = :commission_rate,
            slippage_rate = :slippage_rate,
            initial_capital = :initial_capital,
            strategy_overrides = :strategy_overrides,
            updated_at = :updated_at
        WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    /**
     * Find preferences by user ID (raw data)
     */
    private function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_risk_preferences 
            WHERE user_id = :user_id
            LIMIT 1
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ?: null;
    }
    
    /**
     * Delete user preferences
     * 
     * @param int $userId
     * @return bool
     */
    public function delete(int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM user_risk_preferences 
            WHERE user_id = :user_id
        ");
        
        return $stmt->execute(['user_id' => $userId]);
    }
    
    /**
     * Get all users with specific risk profile
     * 
     * @param string $profile
     * @return array Array of UserRiskPreferences
     */
    public function getUsersByProfile(string $profile): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_risk_preferences 
            WHERE risk_profile = :profile
            ORDER BY user_id
        ");
        
        $stmt->execute(['profile' => $profile]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $preferences = [];
        foreach ($results as $data) {
            $pref = new UserRiskPreferences();
            $pref->fromArray($data);
            $preferences[] = $pref;
        }
        
        return $preferences;
    }
    
    /**
     * Get risk profile template (user_id = 0)
     * 
     * @param string $profile conservative|balanced|aggressive
     * @return UserRiskPreferences
     */
    public function getProfileTemplate(string $profile): UserRiskPreferences
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_risk_preferences 
            WHERE user_id = 0 AND risk_profile = :profile
            LIMIT 1
        ");
        
        $stmt->execute(['profile' => $profile]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $preferences = new UserRiskPreferences();
            $preferences->fromArray($data);
            return $preferences;
        }
        
        // Return default if template not found
        $preferences = new UserRiskPreferences();
        $preferences->setRiskProfile($profile);
        return $preferences;
    }
    
    /**
     * Update single preference field
     * 
     * @param int $userId
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function updateField(int $userId, string $field, $value): bool
    {
        $allowedFields = [
            'default_position_size', 'max_positions', 'max_portfolio_exposure',
            'default_stop_loss', 'default_take_profit', 'default_max_holding_days',
            'enable_trailing_stop', 'trailing_stop_activation', 'trailing_stop_distance',
            'enable_partial_profits', 'risk_profile'
        ];
        
        if (!in_array($field, $allowedFields)) {
            return false;
        }
        
        $sql = "UPDATE user_risk_preferences 
                SET {$field} = :value, updated_at = :updated_at 
                WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId
        ]);
    }
    
    /**
     * Check if user has preferences configured
     * 
     * @param int $userId
     * @return bool
     */
    public function hasPreferences(int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM user_risk_preferences 
            WHERE user_id = :user_id
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
}
