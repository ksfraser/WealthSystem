<?php

namespace Ksfraser\StockInfo;

/**
 * Technical Analysis Jobs Model
 * 
 * Manages technical analysis calculation jobs and their status
 */
class TechnicalAnalysisJobs extends BaseModel
{
    protected $table = 'ta_analysis_jobs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'idstockinfo', 'symbol', 'analysis_type', 'status', 'start_date',
        'end_date', 'progress', 'error_message'
    ];

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    /**
     * Create a new analysis job
     */
    public function createJob(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (idstockinfo, symbol, analysis_type, status, start_date, end_date, progress)
                VALUES (:idstockinfo, :symbol, :analysis_type, :status, :start_date, :end_date, :progress)";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idstockinfo' => $data['idstockinfo'] ?? null,
            ':symbol' => $data['symbol'] ?? null,
            ':analysis_type' => $data['analysis_type'],
            ':status' => $data['status'] ?? 'PENDING',
            ':start_date' => $data['start_date'] ?? null,
            ':end_date' => $data['end_date'] ?? null,
            ':progress' => $data['progress'] ?? 0
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update job progress
     */
    public function updateProgress(int $jobId, int $progress, string $status = null): bool
    {
        $sql = "UPDATE {$this->table} SET progress = :progress, updated_at = CURRENT_TIMESTAMP";
        
        if ($status) {
            $sql .= ", status = :status";
            if ($status === 'COMPLETED') {
                $sql .= ", completed_at = CURRENT_TIMESTAMP";
            }
        }
        
        $sql .= " WHERE id = :jobId";
        
        $stmt = $this->pdo->prepare($sql);
        $params = [
            ':progress' => $progress,
            ':jobId' => $jobId
        ];
        
        if ($status) {
            $params[':status'] = $status;
        }
        
        return $stmt->execute($params);
    }

    /**
     * Set job error
     */
    public function setError(int $jobId, string $errorMessage): bool
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'FAILED', error_message = :error_message, updated_at = CURRENT_TIMESTAMP
                WHERE id = :jobId";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':error_message' => $errorMessage,
            ':jobId' => $jobId
        ]);
    }

    /**
     * Get pending jobs
     */
    public function getPendingJobs(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'PENDING' 
                ORDER BY created_at ASC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get job status
     */
    public function getJobStatus(int $jobId): ?array
    {
        return $this->find($jobId);
    }

    /**
     * Get recent jobs
     */
    public function getRecentJobs(int $limit = 20): array
    {
        $sql = "SELECT j.*, s.symbol as stock_symbol, s.name as stock_name
                FROM {$this->table} j
                LEFT JOIN stockinfo s ON j.idstockinfo = s.idstockinfo
                ORDER BY j.created_at DESC
                LIMIT :limit";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
