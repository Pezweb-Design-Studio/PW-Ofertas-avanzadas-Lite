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

# Parsear argumentos
if [ "$1" = "deploy" ]; then
    mode="deploy"
    edition="$2"
else
    mode="build"
    edition="$1"
fi

# Si no hay edición, mostrar ayuda
if [ -z "$edition" ]; then
    echo -e "${RED}❌ Error: Debes especificar la edición${NC}"
    echo ""
    echo "Uso:"
    echo "  ${CYAN}./build-deploy.sh deploy [lite|pro]${NC}  - Incrementa versión y crea ZIP"
    echo "  ${CYAN}./build-deploy.sh [lite|pro]${NC}         - Copia archivos para desarrollo"
    echo ""
    echo "NPM Scripts:"
    echo "  ${CYAN}npm run build:lite${NC} / ${CYAN}npm run build:pro${NC}"
    echo "  ${CYAN}npm run deploy:lite${NC} / ${CYAN}npm run deploy:pro${NC}"
    exit 1
fi

# Validar edición
if [ "$edition" != "lite" ] && [ "$edition" != "pro" ]; then
    echo -e "${RED}❌ Error: Edición inválida '$edition'${NC}"
    exit 1
fi

# Determinar archivo principal
if [ "$edition" = "lite" ]; then
    main_file="pw-ofertas-avanzadas.lite.php"
else
    main_file="pw-ofertas-avanzadas-pro.php"
fi

echo -e "${CYAN}Modo: ${YELLOW}${mode}${NC}"
echo -e "${CYAN}Edición: ${YELLOW}$edition${NC}"
echo -e "${CYAN}Archivo fuente: ${YELLOW}$main_file${NC}"
echo ""

# Verificar que existe el archivo
if [ ! -f "$main_file" ]; then
    echo -e "${RED}❌ No se encontró: $main_file${NC}"
    exit 1
fi

# Leer versión actual
current_version=$(grep "Version:" "$main_file" | sed 's/.*Version: *//' | sed 's/ *\*.*$//' | tr -d '\n\r')
if [ -z "$current_version" ]; then
    current_version="1.0.0"
fi

new_version="$current_version"

# ============================================
# INCREMENTO DE VERSIÓN (solo si mode=deploy)
# ============================================
if [ "$mode" = "deploy" ]; then
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
    echo ""
    read -p "Continue? (y/N): " -r
    [[ ! $REPLY =~ ^[Yy]$ ]] && exit 0

    echo -e "${YELLOW}[1/5]${NC} Updating version..."

    # Actualizar versión en el archivo
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "s/Version: .*/Version: $new_version/" "$main_file"
        sed -i '' "s/define('PWOA_VERSION', '[^']*')/define('PWOA_VERSION', '$new_version')/" "$main_file"
    else
        sed -i "s/Version: .*/Version: $new_version/" "$main_file"
        sed -i "s/define('PWOA_VERSION', '[^']*')/define('PWOA_VERSION', '$new_version')/" "$main_file"
    fi

    echo -e "${GREEN}✅ Version updated${NC}"
else
    echo -e "${CYAN}Skipping version increment (build mode)${NC}"
    echo -e "${CYAN}Using current version: ${YELLOW}$current_version${NC}"
    echo ""
fi

# ============================================
# COMPOSER INSTALL
# ============================================
echo -e "${YELLOW}[2/5]${NC} Installing dependencies..."
if [ -f "composer.json" ]; then
    rm -rf vendor
    composer install --no-dev --optimize-autoloader --quiet
    echo -e "${GREEN}✅ Composer done${NC}"
fi

# ============================================
# BUILD
# ============================================
echo -e "${YELLOW}[3/5]${NC} Building $edition edition..."

# Determinar directorio de salida (SIEMPRE dentro de releases/edition/)
output_dir="releases/$edition"
mkdir -p "$output_dir"

# Limpiar SOLO el árbol del plugin (no los ZIPs en output_dir).
# LITE: conservar .git / .gitignore / .gitattributes dentro de pw-ofertas-avanzadas
# si el repo de GitHub usa la carpeta del plugin como raíz.
plugin_dir="$output_dir/pw-ofertas-avanzadas"
echo "  → Cleaning $plugin_dir..."
if [ "$edition" = "lite" ] && [ -d "$plugin_dir" ]; then
    _pwoa_git_stash=$(mktemp -d "${TMPDIR:-/tmp}/pwoa-lite-git.XXXXXX")
    for _p in .git .gitignore .gitattributes; do
        if [ -e "$plugin_dir/$_p" ]; then
            mv "$plugin_dir/$_p" "$_pwoa_git_stash/"
        fi
    done
    rm -rf "$plugin_dir"
    mkdir -p "$plugin_dir"
    for _p in .git .gitignore .gitattributes; do
        if [ -e "$_pwoa_git_stash/$_p" ]; then
            mv "$_pwoa_git_stash/$_p" "$plugin_dir/"
        fi
    done
    rmdir "$_pwoa_git_stash" 2>/dev/null || rm -rf "$_pwoa_git_stash"
