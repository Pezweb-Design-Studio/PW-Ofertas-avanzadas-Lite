#!/bin/bash

DESTINO="./flattened"

CARPETAS=(
    "./assets"
    "./src"
)

EXTENSIONES_TEXTO=(
    "php" "js" "css" "html" "htm" "json" "xml" "txt" "md"
    "jsx" "tsx" "ts" "vue" "scss" "sass" "less"
    "yml" "yaml" "ini" "conf" "sh" "bash"
)

IGNORAR=(
    ".DS_Store"
    "Thumbs.db"
    ".gitignore"
    ".gitkeep"
    "desktop.ini"
    ".htaccess"
)

mkdir -p "$DESTINO"

debe_ignorar() {
    local archivo="$1"
    local base
    base=$(basename "$archivo")
    for ignorado in "${IGNORAR[@]}"; do
        if [[ "$base" == "$ignorado" ]]; then
            return 0
        fi
    done
    return 1
}

es_texto_valido() {
    local archivo="$1"
    local extension="${archivo##*.}"
    for ext in "${EXTENSIONES_TEXTO[@]}"; do
        if [[ "$extension" == "$ext" ]]; then
            return 0
        fi
    done
    return 1
}

corregir_doble_encoding() {
    local archivo="$1"
    if grep -q 'Ã±\|Ã¡\|Ã©\|Ã­\|Ã³\|Ãº' "$archivo" 2>/dev/null; then
        echo "    [FIX] Corrigiendo doble encoding..."
        if iconv -f ISO-8859-1 -t UTF-8 "$archivo" > "${archivo}.tmp" 2>/dev/null; then
            mv "${archivo}.tmp" "$archivo"
            return 0
        else
            if iconv -f WINDOWS-1252 -t UTF-8 "$archivo" > "${archivo}.tmp" 2>/dev/null; then
                mv "${archivo}.tmp" "$archivo"
                return 0
            fi
            rm -f "${archivo}.tmp"
        fi
    fi
    return 1
}

convertir_utf8() {
    local archivo="$1"
    local destino="$2"
    local encoding
    cp "$archivo" "$destino"
    if corregir_doble_encoding "$destino"; then
        echo "    [OK] Doble encoding corregido"
    fi
    encoding=$(file -b --mime-encoding "$destino")
    if [ "$encoding" != "utf-8" ] && [ "$encoding" != "us-ascii" ]; then
        echo "    [CONV] Convirtiendo..."
        iconv -f "$encoding" -t UTF-8 "$destino" > "${destino}.tmp" 2>/dev/null && mv "${destino}.tmp" "$destino"
    fi
    if command -v dos2unix &> /dev/null; then
        dos2unix -q "$destino" 2>/dev/null
    else
        sed -i 's/\r$//' "$destino" 2>/dev/null || sed -i '' 's/\r$//' "$destino" 2>/dev/null
    fi
    if head -c 3 "$destino" | od -A n -t x1 | grep -q "ef bb bf"; then
        echo "    [DEL] Eliminando BOM..."
        tail -c +4 "$destino" > "${destino}.tmp" && mv "${destino}.tmp" "$destino"
    fi
}

echo "==> Iniciando proceso..."
echo ""

for DIR in "${CARPETAS[@]}"; do
    if [ ! -d "$DIR" ]; then
        echo "[ERROR] Carpeta no encontrada: $DIR"
        continue
    fi
    echo ">> Procesando: $DIR"
    find "$DIR" -type f | while read -r archivo; do
        base=$(basename "$archivo")
        if debe_ignorar "$archivo"; then
            echo "  - Ignorado: $base"
            continue
        fi
        if ! es_texto_valido "$archivo"; then
            echo "  - Ignorado: $base - binario"
            continue
        fi
        destino="$DESTINO/$base"
        contador=1
        while [[ -e "$destino" ]]; do
            extension="${base##*.}"
            nombre="${base%.*}"
            if [[ "$nombre" == "$extension" ]]; then
                destino="$DESTINO/${nombre}_$contador"
            else
                destino="$DESTINO/${nombre}_$contador.$extension"
            fi
            contador=$((contador + 1))
        done
        echo "  -> Procesando: $base"
        convertir_utf8 "$archivo" "$destino"
        echo "  [OK] Completado"
    done
done

echo ""
echo "==> PROCESO COMPLETADO"
echo ""
total=$(find "$DESTINO" -type f | wc -l | tr -d ' ')
echo "Archivos procesados: $total"
echo "Ubicacion: $DESTINO"
echo ""
echo "Verificando encodings..."
find "$DESTINO" -type f -exec file -b --mime-encoding {} \; | sort | uniq -c
echo ""
echo "Verificando BOMs..."
if find "$DESTINO" -type f -exec sh -c 'head -c 3 "{}" | od -A n -t x1 | grep -q "ef bb bf"' \; 2>/dev/null; then
    echo "[WARN] Se encontraron BOMs"
else
    echo "[OK] Sin BOMs"
fi
echo ""
echo "Verificando doble encoding..."
if find "$DESTINO" -type f -exec grep -q 'Ã±\|Ã¡\|Ã©' {} \; 2>/dev/null; then
    echo "[WARN] Posible doble encoding detectado"
else
    echo "[OK] Sin doble encoding"
fi
echo ""
echo "==> Listo para subir"
echo ""