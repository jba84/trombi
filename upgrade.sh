#!/bin/bash

# Upgrade script for Staff Directory v1.3.0
# This script should be run from the project's root directory.

# --- Color Codes for Output ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# --- Start Upgrade Process ---
echo -e "${GREEN}Starting upgrade process for Staff Directory v1.3.0...${NC}"

# 1. Update Composer Dependencies
echo -e "\n${YELLOW}Step 1: Updating Composer dependencies...${NC}"
if command -v composer &> /dev/null
then
    composer install --no-dev --optimize-autoloader
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Composer dependencies updated successfully.${NC}"
    else
        echo -e "${RED}Error: Composer dependency installation failed. Please run 'composer install' manually to debug.${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}Warning: Composer is not installed or not in the system's PATH. Skipping dependency update. Please ensure your dependencies are up to date manually.${NC}"
fi

# 2. Run Database Migration
echo -e "\n${YELLOW}Step 2: Running database migration script...${NC}"
php database/upgrade_1_3_0.php
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Database migration completed successfully.${NC}"
else
    echo -e "${RED}Error: Database migration failed. Please check the output above for details.${NC}"
    exit 1
fi

# 3. Set File Permissions
echo -e "\n${YELLOW}Step 3: Setting file permissions for required directories...${NC}"

# Define the web server user/group. Common defaults are www-data, apache, or nginx.
# You might need to change this depending on your server configuration.
WEB_USER="www-data"

# Directories that need write access by the web server
WRITABLE_DIRS=(
    "public/uploads/companies"
    "public/uploads/logos"
    "public/uploads/placeholders"
    "logs"
)

for dir in "${WRITABLE_DIRS[@]}"
do
    if [ -d "$dir" ]; then
        # Set ownership to the web user and group
        chown -R $WEB_USER:$WEB_USER "$dir"
        # Set directory permissions to 775 (rwxrwxr-x) and file permissions to 664 (rw-rw-r--)
        find "$dir" -type d -exec chmod 775 {} \;
        find "$dir" -type f -exec chmod 664 {} \;
        echo "Permissions set for $dir"
    else
        echo -e "${YELLOW}Warning: Directory '$dir' not found. Skipping permissions setting for it.${NC}"
    fi
done

echo -e "${GREEN}File permissions updated.${NC}"

# --- Final Message ---
echo -e "\n${GREEN}Upgrade to version 1.3.0 completed successfully!${NC}"
echo -e "Please clear your browser cache to ensure all changes are reflected.${NC}"
