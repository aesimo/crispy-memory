#!/bin/bash

# IdeaOne Security Check Script
# Comprehensive security audit for the application

echo "=== IdeaOne Security Audit ==="
echo ""

passed=0
warnings=0
errors=0

# Color codes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "[+] Checking Environment Variables..."

# Check if .env file exists
if [ -f ".env" ]; then
    # Check if .env is using default values
    if grep -q "your-database-password\|your-anon-key\|your-service-role-key\|your-razorpay-key\|your-jwt-secret-change-this" .env; then
        echo -e "  ${RED}✗${NC} .env file contains default values - must be changed"
        ((errors++))
    else
        echo -e "  ${GREEN}✓${NC} .env file does not contain default values"
        ((passed++))
    fi
else
    echo -e "  ${YELLOW}⚠${NC} .env file not found (expected in production)"
    ((warnings++))
fi

echo ""
echo "[+] Checking File Permissions..."

# Check .env file permissions
if [ -f ".env" ]; then
    perms=$(stat -c %a .env)
    if [ "$perms" -le "644" ]; then
        echo -e "  ${GREEN}✓${NC} .env has secure permissions: $perms"
        ((passed++))
    else
        echo -e "  ${YELLOW}⚠${NC} .env has permissive permissions: $perms"
        ((warnings++))
    fi
fi

# Check config files permissions
for file in config/config.php classes/Database.php classes/Payment.php; do
    if [ -f "$file" ]; then
        perms=$(stat -c %a "$file")
        if [ "$perms" -le "644" ]; then
            echo -e "  ${GREEN}✓${NC} $file has secure permissions: $perms"
            ((passed++))
        else
            echo -e "  ${YELLOW}⚠${NC} $file has permissive permissions: $perms"
            ((warnings++))
        fi
    fi
done

echo ""
echo "[+] Checking for Exposed Sensitive Files..."

# Check for files that shouldn't exist
sensitive_files=(".env.local" ".env.production" "database.sqlite" "passwords.txt" "secrets.txt")
for file in "${sensitive_files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  ${RED}✗${NC} Sensitive file found: $file"
        ((errors++))
    fi
done

# Check .gitignore
if [ -f ".gitignore" ]; then
    if grep -q "\.env" .gitignore; then
        echo -e "  ${GREEN}✓${NC} .env is in .gitignore"
        ((passed++))
    else
        echo -e "  ${RED}✗${NC} .env is not in .gitignore"
        ((errors++))
    fi
fi

echo ""
echo "[+] Checking Code Security..."

# Check for hardcoded API keys in PHP files
hardcoded_keys=$(grep -r "sk_test_\|sk_live_\|AIza\|AKIA" --include="*.php" . 2>/dev/null | grep -v "security_check" | grep -v "Binary" | wc -l)
if [ "$hardcoded_keys" -eq "0" ]; then
    echo -e "  ${GREEN}✓${NC} No hardcoded API keys found in PHP files"
    ((passed++))
else
    echo -e "  ${RED}✗${NC} Found $hardcoded_keys potential hardcoded API keys"
    ((errors++))
fi

# Check for SQL injection vulnerabilities
sql_injection=$(grep -r "mysql_query.*\$_\|query.*\$_GET\|query.*\$_POST" --include="*.php" . 2>/dev/null | grep -v "security_check" | grep -v "Binary" | wc -l)
if [ "$sql_injection" -eq "0" ]; then
    echo -e "  ${GREEN}✓${NC} No obvious SQL injection vulnerabilities found"
    ((passed++))
else
    echo -e "  ${YELLOW}⚠${NC} Found $sql_injection potential SQL injection vulnerabilities"
    ((warnings++))
fi

echo ""
echo "[+] Checking API Endpoints Security..."

api_files=$(find api -name "*.php" -not -name "middleware.php" 2>/dev/null)
if [ -n "$api_files" ]; then
    for file in $api_files; do
        basename_file=$(basename "$file")
        
        # Check if middleware is included
        if grep -q "middleware.php" "$file"; then
            echo -e "  ${GREEN}✓${NC} API endpoint $basename_file includes security middleware"
            ((passed++))
        else
            echo -e "  ${YELLOW}⚠${NC} API endpoint $basename_file does not include security middleware"
            ((warnings++))
        fi
        
        # Check for rate limiting
        if grep -q "checkRateLimit" "$file"; then
            echo -e "  ${GREEN}✓${NC} API endpoint $basename_file has rate limiting"
            ((passed++))
        else
            echo -e "  ${YELLOW}⚠${NC} API endpoint $basename_file does not have rate limiting"
            ((warnings++))
        fi
    done
