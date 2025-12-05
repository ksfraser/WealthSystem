<?php

declare(strict_types=1);

namespace App\Alert;

use App\Services\EmailService;
use InvalidArgumentException;

/**
 * Advanced Alert Engine
 * 
 * Manages custom alert conditions, evaluates them against market data,
 * and triggers notifications when conditions are met.
 * 
 * Features:
 * - Custom alert creation and management
 * - Multi-condition alert support
 * - Alert throttling to prevent spam
 * - Alert history tracking
 * - User-specific alerts
 * 
 * @package App\Alert
 */
class AlertEngine
{
    private EmailService $emailService;
    private array $alerts = [];
    private array $alertHistory = [];
    private array $lastTriggered = [];
    private int $nextAlertId = 1;

    /**
     * Create new alert engine
     *
     * @param EmailService $emailService Email service for notifications
     */
    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Load alerts from repository into engine
     *
     * @param array<int, \App\Models\Alert> $repositoryAlerts Alerts from repository
     * @param bool $replace Whether to replace existing alerts (default: true)
     * @return int Number of alerts loaded
     */
    public function loadAlertsFromRepository(array $repositoryAlerts, bool $replace = true): int
    {
        if ($replace) {
            $this->alerts = [];
            $this->nextAlertId = 1;
        }

        $loadedCount = 0;

        foreach ($repositoryAlerts as $alert) {
            $alertId = $this->nextAlertId++;

            // Create condition from alert model
            $condition = new AlertCondition(
                $alert->getConditionType(),
                $alert->getThreshold()
            );

            $this->alerts[$alertId] = [
                'id' => $alertId,
                'user_id' => $alert->getUserId(),
                'name' => $alert->getName(),
                'symbol' => $alert->getSymbol(),
                'conditions' => [$condition],
                'email' => $alert->getEmail(),
                'throttle_minutes' => $alert->getThrottleMinutes(),
                'active' => $alert->isActive(),
                'created_at' => $alert->getCreatedAt()->getTimestamp()
            ];

            $loadedCount++;
        }

        return $loadedCount;
    }

    /**
     * Create a new alert
     *
     * @param array<string, mixed> $config Alert configuration
     * @return int Alert ID
     * @throws InvalidArgumentException If configuration is invalid
     */
    public function createAlert(array $config): int
    {
        $this->validateAlertConfig($config);

        $alertId = $this->nextAlertId++;

        // Parse conditions
        $conditions = [];
        
        if (isset($config['conditions']) && is_array($config['conditions'])) {
            // Multiple conditions
            foreach ($config['conditions'] as $conditionData) {
                $conditions[] = new AlertCondition(
                    $conditionData['type'],
                    $conditionData['value']
                );
            }
        } elseif (isset($config['condition_type']) && isset($config['threshold'])) {
            // Single condition
            $conditions[] = new AlertCondition(
                $config['condition_type'],
                $config['threshold']
            );
        }

        $this->alerts[$alertId] = [
            'id' => $alertId,
            'user_id' => $config['user_id'],
            'name' => $config['name'],
            'symbol' => $config['symbol'] ?? null,
            'conditions' => $conditions,
            'email' => $config['email'] ?? null,
            'throttle_minutes' => $config['throttle_minutes'] ?? 0,
            'active' => true,
            'created_at' => time()
        ];

        return $alertId;
    }

