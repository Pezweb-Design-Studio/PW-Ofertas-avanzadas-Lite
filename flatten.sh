#!/bin/bash

DESTINO="./flattened"

# Carpetas específicas a aplanar (sin comas al final)
CARPETAS=(
    "./assets"
    "./src"
    "./templates"
)

mkdir -p "$DESTINO"

for DIR in "${CARPETAS[@]}"; do
    echo "Aplanando (copiando desde): $DIR"

    find "$DIR" -type f | while read -r archivo; do
    base=$(basename "$archivo")
    destino="$DESTINO/$base"
    contador=1

    # Si el archivo ya existe, agrega sufijo incremental
    while [[ -e "$destino" ]]; do
        extension="${base##*.}"
        nombre="${base%.*}"

        if [[ "$nombre" == "$extension" ]]; then
            destino="$DESTINO/  ${nombre}_$contador"
        else
            destino="$DESTINO/${nombre}_$contador.$extension"
        fi
            ((contador++))
        done

        cp "$archivo" "$destino"
    done
done
