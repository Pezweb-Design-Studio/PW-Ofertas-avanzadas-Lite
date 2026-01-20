#!/bin/bash
set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${BLUE}🚀 PW Ofertas Avanzadas Plugin Build${NC}"
echo "====================================="

edition="$1"

if [ -z "$edition" ]; then
    echo -e "${RED}❌ Error: Debes especificar la edición${NC}"
    echo "Uso: bash build-deploy-v2.sh [lite|pro]"
    exit 1
fi

if [ "$edition" != "lite" ] && [ "$edition" != "pro" ]; then
    echo -e "${RED}❌ Error: Edición inválida '$edition'${NC}"
    exit 1
fi

if [ "$edition" = "lite" ]; then
    main_file="pw-ofertas-avanzadas.lite.php"
else
    main_file="pw-ofertas-avanzadas-pro.php"
fi

echo -e "${CYAN}Edición: ${YELLOW}$edition${NC}"
echo -e "${CYAN}Archivo fuente: ${YELLOW}$main_file${NC}"
echo ""

if [ ! -f "$main_file" ]; then
    echo -e "${RED}❌ No se encontró: $main_file${NC}"
    exit 1
fi

current_version=$(grep "Version:" "$main_file" | sed 's/.*Version: *//' | sed 's/ *\*.*$//' | tr -d '\n\r')
if [ -z "$current_version" ]; then
    current_version="1.0.0"
fi

IFS='.' read -ra VERSION_PARTS <<< "$current_version"
major=${VERSION_PARTS[0]:-1}
minor=${VERSION_PARTS[1]:-0}
patch=${VERSION_PARTS[2]:-0}

echo -e "${CYAN}Current version: ${YELLOW}$current_version${NC}"
echo ""
echo "Select version increment:"
echo -e "${RED}1)${NC} MAJOR (${major}.x.x → $((major + 1)).0.0)"
echo -e "${YELLOW}2)${NC} MINOR (x.${minor}.x → ${major}.$((minor + 1)).0)"
echo -e "${GREEN}3)${NC} PATCH (x.x.${patch} → ${major}.${minor}.$((patch + 1)))"
echo ""

read -p "Choose [1-3, default=3]: " -r increment_type
increment_type=${increment_type:-3}

case $increment_type in
    1) new_version="$((major + 1)).0.0"; change_type="MAJOR" ;;
    2) new_version="${major}.$((minor + 1)).0"; change_type="MINOR" ;;
    3) new_version="${major}.${minor}.$((patch + 1))"; change_type="PATCH" ;;
    *) new_version="${major}.${minor}.$((patch + 1))"; change_type="PATCH" ;;
esac

echo ""
echo -e "${BLUE}Building:${NC} ${current_version} → ${GREEN}${new_version}${NC} (${change_type})"
echo -e "${BLUE}Output:${NC} releases/pw-ofertas-avanzadas-${edition}-v${new_version}.zip"
echo ""
read -p "Continue? (y/N): " -r
[[ ! $REPLY =~ ^[Yy]$ ]] && exit 0

echo -e "${YELLOW}[1/4]${NC} Updating version..."

if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s/Version: .*/Version: $new_version/" "$main_file"
    sed -i '' "s/define('PWOA_VERSION', '[^']*')/define('PWOA_VERSION', '$new_version')/" "$main_file"
else
    sed -i "s/Version: .*/Version: $new_version/" "$main_file"
    sed -i "s/define('PWOA_VERSION', '[^']*')/define('PWOA_VERSION', '$new_version')/" "$main_file"
fi

echo -e "${GREEN}✅ Version updated${NC}"

echo -e "${YELLOW}[2/4]${NC} Installing dependencies..."
if [ -f "composer.json" ]; then
    rm -rf vendor
    composer install --no-dev --optimize-autoloader --quiet
    echo -e "${GREEN}✅ Composer done${NC}"
fi

echo -e "${YELLOW}[3/4]${NC} Building $edition edition..."

mkdir -p releases
zip_file="releases/pw-ofertas-avanzadas-${edition}-v${new_version}.zip"
rm -f "$zip_file"

temp_parent="temp_build_$$"
plugin_dir="$temp_parent/pw-ofertas-avanzadas"
mkdir -p "$plugin_dir"

# Archivo principal
cp "$main_file" "$plugin_dir/pw-ofertas-avanzadas.php"

# README y LICENCE
cp README.md "$plugin_dir/"
cp LICENCE.txt "$plugin_dir/"

# Composer
cp composer.json "$plugin_dir/"
[ -f "composer.lock" ] && cp composer.lock "$plugin_dir/"

# Vendor
cp -r vendor "$plugin_dir/"

# Assets base
mkdir -p "$plugin_dir/assets/js"
cp assets/js/wizard.js "$plugin_dir/assets/js/"

# SRC base
mkdir -p "$plugin_dir/src"

