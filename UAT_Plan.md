# User Acceptance Testing (UAT) Plan
**ChatGPT Micro Cap Trading Experiment - Post Cleanup Validation**
*Generated: September 26, 2025*

## 🎯 UAT Objectives

Validate that all core trading system functionality works correctly after the dead code cleanup, ensuring:
- ✅ User authentication and session management
- ✅ Trading functionality and data persistence  
- ✅ Portfolio management capabilities
- ✅ System navigation and user interface
- ✅ Database connectivity and data integrity
- ✅ Admin functionality

---

## 🧪 Test Scenarios

### **Scenario 1: User Authentication Flow**
**Objective:** Verify login/logout functionality works correctly

**Test Steps:**
1. Navigate to `http://localhost/web_ui/index.php`
2. Should be redirected to login page if not authenticated
3. Attempt login with invalid credentials
4. Attempt login with valid credentials (if user exists)
5. Verify successful authentication redirects to dashboard
6. Test logout functionality
7. Verify logout redirects to login page

**Expected Results:**
- ✅ Unauthenticated users redirected to login
- ✅ Invalid credentials rejected with error message
- ✅ Valid credentials allow access to system
- ✅ User session maintained across page navigation
- ✅ Logout properly terminates session

---

### **Scenario 2: Dashboard and Navigation**
**Objective:** Ensure main dashboard loads and navigation works

**Test Steps:**
1. Log into the system
2. Verify dashboard loads with user information
3. Test navigation to each main section:
   - Portfolios (`portfolios.php`)
   - Trades (`trades.php`) 
   - Analytics (`analytics.php`)
   - Database Manager (`database.php`)
4. Verify navigation menu displays correctly
5. Check that removed duplicate files don't cause broken links

**Expected Results:**
- ✅ Dashboard displays user information and system status
- ✅ All navigation links work correctly
- ✅ No broken links to removed files (trades_old.php, portfolios_old.php)
- ✅ Page styling and layout intact
- ✅ Quick actions and menu items functional

---

### **Scenario 3: Trading Operations**
**Objective:** Verify trade management functionality

**Test Steps:**
1. Navigate to Trades page (`trades.php`)
2. Test trade filtering by date range
3. Attempt to add a new trade (if functionality exists)
4. Verify trade data displays correctly
5. Test CSV import/export functionality (if available)
6. Check Python script integration buttons work

**Expected Results:**
- ✅ Trades page loads without errors
- ✅ Trade filtering works correctly
- ✅ No references to removed `trades_clean.php` or `trades_old.php`
- ✅ Data persistence functions correctly
- ✅ Python automation scripts accessible

---

### **Scenario 4: Portfolio Management**
**Objective:** Ensure portfolio functionality intact

**Test Steps:**
1. Navigate to Portfolios page (`portfolios.php`)
2. Test portfolio creation (if available)
3. Verify portfolio data displays correctly
4. Test portfolio filtering and search
5. Check portfolio analysis features
6. Verify no broken references to `portfolios_old.php`

**Expected Results:**
- ✅ Portfolio page loads correctly
- ✅ Portfolio data displays properly
- ✅ No broken links or missing functionality
- ✅ Database operations work correctly

---

### **Scenario 5: Database Connectivity**
**Objective:** Verify database operations function correctly

**Test Steps:**
1. Navigate to Database Manager (`database.php`)
2. Test database connection status
3. Verify data can be read from database
4. Test any database administration features
5. Check error handling for database issues

**Expected Results:**
- ✅ Database connections established successfully
- ✅ Data retrieval functions correctly
- ✅ CommonDAO and EnhancedCommonDAO work properly
- ✅ Error handling graceful and informative

---

### **Scenario 6: Admin Functionality**
**Objective:** Ensure admin features work correctly after cleanup

