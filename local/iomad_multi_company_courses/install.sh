#!/bin/bash

# IOMAD Multi-Company Courses Plugin Installation Script
# This script helps with the installation of the plugin

echo "IOMAD Multi-Company Courses Plugin Installation"
echo "==============================================="

# Check if we're in the right directory
if [ ! -f "config.php" ]; then
    echo "Error: This script must be run from the Moodle root directory"
    exit 1
fi

# Check if IOMAD is installed
if [ ! -d "local/iomad" ]; then
    echo "Error: IOMAD local plugin not found. Please install IOMAD first."
    exit 1
fi

if [ ! -d "blocks/iomad_company_admin" ]; then
    echo "Error: IOMAD Company Admin block not found. Please install IOMAD first."
    exit 1
fi

# Check if plugin directory already exists
if [ -d "local/iomad_multi_company_courses" ]; then
    echo "Warning: Plugin directory already exists. This will overwrite existing files."
    read -p "Continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Installation cancelled."
        exit 1
    fi
fi

echo "Plugin appears to be properly structured."
echo ""
echo "Next steps:"
echo "1. Visit your Moodle site as an administrator"
echo "2. Go to Site Administration > Notifications"
echo "3. Follow the prompts to complete the plugin installation"
echo "4. The plugin will appear in the IOMAD Company Admin interface under Course Admin"
echo ""
echo "Requirements:"
echo "- Companies must have company codes populated for search functionality"
echo "- Users need appropriate capabilities to access the feature"
echo ""
echo "Installation script completed successfully!"