<?php

/**
 * Core interfaces for improved architecture and dependency injection
 */

interface DatabaseConnectionInterface
{
    public function getConnection(): PDO;
}

interface ConfigurationInterface
{
    public function get(string $key, $default = null);
    public function has(string $key): bool;
    public function load(?string $configFile = null): array;
}

interface LoggerInterface
{
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
}

interface ValidatorInterface
{
    public function validate($data): ValidationResult;
}

interface CsvParserInterface
{
    public function parse(string $filePath): array;
    public function write(string $filePath, array $data): bool;
}

interface DataRepositoryInterface
{
    public function save(array $data): bool;
    public function findAll(): array;
    public function findBy(array $criteria): array;
    public function delete(array $criteria): bool;
}

interface SchemaManagerInterface
{
    public function migrate(): bool;
    public function rollback(int $steps = 1): bool;
    public function getAppliedMigrations(): array;
}

/**
 * Value objects for better type safety
 */
class ValidationResult
{
    private bool $isValid;
    private array $errors;
    private array $warnings;

    public function __construct(bool $isValid, array $errors = [], array $warnings = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}

class ImportResult
{
    public function __construct(
        private bool $success,
        private int $processedCount = 0,
        private array $errors = [],
        private array $warnings = []
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}

class UploadResult
{
    public function __construct(
        private bool $success,
        private ?string $filePath = null,
        private ?string $error = null
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
