#!/bin/bash
# build-deploy.sh - PW Ofertas Avanzadas WordPress Plugin Builder

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${BLUE}🚀 PW Ofertas Avanzadas Plugin Build${NC}"
echo "====================================="

# Archivo principal del plugin
main_file="pw-ofertas-avanzadas.php"

if [ ! -f "$main_file" ]; then
    echo -e "${RED}❌ No se encontró el archivo principal del plugin: $main_file${NC}"
    exit 1
fi

# Get current version
current_version=$(grep "Version:" "$main_file" | sed 's/.*Version: *//' | sed 's/ *\*.*$//' | tr -d '\n\r')
if [ -z "$current_version" ]; then
    current_version="1.0.0"
    echo -e "${YELLOW}⚠ No se encontró versión actual, usando 1.0.0${NC}"
fi

IFS='.' read -ra VERSION_PARTS <<< "$current_version"
major=${VERSION_PARTS[0]:-1}
minor=${VERSION_PARTS[1]:-0}
patch=${VERSION_PARTS[2]:-0}

echo -e "${CYAN}Current version: ${YELLOW}$current_version${NC}"
echo ""
echo "Select version increment type:"
echo ""
echo -e "${RED}1)${NC} MAJOR (${major}.x.x → $((major + 1)).0.0)"
echo "   Breaking changes, incompatible API, major features"
echo ""
echo -e "${YELLOW}2)${NC} MINOR (x.${minor}.x → ${major}.$((minor + 1)).0)"
echo "   New features, backwards compatible"
echo ""
echo -e "${GREEN}3)${NC} PATCH (x.x.${patch} → ${major}.${minor}.$((patch + 1)))"
echo "   Bug fixes, small improvements"
echo ""

read -p "Choose increment type [1-3, default=3]: " -r increment_type
increment_type=${increment_type:-3}

case $increment_type in
    1)
        new_major=$((major + 1))
        new_version="${new_major}.0.0"
        change_type="MAJOR"
        ;;
    2)
        new_minor=$((minor + 1))
        new_version="${major}.${new_minor}.0"
        change_type="MINOR"
        ;;
    3)
        new_patch=$((patch + 1))
        new_version="${major}.${minor}.${new_patch}"
        change_type="PATCH"
        ;;
    *)
        echo -e "${RED}Invalid option. Defaulting to PATCH${NC}"
        new_patch=$((patch + 1))
        new_version="${major}.${minor}.${new_patch}"
        change_type="PATCH"
        ;;
esac

echo ""
echo -e "${BLUE}Building version:${NC} ${current_version} → ${GREEN}${new_version}${NC} (${change_type})"
echo -e "${BLUE}Output:${NC} releases/pw-ofertas-avanzadas-v${new_version}.zip"
echo ""
read -p "Continue? (y/N): " -r
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo -e "${YELLOW}[1/5]${NC} Updating version numbers..."

# Update version in files (macOS compatible)
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s/Version: .*/Version: $new_version/" "$main_file"
    sed -i '' "s/define('PWOA_VERSION', '[^']*')/define('PWOA_VERSION', '$new_version')/" "$main_file"
else
    sed -i "s/Version: .*/Version: $new_version/" "$main_file"
    sed -i "s/define('PWOA_VERSION', '[^']*')/define('PWOA_VERSION', '$new_version')/" "$main_file"
fi

echo -e "${GREEN}✅ Version updated to $new_version${NC}"

echo -e "${YELLOW}[2/5]${NC} Installing dependencies..."

# Composer dependencies
if [ -f "composer.json" ]; then
    rm -rf vendor
    composer install --no-dev --optimize-autoloader --quiet >/dev/null 2>&1
    echo -e "${GREEN}✅ Composer dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ No composer.json found${NC}"
fi

echo -e "${YELLOW}[3/4]${NC} Creating ZIP with correct structure..."

# Create releases directory
mkdir -p releases

# ZIP file path
zip_file="releases/pw-ofertas-avanzadas-v${new_version}.zip"

# Remove existing ZIP if exists
rm -f "$zip_file"

# Create a temporary directory for the plugin structure
temp_parent="temp_build_$$"
plugin_dir="$temp_parent/pw-ofertas-avanzadas"

# Create the plugin directory structure
mkdir -p "$plugin_dir"

# Copy main plugin file
cp "$main_file" "$plugin_dir/"

# Copy README
cp README.md "$plugin_dir/"

