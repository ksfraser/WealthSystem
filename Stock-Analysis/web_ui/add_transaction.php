<?php
/**
 * Add Transaction Page
 *
 * @startuml add_transaction_sequence.puml
 * actor User
 * boundary add_transaction.php
 * control AddTransactionController
 * entity BankAccountsDAO
 * entity StockSymbolDAO
 * entity InvestGLDAO
 * database Database
 *
 * User -> add_transaction.php : GET request
 * activate add_transaction.php
 * add_transaction.php -> AddTransactionController : create
 * activate AddTransactionController
 * AddTransactionController -> BankAccountsDAO : getUserAccessibleBankAccounts()
 * activate BankAccountsDAO
 * BankAccountsDAO -> Database : SELECT bank_accounts
 * Database --> BankAccountsDAO : bank accounts
 * BankAccountsDAO --> AddTransactionController : bank accounts
 * deactivate BankAccountsDAO
 * AddTransactionController -> StockSymbolDAO : getAllSymbols()
 * activate StockSymbolDAO
 * StockSymbolDAO -> Database : SELECT stock_symbols
 * Database --> StockSymbolDAO : symbols
 * StockSymbolDAO --> AddTransactionController : symbols
 * deactivate StockSymbolDAO
 * AddTransactionController -> add_transaction.php : render view with data
 * add_transaction.php --> User : display form
 * deactivate AddTransactionController
 * deactivate add_transaction.php
 *
 * User -> add_transaction.php : POST request (submit form)
 * activate add_transaction.php
 * add_transaction.php -> AddTransactionController : handlePost()
 * activate AddTransactionController
 * AddTransactionController -> AddTransactionController : validate(data)
 * alt validation fails
 *     AddTransactionController -> add_transaction.php : render view with error
 * else validation succeeds
 *     AddTransactionController -> InvestGLDAO : addTransaction()
 *     activate InvestGLDAO
 *     InvestGLDAO -> Database : INSERT gl_trans_invest
 *     Database --> InvestGLDAO : success
 *     InvestGLDAO --> AddTransactionController : success
 *     deactivate InvestGLDAO
 *     AddTransactionController -> add_transaction.php : redirect to view_imported_transactions.php
 * end
 * deactivate AddTransactionController
 * deactivate add_transaction.php
 * @enduml
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/NavigationService.php';
require_once __DIR__ . '/BankAccountsDAO.php';
require_once __DIR__ . '/StockSymbolDAO.php';
require_once __DIR__ . '/InvestGLDAO.php';

class AddTransactionController {
    private $navService;
    private $bankAccountsDAO;
    private $stockSymbolDAO;
    private $investGLDAO;
    private $currentUser;
    public $errors = [];
    public $postData = [];

    public function __construct() {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        $this->navService = new NavigationService();
        
        // Establish a single database connection to be shared
        $this->bankAccountsDAO = new BankAccountsDAO();
        $pdo = $this->bankAccountsDAO->getPdo();

        if (!$pdo) {
            // Log the error and stop execution if the connection fails
            error_log("Critical Error: Database connection could not be established in AddTransactionController.");
            // Display a user-friendly error message
            die("A critical database error occurred. Please try again later or contact support.");
        }

        // Share the connection with other DAOs
        $this->stockSymbolDAO = new StockSymbolDAO($pdo);
        $this->investGLDAO = new InvestGLDAO($pdo);
        
        $this->currentUser = $this->navService->getCurrentUser();
    }

    public function handleRequest() {
        if (!$this->currentUser) {
            header('Location: login.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        } else {
            $this->handleGet();
        }
    }

    private function handleGet() {
        $this->renderPage();
    }

    private function handlePost() {
        $this->postData = $_POST;
        if ($this->validate()) {
            try {
                $this->investGLDAO->addTransaction(
                    $this->currentUser['id'],
                    '1100', // Placeholder GL Account
                    $this->postData['stock_symbol'],
                    $this->postData['tran_date'],
                    $this->postData['tran_type'],
                    $this->postData['amount'],
                    $this->postData['quantity'],
                    $this->postData['price'],
                    0, // Fees
                    0, // Cost Basis
                    $this->postData['description'],
                    $this->postData['bank_account_id']
                );
                header('Location: view_imported_transactions.php?bank_account_id=' . $this->postData['bank_account_id']);
                exit;
            } catch (Exception $e) {
                $this->errors[] = "Failed to add transaction: " . $e->getMessage();
            }
        }
        $this->renderPage();
    }

    private function validate() {
        // Basic validation
        if (empty($this->postData['bank_account_id'])) $this->errors[] = "Bank Account is required.";
        if (empty($this->postData['tran_date'])) $this->errors[] = "Transaction Date is required.";
        if (empty($this->postData['tran_type'])) $this->errors[] = "Activity type is required.";
        if (empty($this->postData['stock_symbol'])) $this->errors[] = "Symbol is required.";
        if (!is_numeric($this->postData['quantity'])) $this->errors[] = "Quantity must be a number.";
        if (!is_numeric($this->postData['price'])) $this->errors[] = "Price must be a number.";
        if (!is_numeric($this->postData['amount'])) $this->errors[] = "Amount must be a number.";

        // Auto-calculate amount if not provided
        if (is_numeric($this->postData['quantity']) && is_numeric($this->postData['price']) && empty($this->postData['amount'])) {
            $this->postData['amount'] = $this->postData['quantity'] * $this->postData['price'];
        }

        return empty($this->errors);
    }

    public function getBankAccounts() {
        return $this->bankAccountsDAO->getUserAccessibleBankAccounts($this->currentUser['id']);
    }

    public function getStockSymbols() {
        return $this->stockSymbolDAO->getAllSymbols();
    }

    public function getTransactionTypes() {
        return ['BUY', 'SELL', 'DIVIDEND', 'SPLIT', 'TRANSFER', 'FEE', 'OTHER'];
    }

    public function renderPage() {
        $pageTitle = "Add New Transaction";
        $navHeader = $this->navService->renderNavigationHeader($pageTitle);
        $navCSS = $this->navService->getDashboardCSS();
        $navScript = $this->navService->getNavigationScript();

        $bankAccounts = $this->getBankAccounts();
        $stockSymbols = $this->getStockSymbols();
        $transactionTypes = $this->getTransactionTypes();
        $selectedBankAccountId = $_GET['bank_account_id'] ?? $this->postData['bank_account_id'] ?? null;

        include __DIR__ . '/views/add_transaction_view.php';
    }
}

$controller = new AddTransactionController();
$controller->handleRequest();