# ============================================
# COPIA SELECTIVA SEGÚN EDICIÓN
# ============================================

if [ "$edition" = "lite" ]; then
    echo "  → Building LITE edition"

    # Admin (LITE)
    mkdir -p "$plugin_dir/src/Admin/Views"
    cp src/Admin/AdminController.lite.php "$plugin_dir/src/Admin/AdminController.php"
    cp src/Admin/Views/dashboard.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/wizard.lite.php "$plugin_dir/src/Admin/Views/wizard.php"
    cp assets/js/wizard.lite-addon.js "$plugin_dir/assets/js/"

    # Core (LITE)
    mkdir -p "$plugin_dir/src/Core"
    cp src/Core/Activator.lite.php "$plugin_dir/src/Core/Activator.php"
    cp src/Core/Deactivator.php "$plugin_dir/src/Core/"
    cp src/Core/Plugin.php "$plugin_dir/src/Core/"

    # Handlers (compartidos)
    mkdir -p "$plugin_dir/src/Handlers"
    cp src/Handlers/CartHandler.php "$plugin_dir/src/Handlers/"
    cp src/Handlers/OrderHandler.php "$plugin_dir/src/Handlers/"
    cp src/Handlers/ProductBadgeHandler.php "$plugin_dir/src/Handlers/"
    cp src/Handlers/ProductExpiryHandler.php "$plugin_dir/src/Handlers/"

    # Repositories (solo Campaign para LITE)
    mkdir -p "$plugin_dir/src/Repositories"
    cp src/Repositories/CampaignRepository.php "$plugin_dir/src/Repositories/"

    # Services (compartidos)
    mkdir -p "$plugin_dir/src/Services"
    cp src/Services/DiscountEngine.php "$plugin_dir/src/Services/"
    cp src/Services/ProductMatcher.php "$plugin_dir/src/Services/"

    # Strategies (SOLO LITE)
    mkdir -p "$plugin_dir/src/Strategies/Lite"
    cp src/Strategies/DiscountStrategy.php "$plugin_dir/src/Strategies/"
    cp -r src/Strategies/Lite/* "$plugin_dir/src/Strategies/Lite/"

else
    echo "  → Building PRO edition"

    # Admin (PRO)
    mkdir -p "$plugin_dir/src/Admin/Views"
    cp src/Admin/AdminController.php "$plugin_dir/src/Admin/"
    cp src/Admin/Views/dashboard.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/wizard.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/analytics.php "$plugin_dir/src/Admin/Views/"
    cp assets/js/analytics.js "$plugin_dir/assets/js/"

    # Core (PRO)
    mkdir -p "$plugin_dir/src/Core"
    cp src/Core/Activator.php "$plugin_dir/src/Core/"
    cp src/Core/Deactivator.php "$plugin_dir/src/Core/"
    cp src/Core/Plugin.php "$plugin_dir/src/Core/"

    # Handlers (todos)
    mkdir -p "$plugin_dir/src/Handlers"
    cp src/Handlers/*.php "$plugin_dir/src/Handlers/"

    # Repositories (todos)
    mkdir -p "$plugin_dir/src/Repositories"
    cp src/Repositories/*.php "$plugin_dir/src/Repositories/"

    # Services (todos)
    mkdir -p "$plugin_dir/src/Services"
    cp src/Services/*.php "$plugin_dir/src/Services/"

    # Strategies (LITE + PRO)
    mkdir -p "$plugin_dir/src/Strategies/Lite"
    mkdir -p "$plugin_dir/src/Strategies/Pro"
    cp src/Strategies/DiscountStrategy.php "$plugin_dir/src/Strategies/"
    cp -r src/Strategies/Lite/* "$plugin_dir/src/Strategies/Lite/"
    cp -r src/Strategies/Pro/* "$plugin_dir/src/Strategies/Pro/"
fi

# Limpiar
cd "$plugin_dir"
find . -name "*.map" -delete 2>/dev/null || true
find . -name ".DS_Store" -delete 2>/dev/null || true
find vendor/ -name "*.md" -delete 2>/dev/null || true
find vendor/ -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true

cd ../..
cd "$temp_parent"
zip -r "../$zip_file" pw-ofertas-avanzadas >/dev/null 2>&1
cd ..
rm -rf "$temp_parent"

echo -e "${GREEN}✅ ZIP created: $zip_file${NC}"

echo -e "${YELLOW}[4/4]${NC} Verifying..."
unzip -l "$zip_file" | head -20

zip_size=$(du -sh "$zip_file" | cut -f1)
echo ""
echo "================================="
echo -e "${GREEN}🎉 BUILD COMPLETE${NC}"
echo "================================="
echo "📦 $zip_file"
echo "📏 Size: $zip_size"
echo "📖 Version: $new_version ($change_type)"
echo "🏷️  Edition: $edition"
echo ""