# Copy LICENCE
cp LICENCE.txt "$plugin_dir/"

# Copy directories (estructura exacta del proyecto)
cp -r src "$plugin_dir/"
cp -r assets "$plugin_dir/"
cp -r vendor "$plugin_dir/"

# Copy composer files
cp composer.json "$plugin_dir/"
[ -f "composer.lock" ] && cp composer.lock "$plugin_dir/"

# Clean development files from the plugin directory
cd "$plugin_dir"

# Remove specific development/test files
rm -f build-deploy.sh 2>/dev/null || true
rm -f flatten.sh 2>/dev/null || true
rm -f estructura.txt 2>/dev/null || true
rm -f .gitignore 2>/dev/null || true
rm -rf .git 2>/dev/null || true

# Remove map files and other dev artifacts
find . -name "*.map" -delete 2>/dev/null || true
find . -name ".DS_Store" -delete 2>/dev/null || true

# Clean vendor directory
find vendor/ -name "*.md" -delete 2>/dev/null || true
find vendor/ -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name "test" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name "Tests" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name "Test" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name "phpunit" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name "phpstan" -type d -exec rm -rf {} + 2>/dev/null || true

cd ../..

# Create the ZIP from the temp parent directory (includes pw-ofertas-avanzadas/ folder)
cd "$temp_parent"
zip -r "../$zip_file" pw-ofertas-avanzadas >/dev/null 2>&1
cd ..

# Clean up
rm -rf "$temp_parent"

echo -e "${GREEN}✅ ZIP created: $zip_file${NC}"

echo -e "${YELLOW}[4/4]${NC} Verifying ZIP structure..."
echo "ZIP contents (first 20 files):"
unzip -l "$zip_file" | head -25

# Test extraction to verify structure
test_dir="test_verify_$$"
mkdir "$test_dir"
cd "$test_dir"
unzip -q "../$zip_file"

if [ -d "pw-ofertas-avanzadas" ] && [ -f "pw-ofertas-avanzadas/$main_file" ]; then
    echo -e "${GREEN}✅ ZIP structure is correct!${NC}"
    echo -e "${GREEN}✅ WordPress will detect this plugin properly${NC}"

    # Verify key files
    echo ""
    echo "Key files verified:"
    [ -f "pw-ofertas-avanzadas/$main_file" ] && echo "  ✅ $main_file"
    [ -d "pw-ofertas-avanzadas/src" ] && echo "  ✅ src/"
    [ -d "pw-ofertas-avanzadas/vendor" ] && echo "  ✅ vendor/"
    [ -d "pw-ofertas-avanzadas/assets" ] && echo "  ✅ assets/"
    [ -f "pw-ofertas-avanzadas/composer.json" ] && echo "  ✅ composer.json"

    # Check what was cleaned
    echo ""
    echo "Development files removed:"
    [ ! -f "pw-ofertas-avanzadas/build-deploy.sh" ] && echo "  ✅ build-deploy.sh (removed)"
    [ ! -f "pw-ofertas-avanzadas/flatten.sh" ] && echo "  ✅ flatten.sh (removed)"
    [ ! -f "pw-ofertas-avanzadas/estructura.txt" ] && echo "  ✅ estructura.txt (removed)"
    [ ! -d "pw-ofertas-avanzadas/node_modules" ] && echo "  ✅ node_modules/ (not included)"
else
    echo -e "${RED}❌ ZIP structure is wrong${NC}"
    echo "Found:"
    ls -la
fi

cd ..
rm -rf "$test_dir"

# Show final information
zip_size=$(du -sh "$zip_file" | cut -f1)
echo ""
echo "================================="
echo -e "${GREEN}🎉 BUILD COMPLETE!${NC}"
echo "================================="
echo ""
echo "📦 Package: $zip_file"
echo "📏 Size: $zip_size"
echo "📖 Version: $new_version (${change_type})"
echo "🚀 Ready for WordPress!"
echo ""
echo "To install:"
echo "1. Upload via WordPress admin (Plugins → Add New → Upload)"
echo "2. Or extract to wp-content/plugins/ (will create pw-ofertas-avanzadas/ folder)"
echo ""
echo ""
echo "Next steps:"
echo "- Test the plugin on a fresh WordPress install"
echo "- Commit version changes: git add $main_file composer.json"
echo "- Create release: git commit -m 'Release v${new_version}'"
echo "- Tag release: git tag v${new_version}"
echo "- Push: git push origin main --tags"
echo ""