else
    rm -rf "$plugin_dir"
    mkdir -p "$plugin_dir"
fi

# Archivo principal
cp "$main_file" "$plugin_dir/pw-ofertas-avanzadas.php"

cp LICENCE.txt "$plugin_dir/"

# Composer
cp composer.json "$plugin_dir/"
[ -f "composer.lock" ] && cp composer.lock "$plugin_dir/"

# Vendor
cp -r vendor "$plugin_dir/"

# ============================================
# COPIA SELECTIVA SEGÚN EDICIÓN
# ============================================

if [ "$edition" = "lite" ]; then
    echo "  → Building LITE edition"

    # readme: build → README.md (GitHub); deploy → readme.txt (WordPress.org / ZIP)
    if [ "$mode" = "deploy" ]; then
        if [ ! -f "readme.txt" ]; then
            echo -e "${RED}❌ Falta readme.txt en la raíz (requerido para deploy Lite / WordPress.org).${NC}"
            exit 1
        fi
        cp readme.txt "$plugin_dir/readme.txt"
    else
        if [ ! -f "README.md" ]; then
            echo -e "${RED}❌ Falta README.md en la raíz (requerido para build Lite / repo).${NC}"
            exit 1
        fi
        cp README.md "$plugin_dir/README.md"
    fi

    # Admin (LITE)
    mkdir -p "$plugin_dir/src/Admin/Views"
    cp src/Admin/AdminController.lite.php "$plugin_dir/src/Admin/AdminController.php"
    cp src/Admin/Views/dashboard.lite.php "$plugin_dir/src/Admin/Views/dashboard.php"
    cp src/Admin/Views/wizard.lite.php "$plugin_dir/src/Admin/Views/wizard.php"
    cp src/Admin/Views/shortcodes.php "$plugin_dir/src/Admin/Views/"

    # Core (LITE)
    mkdir -p "$plugin_dir/src/Core"
    cp src/Core/Activator.lite.php "$plugin_dir/src/Core/Activator.php"
    cp src/Core/Plugin.lite.php "$plugin_dir/src/Core/Plugin.php"
    cp src/Core/Deactivator.php "$plugin_dir/src/Core/"

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

    # Shortcodes (compartidos)
    mkdir -p "$plugin_dir/src/Shortcodes"
    cp src/Shortcodes/ProductsShortcode.php "$plugin_dir/src/Shortcodes/"

    # Strategies (SOLO LITE)
    mkdir -p "$plugin_dir/src/Strategies/Lite"
    cp src/Strategies/DiscountStrategy.php "$plugin_dir/src/Strategies/"
    cp -r src/Strategies/Lite/* "$plugin_dir/src/Strategies/Lite/"

else
    echo "  → Building PRO edition"

    if [ "$mode" = "deploy" ]; then
        [ -f "readme.txt" ] && cp readme.txt "$plugin_dir/"
    else
        [ -f "README.md" ] && cp README.md "$plugin_dir/"
    fi

    # Admin (PRO)
    mkdir -p "$plugin_dir/src/Admin/Views/data"
    cp src/Admin/AdminController.php "$plugin_dir/src/Admin/"
    cp src/Admin/Views/dashboard.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/wizard.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/analytics.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/settings.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/shortcodes.php "$plugin_dir/src/Admin/Views/"
    cp src/Admin/Views/data/stacking-options.php "$plugin_dir/src/Admin/Views/data/"

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

    # Shortcodes (compartidos)
    mkdir -p "$plugin_dir/src/Shortcodes"
    cp src/Shortcodes/ProductsShortcode.php "$plugin_dir/src/Shortcodes/"

    # Strategies (LITE + PRO)
    mkdir -p "$plugin_dir/src/Strategies/Lite"
    mkdir -p "$plugin_dir/src/Strategies/Pro"
    cp src/Strategies/DiscountStrategy.php "$plugin_dir/src/Strategies/"
    cp -r src/Strategies/Lite/* "$plugin_dir/src/Strategies/Lite/"
    cp -r src/Strategies/Pro/* "$plugin_dir/src/Strategies/Pro/"
fi

# ============================================
# COMPARTIDO: AdminAssets, i18n, traducciones, assets
# ============================================
echo "  → Shared files (AdminAssets, I18n, languages, assets)..."

mkdir -p "$plugin_dir/src/Admin"
cp src/Admin/AdminAssets.php "$plugin_dir/src/Admin/"

mkdir -p "$plugin_dir/src/Core"
cp src/Core/Schema.php "$plugin_dir/src/Core/"
cp src/Core/I18n.php "$plugin_dir/src/Core/"
cp src/Core/UpgradeUrl.php "$plugin_dir/src/Core/"

if [ -d "languages" ]; then
    mkdir -p "$plugin_dir/languages"
    cp languages/* "$plugin_dir/languages/" 2>/dev/null || true
    if command -v msgfmt >/dev/null 2>&1; then
        for po in "$plugin_dir/languages"/*.po; do
            [ -f "$po" ] || continue
            msgfmt -o "${po%.po}.mo" "$po"
        done
    fi
fi

[ -f "readme_es.txt" ] && cp readme_es.txt "$plugin_dir/"

mkdir -p "$plugin_dir/assets/js" "$plugin_dir/assets/css"
cp assets/js/wizard.js "$plugin_dir/assets/js/"
if [ "$edition" = "lite" ]; then
    cp assets/js/wizard.lite-addon.js "$plugin_dir/assets/js/"
    cp assets/js/admin-dashboard-lite.js "$plugin_dir/assets/js/"
else
    cp assets/js/admin-dashboard.js "$plugin_dir/assets/js/"
    cp assets/js/analytics.js "$plugin_dir/assets/js/"
fi
cp assets/js/admin-settings.js "$plugin_dir/assets/js/"
cp assets/js/admin-shortcodes.js "$plugin_dir/assets/js/"
cp assets/js/product-badges.js "$plugin_dir/assets/js/"

shopt -s nullglob
for css in assets/css/*.css; do
    cp "$css" "$plugin_dir/assets/css/"
done
shopt -u nullglob

# ============================================
# LITE: Git en releases/lite/pw-ofertas-avanzadas/ (carpeta que se pushea a GitHub)
# ============================================
if [ "$edition" = "lite" ]; then
    LITE_GITHUB_REMOTE="https://github.com/Pezweb-Design-Studio/PW-Ofertas-avanzadas-Lite.git"
    if [ -f "build-support/lite-plugin-repo.gitignore" ] && [ ! -f "$plugin_dir/.gitignore" ]; then
        cp "build-support/lite-plugin-repo.gitignore" "$plugin_dir/.gitignore"
    fi
    if command -v git >/dev/null 2>&1; then
        (
            cd "$plugin_dir" || exit 0
            if [ ! -d .git ]; then
                git init -b main >/dev/null 2>&1 || git init >/dev/null 2>&1
            fi
            if git remote get-url origin >/dev/null 2>&1; then
                git remote set-url origin "$LITE_GITHUB_REMOTE"
            else
                git remote add origin "$LITE_GITHUB_REMOTE" 2>/dev/null || true
            fi
        )
        echo "  → Lite: $plugin_dir → origin $LITE_GITHUB_REMOTE"
    else
        echo "  → Lite: git no encontrado; configura el remoto en $plugin_dir"
    fi
fi

# ============================================
# LIMPIEZA
# ============================================
echo -e "${YELLOW}[4/5]${NC} Cleaning up..."
cd "$plugin_dir"
find . -name "*.map" -delete 2>/dev/null || true
find . -name ".DS_Store" -delete 2>/dev/null || true
find vendor/ -name "*.md" -delete 2>/dev/null || true
find vendor/ -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true
cd - >/dev/null

# ============================================
# CREAR ZIP (solo en modo deploy)
# ============================================
if [ "$mode" = "deploy" ]; then
    echo -e "${YELLOW}[5/5]${NC} Creating ZIP..."

    zip_file="$output_dir/pw-ofertas-avanzadas-${edition}-v${new_version}.zip"
    rm -f "$zip_file"

    cd "$output_dir"
    zip -r "pw-ofertas-avanzadas-${edition}-v${new_version}.zip" pw-ofertas-avanzadas >/dev/null 2>&1
    cd - >/dev/null

    echo -e "${GREEN}✅ ZIP created: $zip_file${NC}"

    # Verificación
    unzip -l "$zip_file" | head -20

    zip_size=$(du -sh "$zip_file" | cut -f1)
    echo ""
    echo "================================="
    echo -e "${GREEN}🎉 DEPLOY COMPLETE${NC}"
    echo "================================="
    echo "📦 $zip_file"
    echo "📁 Size: $zip_size"
    echo "📖 Version: $new_version (${change_type})"
    echo "🏷️  Edition: $edition"
    echo ""
else
    echo -e "${YELLOW}[5/5]${NC} Skipping ZIP (build mode)..."

    echo ""
    echo "================================="
    echo -e "${GREEN}🎉 BUILD COMPLETE${NC}"
    echo "================================="
    echo "📂 Output: $plugin_dir"
    echo "📖 Version: $current_version"
    echo "🏷️  Edition: $edition"
    echo ""
    echo "Files ready for development/testing"
    echo ""
fi