**Test Steps:**
1. Access admin sections (if admin user available)
2. Test admin user management (`admin_users.php`)
3. Verify admin account types (`admin_account_types.php`)
4. Check admin bank accounts (`admin_bank_accounts.php`)
5. Test enhanced admin brokerage features
6. Ensure no references to removed admin backup files

**Expected Results:**
- ✅ Admin pages load correctly
- ✅ EnhancedCommonDAO functionality works in admin pages
- ✅ No broken references to removed `admin_users_old.php` etc.
- ✅ Admin operations complete successfully

---

### **Scenario 7: System Status and Diagnostics**
**Objective:** Verify system monitoring functionality

**Test Steps:**
1. Navigate to System Status page (`system_status.php`)
2. Check system diagnostic information
3. Verify server status indicators
4. Test system health checks
5. Ensure all components report correctly

**Expected Results:**
- ✅ System status displays correctly
- ✅ All components report healthy status
- ✅ Diagnostic information accurate
- ✅ No errors from missing files

---

## 🚨 Critical Validation Points

### **Files That Must Work:**
- ✅ `web_ui/index.php` - Main entry point
- ✅ `web_ui/login.php` - Authentication
- ✅ `web_ui/trades.php` - Trading functionality  
- ✅ `web_ui/portfolios.php` - Portfolio management
- ✅ `web_ui/UserAuthDAO.php` - Authentication backend
- ✅ `web_ui/EnhancedCommonDAO.php` - Database operations

### **Files That Should Not Exist:**
- ❌ `web_ui/ExampleUsage.php` - Removed (orphaned)
- ❌ `web_ui/ImportService.php` - Removed (orphaned)
- ❌ `web_ui/ImprovedDAO.php` - Removed (orphaned)
- ❌ `web_ui/trades_clean.php` - Removed (duplicate)
- ❌ `web_ui/trades_old.php` - Removed (duplicate)
- ❌ `web_ui/admin_users_old.php` - Removed (backup)
- ❌ `web_ui/portfolios_old.php` - Removed (backup)

### **Navigation References:**
- ✅ All navigation menus should point to `trades.php` (not old versions)
- ✅ Admin sections should use `EnhancedCommonDAO` correctly
- ✅ No broken internal links

---

## 📝 UAT Execution Checklist

### **Pre-Testing Setup:**
- [ ] Web server running (Apache/Nginx)
- [ ] PHP environment configured  
- [ ] Database connection available
- [ ] Test user accounts created (if needed)
- [ ] Browser developer tools available for debugging

### **During Testing:**
- [ ] Document any errors or unexpected behavior
- [ ] Check browser console for JavaScript errors
- [ ] Monitor server logs for PHP errors
- [ ] Test in multiple browsers if possible
- [ ] Verify responsive design on different screen sizes

### **Post-Testing:**
- [ ] Verify all scenarios completed
- [ ] Document any issues found
- [ ] Confirm system ready for production use
- [ ] Update documentation as needed

---

## 🎯 Success Criteria

**UAT is considered successful if:**
1. **100% of critical scenarios pass** without errors
2. **All navigation functions correctly** with no broken links
3. **Database operations complete successfully** 
4. **User authentication works reliably**
5. **No references to deleted files cause errors**
6. **System performance remains acceptable**
7. **Admin functionality operates normally**

---

## 📋 Issue Tracking Template

When issues are found, document using this format:

```
**Issue ID:** UAT-001
**Scenario:** [Scenario Name]
**Severity:** Critical/High/Medium/Low
**Description:** [What happened]
**Steps to Reproduce:** [Exact steps]
**Expected Result:** [What should happen]
**Actual Result:** [What actually happened]
**Browser/Environment:** [Testing environment]
**Status:** Open/In Progress/Resolved
```

---

## 🎉 UAT Sign-off

**Testing Completed By:** _________________  
**Date:** _________________  
**Overall Result:** Pass / Fail / Pass with Minor Issues  
**Approved for Production:** Yes / No  
**Additional Notes:** _________________

*UAT Plan v1.0 - Generated after dead code cleanup completion*