    /**
     * Evaluate multiple conditions
     *
     * @param array<int, AlertCondition> $conditions Conditions to evaluate
     * @param array<string, mixed> $data Market data
     * @return bool True if all conditions are met
     */
    public function evaluateConditions(array $conditions, array $data): bool
    {
        foreach ($conditions as $condition) {
            if (!$condition->evaluate($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check all alerts against current market data
     *
     * @param array<string, array<string, mixed>> $marketData Market data by symbol
     * @return void
     */
    public function checkAlerts(array $marketData): void
    {
        foreach ($this->alerts as $alertId => $alert) {
            if (!$alert['active']) {
                continue;
            }

            // Check throttling
            if ($this->isThrottled($alertId, $alert['throttle_minutes'])) {
                continue;
            }

            // Get relevant market data
            $symbol = $alert['symbol'];
            if (!isset($marketData[$symbol])) {
                continue;
            }

            $data = $marketData[$symbol];

            // Evaluate conditions
            if ($this->evaluateConditions($alert['conditions'], $data)) {
                $this->triggerAlert($alertId, $data);
            }
        }
    }

    /**
     * Get alert history
     *
     * @param int $alertId Alert ID
     * @return array<int, array<string, mixed>> History entries
     */
    public function getAlertHistory(int $alertId): array
    {
        return $this->alertHistory[$alertId] ?? [];
    }

    /**
     * Get active alerts for a user
     *
     * @param int $userId User ID
     * @return array<int, array<string, mixed>> Active alerts
     */
    public function getActiveAlerts(int $userId): array
    {
        $userAlerts = [];

        foreach ($this->alerts as $alert) {
            if ($alert['user_id'] === $userId && $alert['active']) {
                $userAlerts[] = $this->sanitizeAlertForOutput($alert);
            }
        }

        return $userAlerts;
    }

    /**
     * Delete an alert
     *
     * @param int $alertId Alert ID
     * @return void
     */
    public function deleteAlert(int $alertId): void
    {
        unset($this->alerts[$alertId]);
        unset($this->alertHistory[$alertId]);
        unset($this->lastTriggered[$alertId]);
    }

    /**
     * Update an alert
     *
     * @param int $alertId Alert ID
     * @param array<string, mixed> $updates Update data
     * @return void
     */
    public function updateAlert(int $alertId, array $updates): void
    {
        if (!isset($this->alerts[$alertId])) {
            throw new InvalidArgumentException("Alert not found: {$alertId}");
        }

        // Update simple fields
        foreach (['name', 'threshold', 'email', 'throttle_minutes'] as $field) {
            if (isset($updates[$field])) {
                if ($field === 'threshold' && isset($this->alerts[$alertId]['conditions'][0])) {
                    // Update condition value
                    $oldCondition = $this->alerts[$alertId]['conditions'][0];
                    $this->alerts[$alertId]['conditions'][0] = new AlertCondition(
                        $oldCondition->getType(),
                        $updates[$field]
                    );
                } else {
                    $this->alerts[$alertId][$field] = $updates[$field];
                }
            }
        }
    }

    /**
     * Get a specific alert
     *
     * @param int $alertId Alert ID
     * @return array<string, mixed> Alert data
     */
    public function getAlert(int $alertId): array
    {
        if (!isset($this->alerts[$alertId])) {
            throw new InvalidArgumentException("Alert not found: {$alertId}");
        }

        $alert = $this->sanitizeAlertForOutput($this->alerts[$alertId]);
        
        // Extract threshold from first condition for compatibility
        if (!empty($alert['conditions'])) {
            $alert['threshold'] = $alert['conditions'][0]['value'];
        }

        return $alert;
    }

    /**
     * Get alert statistics for a user
     *
     * @param int $userId User ID
     * @return array<string, mixed> Statistics
     */
    public function getStatistics(int $userId): array
    {
        $userAlerts = $this->getActiveAlerts($userId);
        $triggeredCount = 0;
        $lastTriggered = null;

        foreach ($this->alertHistory as $alertId => $history) {
            if (isset($this->alerts[$alertId]) && $this->alerts[$alertId]['user_id'] === $userId) {
                $triggeredCount += count($history);
                
                if (!empty($history)) {
                    $lastEntry = end($history);
                    if ($lastTriggered === null || $lastEntry['triggered_at'] > $lastTriggered) {
                        $lastTriggered = $lastEntry['triggered_at'];
                    }
                }
            }
        }

        return [
            'total_alerts' => count($userAlerts),
            'triggered_count' => $triggeredCount,
            'last_triggered' => $lastTriggered
        ];
    }

    /**
     * Trigger an alert
     *
     * @param int $alertId Alert ID
     * @param array<string, mixed> $data Market data that triggered alert
     * @return void
     */
    private function triggerAlert(int $alertId, array $data): void
    {
        $alert = $this->alerts[$alertId];

        // Record in history
        if (!isset($this->alertHistory[$alertId])) {
            $this->alertHistory[$alertId] = [];
        }

        $this->alertHistory[$alertId][] = [
            'triggered_at' => time(),
            'data' => $data
        ];

        // Update last triggered time
        $this->lastTriggered[$alertId] = time();

        // Send notification
        if ($alert['email']) {
            $this->sendAlertNotification($alert, $data);
        }
    }

    /**
     * Check if alert is throttled
     *
     * @param int $alertId Alert ID
     * @param int $throttleMinutes Throttle period in minutes
     * @return bool True if throttled
     */
    private function isThrottled(int $alertId, int $throttleMinutes): bool
    {
        if ($throttleMinutes <= 0) {
            return false;
        }

        if (!isset($this->lastTriggered[$alertId])) {
            return false;
        }

        $elapsedMinutes = (time() - $this->lastTriggered[$alertId]) / 60;
        
        return $elapsedMinutes < $throttleMinutes;
    }

    /**
     * Send alert notification email
     *
     * @param array<string, mixed> $alert Alert configuration
     * @param array<string, mixed> $data Market data
     * @return void
     */
    private function sendAlertNotification(array $alert, array $data): void
    {
        $this->emailService->sendAlert(
            $alert['email'],
            [
                'type' => 'custom_alert',
                'severity' => 'info',
                'title' => $alert['name'],
                'message' => $this->formatAlertMessage($alert, $data),
                'data' => $data
            ]
        );
    }

    /**
     * Format alert message
     *
     * @param array<string, mixed> $alert Alert configuration
     * @param array<string, mixed> $data Market data
     * @return string Formatted message
     */
    private function formatAlertMessage(array $alert, array $data): string
    {
        $symbol = $alert['symbol'] ?? 'Unknown';
        $price = $data['price'] ?? 'N/A';
        $volume = $data['volume'] ?? 'N/A';

        return "Alert '{$alert['name']}' triggered for {$symbol}. " .
               "Current Price: {$price}, Volume: {$volume}";
    }

    /**
     * Validate alert configuration
     *
     * @param array<string, mixed> $config Configuration
     * @return void
     * @throws InvalidArgumentException If invalid
     */
    private function validateAlertConfig(array $config): void
    {
        if (!isset($config['user_id'])) {
            throw new InvalidArgumentException("user_id is required");
        }

        if (!isset($config['name']) || empty($config['name'])) {
            throw new InvalidArgumentException("name is required");
        }

        // Must have either single condition or multiple conditions
        $hasSingleCondition = isset($config['condition_type']) && isset($config['threshold']);
        $hasMultipleConditions = isset($config['conditions']) && is_array($config['conditions']);

        if (!$hasSingleCondition && !$hasMultipleConditions) {
            throw new InvalidArgumentException("Alert must have at least one condition");
        }
    }

    /**
     * Sanitize alert for output
     *
     * @param array<string, mixed> $alert Alert data
     * @return array<string, mixed> Sanitized data
     */
    private function sanitizeAlertForOutput(array $alert): array
    {
        // Convert AlertCondition objects to arrays
        $conditions = [];
        foreach ($alert['conditions'] as $condition) {
            $conditions[] = [
                'type' => $condition->getType(),
                'value' => $condition->getValue()
            ];
        }

        return [
            'id' => $alert['id'],
            'user_id' => $alert['user_id'],
            'name' => $alert['name'],
            'symbol' => $alert['symbol'],
            'conditions' => $conditions,
            'email' => $alert['email'],
            'throttle_minutes' => $alert['throttle_minutes'],
            'active' => $alert['active'],
            'created_at' => $alert['created_at']
        ];
    }
}
