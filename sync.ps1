# GIT SYNC AUTOMATIC - VS System ERP
# Uso: .\sync.ps1 "Mensaje del commit"

param (
    [string]$message = "Auto-sync update"
)

Write-Host "Iniciando sincronización automática..." -ForegroundColor Cyan

# 1. Agregar cambios
git add .
Write-Host "Archivos agregados." -ForegroundColor Gray

# 2. Commit
git commit -m "Auto-sync: $message"
Write-Host "Commit realizado: $message" -ForegroundColor Green

# 3. Pull con rebase para mantener historial lineal
Write-Host "Sincronizando con el servidor remoto (Pull)..." -ForegroundColor Gray
git pull origin main --rebase

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error durante el pull. Por favor, resuelve los conflictos manualmente." -ForegroundColor Red
    exit $LASTEXITCODE
}

# 4. Push
Write-Host "Subiendo cambios (Push)..." -ForegroundColor Gray
git push origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host "Sincronización completada con éxito." -ForegroundColor Green
} else {
    Write-Host "Error al subir los cambios." -ForegroundColor Red
}
