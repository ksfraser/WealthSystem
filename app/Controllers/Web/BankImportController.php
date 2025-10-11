<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthenticationService;
use App\Services\BankImportService;
use App\Services\NavigationService;
use App\Repositories\Interfaces\BankAccountRepositoryInterface;

/**
 * Bank Import Controller
 * 
 * Handles CSV file uploads and bank transaction imports.
 * Follows Single Responsibility Principle (SRP) - bank import operations only.
 */
class BankImportController extends BaseController 
{
    private AuthenticationService $authService;
    private BankImportService $importService;
    private NavigationService $navService;
    private BankAccountRepositoryInterface $bankAccountRepo;
    
    public function __construct(
        AuthenticationService $authService,
        BankImportService $importService,
        NavigationService $navService,
        BankAccountRepositoryInterface $bankAccountRepo
    ) {
        $this->authService = $authService;
        $this->importService = $importService;
        $this->navService = $navService;
        $this->bankAccountRepo = $bankAccountRepo;
    }
    
    /**
     * Main processing method
     */
    protected function process(Request $request): Response 
    {
        if ($request->isMethod('POST')) {
            return $this->handleUpload($request);
        }
        
        return $this->showUploadForm($request);
    }
    
    /**
     * Show upload form
     */
    public function showUploadForm(Request $request): Response 
    {
        $user = $this->authService->getCurrentUser();
        
        // Get user's accessible bank accounts
        $bankAccounts = $this->bankAccountRepo->findBy(['user_id' => $user['id']]);
        
        // Get supported file formats
        $supportedFormats = $this->importService->getSupportedFormats();
        
        $viewData = [
            'user' => $user,
            'bankAccounts' => $bankAccounts,
            'supportedFormats' => $supportedFormats,
            'navigation' => $this->navService->getNavigationMenu(),
            'breadcrumbs' => $this->navService->getBreadcrumbs('import'),
            'quickActions' => $this->navService->getQuickActions()
        ];
        
        return $this->view('BankImport/index', $viewData);
    }
    
    /**
     * Handle file upload and processing
     */
    public function handleUpload(Request $request): Response 
    {
        try {
            // Validate request data
            $data = $this->validate($request, [
                'bank_account_id' => 'required',
                'csv_file' => 'required'
            ]);
            
            $user = $this->authService->getCurrentUser();
            
            // Process the import
            $result = $this->importService->processImport($request->file('csv_file'), $user['id']);
            
            // Return success response
            $viewData = [
                'result' => $result,
                'user' => $user,
                'navigation' => $this->navService->getNavigationMenu(),
                'breadcrumbs' => $this->navService->getBreadcrumbs('import'),
                'quickActions' => $this->navService->getQuickActions()
            ];
            
            return $this->view('bank-import.success', $viewData);
            
        } catch (\Exception $e) {
            // Return error page
            $viewData = [
                'error' => $e->getMessage(),
                'navigation' => $this->navService->getNavigationMenu(),
                'breadcrumbs' => $this->navService->getBreadcrumbs('import'),
                'quickActions' => $this->navService->getQuickActions()
            ];
            
            return $this->view('bank-import.error', $viewData)->setStatusCode(500);
        }
    }
    
    /**
     * API endpoint for format detection
     */
    public function detectFormat(Request $request): Response 
    {
        try {
            if (!$request->file('csv_file')) {
                return $this->json(['error' => 'No file uploaded'], 400);
            }
            
            $detectedFormat = $this->importService->detectFormat($request->file('csv_file'));
            
            return $this->json([
                'format' => $detectedFormat,
                'confidence' => $detectedFormat ? 'high' : 'none'
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Pre-processing: Ensure user is authenticated
     */
    protected function before(Request $request): void 
    {
        if (!$this->authService->isAuthenticated()) {
            // Redirect to login will be handled in main process
            return;
        }
        
        // Set file upload security headers
        header('X-Content-Type-Options: nosniff');
    }
}