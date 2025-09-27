<?php
/**
 * Advisor Management Helper - Reusable components for advisor/client selection and relationship management
 * Following SRP: This class handles only advisor-related data operations and UI components
 */

class AdvisorManagementHelper {
    private $auth;
    private $rbac;
    
    public function __construct($userAuthDAO, $rbacService = null) {
        $this->auth = $userAuthDAO;
        $this->rbac = $rbacService;
    }
    
    /**
     * Get all users suitable for advisor selection (with advisor role or admin)
     */
    public function getAdvisorCandidates() {
        try {
            $allUsers = $this->auth->getAllUsers(1000);
            
            // Filter for users who can be advisors (have advisor role or are admin)
            $advisors = [];
            foreach ($allUsers as $user) {
                // Check if user has advisor role or is admin
                if ($user['is_admin']) {
                    $advisors[] = $user;
                } elseif ($this->rbac) {
                    $userRoles = $this->rbac->getUserRoles($user['id']);
                    if (in_array('advisor', $userRoles)) {
                        $advisors[] = $user;
                    }
                }
            }
            
            return $advisors;
        } catch (Exception $e) {
            error_log("Error loading advisor candidates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all users suitable for client selection (regular users)
     */
    public function getClientCandidates() {
        try {
            $allUsers = $this->auth->getAllUsers(1000);
            
            // All users can be clients, but we'll exclude system accounts if any
            return array_filter($allUsers, function($user) {
                return !empty($user['username']) && !empty($user['email']);
            });
        } catch (Exception $e) {
            error_log("Error loading client candidates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Render advisor selection dropdown (reusable component)
     */
    public function renderAdvisorSelector($name, $id, $selectedValue = '', $required = true, $includeEmpty = true) {
        $advisors = $this->getAdvisorCandidates();
        $requiredAttr = $required ? 'required' : '';
        
        $html = "<select name=\"{$name}\" id=\"{$id}\" {$requiredAttr}>\n";
        
        if ($includeEmpty) {
            $html .= "    <option value=\"\">Select Advisor...</option>\n";
        }
        
        foreach ($advisors as $advisor) {
            $selected = ($advisor['id'] == $selectedValue) ? 'selected' : '';
            $displayName = htmlspecialchars($advisor['username']) . ' (' . htmlspecialchars($advisor['email']) . ')';
            $html .= "    <option value=\"{$advisor['id']}\" {$selected}>{$displayName}</option>\n";
        }
        
        $html .= "</select>\n";
        return $html;
    }
    
    /**
     * Render client selection dropdown (reusable component)
     */
    public function renderClientSelector($name, $id, $selectedValue = '', $required = true, $includeEmpty = true) {
        $clients = $this->getClientCandidates();
        $requiredAttr = $required ? 'required' : '';
        
        $html = "<select name=\"{$name}\" id=\"{$id}\" {$requiredAttr}>\n";
        
        if ($includeEmpty) {
            $html .= "    <option value=\"\">Select Client...</option>\n";
        }
        
        foreach ($clients as $client) {
            $selected = ($client['id'] == $selectedValue) ? 'selected' : '';
            $displayName = htmlspecialchars($client['username']) . ' (' . htmlspecialchars($client['email']) . ')';
            $html .= "    <option value=\"{$client['id']}\" {$selected}>{$displayName}</option>\n";
        }
        
        $html .= "</select>\n";
        return $html;
    }
    
    /**
     * Get advisor relationships for a specific user (for profile page)
     */
    public function getUserAdvisorRelationships($userId) {
        try {
            // Get PDO connection from UserAuthDAO
            $reflection = new ReflectionClass($this->auth);
            $pdoProperty = $reflection->getProperty('pdo');
            $pdoProperty->setAccessible(true);
            $pdo = $pdoProperty->getValue($this->auth);
            
            $stmt = $pdo->prepare("
                SELECT 
                    ua.id,
                    ua.advisor_id,
                    ua.permission_level,
                    ua.status,
                    ua.accepted_at,
                    ua.invited_at,
                    advisor.username as advisor_name,
                    advisor.email as advisor_email
                FROM user_advisors ua
                JOIN users advisor ON ua.advisor_id = advisor.id
                WHERE ua.user_id = ? AND ua.status IN ('active', 'pending')
                ORDER BY ua.accepted_at DESC, ua.invited_at DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error loading user advisor relationships: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Render relationship table for user profile (reusable component)
     */
    public function renderUserRelationshipTable($userId) {
        $relationships = $this->getUserAdvisorRelationships($userId);
        
        if (empty($relationships)) {
            return "<p><em>No advisor relationships found.</em></p>";
        }
        
        $html = "<table class='relationships-table'>";
        $html .= "<thead><tr><th>Advisor</th><th>Access Level</th><th>Status</th><th>Date</th></tr></thead>";
        $html .= "<tbody>";
        
        foreach ($relationships as $rel) {
            $statusClass = $rel['status'] === 'active' ? 'success' : 'warning';
            $accessLevel = $rel['permission_level'] === 'read' ? 'Read Only' : 'Read/Write';
            $date = $rel['accepted_at'] ? date('M j, Y', strtotime($rel['accepted_at'])) : 'Pending';
            
            $html .= "<tr>";
            $html .= "<td><strong>" . htmlspecialchars($rel['advisor_name']) . "</strong><br>";
            $html .= "<small>" . htmlspecialchars($rel['advisor_email']) . "</small></td>";
            $html .= "<td>{$accessLevel}</td>";
            $html .= "<td><span class='status-{$statusClass}'>" . ucfirst($rel['status']) . "</span></td>";
            $html .= "<td>{$date}</td>";
            $html .= "</tr>";
        }
        
        $html .= "</tbody></table>";
        return $html;
    }
    
    /**
     * Debug information about loaded data
     */
    public function getDebugInfo() {
        $advisors = $this->getAdvisorCandidates();
        $clients = $this->getClientCandidates();
        
        return [
            'advisor_count' => count($advisors),
            'client_count' => count($clients),
            'total_users' => count($this->auth->getAllUsers(1000) ?? [])
        ];
    }
}
?>