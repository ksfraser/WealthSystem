<?php

namespace App;

/**
 * Core interfaces for improved architecture and dependency injection
 */

interface DatabaseConnectionInterface
{
    public function getConnection();
}

interface ConfigurationInterface
{
    public function get($key, $default = null);
    public function has($key);
    public function load($configFile = null);
}

interface LoggerInterface
{
    public function error($message, array $context = []);
    public function warning($message, array $context = []);
    public function info($message, array $context = []);
    public function debug($message, array $context = []);
}

interface ValidatorInterface
{
    public function validate($data);
}

interface CsvParserInterface
{
    public function parse($filePath);
    public function write($filePath, array $data);
}

interface DataRepositoryInterface
{
    public function save(array $data);
    public function findAll();
    public function findBy(array $criteria);
    public function delete(array $criteria);
}

interface SchemaManagerInterface
{
    public function migrate();
    public function rollback($steps = 1);
    public function getAppliedMigrations();
}

/**
 * Value objects for better type safety
 */
class ValidationResult
{
    private $isValid;
    private $errors;
    private $warnings;

    public function __construct($isValid, array $errors = [], array $warnings = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    public function isValid()
    {
        return $this->isValid;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }

    public function hasWarnings()
    {
        return !empty($this->warnings);
    }
}

class ImportResult
{
    private $success;
    private $processedCount;
    private $errors;
    private $warnings;

    public function __construct($success, $processedCount = 0, array $errors = [], array $warnings = [])
    {
        $this->success = $success;
        $this->processedCount = $processedCount;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function getProcessedCount()
    {
        return $this->processedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }
}

class UploadResult
{
    private $success;
    private $filePath;
    private $error;

    public function __construct($success, $filePath = null, $error = null)
    {
        $this->success = $success;
        $this->filePath = $filePath;
        $this->error = $error;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getError()
    {
        return $this->error;
    }
}
