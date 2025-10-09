<?php

class BankImportView {
    private $navService;

    public function __construct(NavigationService $navService) {
        $this->navService = $navService;
    }

    public function renderUploadForm(): void {
        $navHeader = $this->navService->renderNavigationHeader('Import Bank CSV');
        $navScript = $this->navService->getNavigationScript();

        include __DIR__ . '/../views/bank_import_upload.php';
    }

    public function renderStagingPage(string $prompt, string $stagingFile, string $type, array $userBankAccounts): void {
        $navHeader = $this->navService->renderNavigationHeader('Import Bank CSV - Assign Account');
        $navScript = $this->navService->getNavigationScript();

        include __DIR__ . '/../views/bank_import_staging.php';
    }

    public function renderSuccessPage(array $rows, ?string $accountName = null, ?string $viewTransactionsUrl = null, ?string $downloadLogUrl = null): void {
        $navHeader = $this->navService->renderNavigationHeader('Import Bank CSV - Complete');
        $navScript = $this->navService->getNavigationScript();

        include __DIR__ . '/../views/bank_import_success.php';
    }

    public function renderErrorPage(string $errorTitle, string $errorMessage, ?string $errorDetails = null): void {
        $navHeader = $this->navService->renderNavigationHeader('Import Bank CSV - Error');
        $navScript = $this->navService->getNavigationScript();

        include __DIR__ . '/../views/bank_import_error.php';
    }

    public function renderStagingErrorPage(string $errorDetails): void {
        $navHeader = $this->navService->renderNavigationHeader('Import Bank CSV - Error');
        $navScript = $this->navService->getNavigationScript();

        $errorTitle = 'Error During Staging';
        $errorMessage = 'An error occurred while processing your CSV file:';

        include __DIR__ . '/../views/bank_import_error.php';
    }

    public function renderAccountNotFoundError(): void {
        $navHeader = $this->navService->renderNavigationHeader('Import Bank CSV - Error');
        $navScript = $this->navService->getNavigationScript();

        $errorTitle = 'Account Not Found';
        $errorMessage = 'The selected bank account was not found. It may have been deleted or you may not have access to it.';

        include __DIR__ . '/../views/bank_import_error.php';
    }
}