fi

echo ""
echo "[+] Checking Database Security..."

# Check for prepared statements
if [ -f "classes/Database.php" ]; then
    if grep -q "prepare" classes/Database.php; then
        echo -e "  ${GREEN}✓${NC} Database class uses prepared statements"
        ((passed++))
    else
        echo -e "  ${RED}✗${NC} Database class does not use prepared statements"
        ((errors++))
    fi
fi

# Check for Supabase RLS
if [ -f "database/supabase-schema.sql" ]; then
    if grep -q "ENABLE ROW LEVEL SECURITY" database/supabase-schema.sql; then
        echo -e "  ${GREEN}✓${NC} Supabase Row Level Security is configured"
        ((passed++))
    else
        echo -e "  ${YELLOW}⚠${NC} Supabase Row Level Security not found in schema"
        ((warnings++))
    fi
fi

echo ""
echo "[+] Checking Frontend Security..."

# Check for exposed secrets in JavaScript
js_secrets=$(grep -r "RAZORPAY_KEY_ID\|SUPABASE.*KEY" --include="*.js" assets/ 2>/dev/null | wc -l)
if [ "$js_secrets" -eq "0" ]; then
    echo -e "  ${GREEN}✓${NC} No exposed secrets in JavaScript files"
    ((passed++))
else
    echo -e "  ${RED}✗${NC} Found $js_secrets potential secrets in JavaScript"
    ((errors++))
fi

# Check if public pages have direct database access
public_pages=("pages/categories.php" "pages/pricing.php" "index.php")
for page in "${public_pages[@]}"; do
    if [ -f "$page" ]; then
        if grep -q "Database::" "$page"; then
            echo -e "  ${YELLOW}⚠${NC} Public page $page has direct database queries"
            ((warnings++))
        else
            echo -e "  ${GREEN}✓${NC} Public page $page does not have direct database access"
            ((passed++))
        fi
    fi
done

echo ""
echo "[+] Checking Security Infrastructure..."

# Check for .htaccess
if [ -f ".htaccess" ]; then
    echo -e "  ${GREEN}✓${NC} .htaccess security configuration found"
    ((passed++))
else
    echo -e "  ${YELLOW}⚠${NC} .htaccess not found (Apache web server)"
    ((warnings++))
fi

# Check for security classes
if [ -f "classes/CSRFProtection.php" ]; then
    echo -e "  ${GREEN}✓${NC} CSRF Protection class found"
    ((passed++))
else
    echo -e "  ${YELLOW}⚠${NC} CSRF Protection class not found"
    ((warnings++))
fi

if [ -f "classes/InputValidator.php" ]; then
    echo -e "  ${GREEN}✓${NC} Input Validator class found"
    ((passed++))
else
    echo -e "  ${YELLOW}⚠${NC} Input Validator class not found"
    ((warnings++))
fi

if [ -f "config/security.php" ]; then
    echo -e "  ${GREEN}✓${NC} Security configuration found"
    ((passed++))
else
    echo -e "  ${YELLOW}⚠${NC} Security configuration not found"
    ((warnings++))
fi

# Check for Railway deployment config
if [ -f "railway.json" ]; then
    echo -e "  ${GREEN}✓${NC} Railway deployment configuration found"
    ((passed++))
else
    echo -e "  ${YELLOW}⚠${NC} Railway deployment configuration not found"
    ((warnings++))
fi

echo ""
echo "=== Security Audit Report ==="
echo ""
echo -e "${GREEN}✓ Passed: $passed${NC}"
echo -e "${YELLOW}⚠ Warnings: $warnings${NC}"
echo -e "${RED}✗ Errors: $errors${NC}"
echo ""

total_issues=$((warnings + errors))
if [ $total_issues -eq 0 ]; then
    echo -e "${GREEN}🎉 All security checks passed!${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️ Found $total_issues issues that need attention.${NC}"
    if [ $errors -gt 0 ]; then
        exit 1
    else
        exit 0
    fi
fi