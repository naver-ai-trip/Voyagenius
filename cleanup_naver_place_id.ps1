# Naver Place ID Removal - Automated Cleanup Script
# Run this script from the project root directory

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NAVER Place ID Removal - Cleanup Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Replace test files
Write-Host "Step 1: Replacing test files..." -ForegroundColor Yellow

if (Test-Path "tests\Feature\PlaceSearchTest_NEW.php") {
    Remove-Item "tests\Feature\PlaceSearchTest.php" -Force -ErrorAction SilentlyContinue
    Rename-Item "tests\Feature\PlaceSearchTest_NEW.php" "PlaceSearchTest.php"
    Write-Host "✓ PlaceSearchTest.php replaced" -ForegroundColor Green
} else {
    Write-Host "✗ PlaceSearchTest_NEW.php not found" -ForegroundColor Red
}

if (Test-Path "tests\Unit\PlaceModelTest_NEW.php") {
    Remove-Item "tests\Unit\PlaceModelTest.php" -Force -ErrorAction SilentlyContinue
    Rename-Item "tests\Unit\PlaceModelTest_NEW.php" "PlaceModelTest.php"
    Write-Host "✓ PlaceModelTest.php replaced" -ForegroundColor Green
} else {
    Write-Host "✗ PlaceModelTest_NEW.php not found" -ForegroundColor Red
}

Write-Host ""

# Step 2: Run migrations
Write-Host "Step 2: Running database migrations..." -ForegroundColor Yellow
Write-Host "  - Removing naver_place_id column and adding lat/lng unique constraint..." -ForegroundColor Gray
Write-Host "  - Making address column nullable..." -ForegroundColor Gray
Write-Host "  - Fixing itinerary_items time columns (datetime -> time)..." -ForegroundColor Gray
php artisan migrate --force

Write-Host ""

# Step 3: Run tests
Write-Host "Step 3: Running test suite..." -ForegroundColor Yellow
php artisan test

Write-Host ""

# Step 4: Regenerate Swagger docs
Write-Host "Step 4: Regenerating Swagger documentation..." -ForegroundColor Yellow
php artisan l5-swagger:generate

Write-Host ""

# Step 5: Search for remaining references
Write-Host "Step 5: Checking for remaining naver_place_id references..." -ForegroundColor Yellow
$references = Get-ChildItem -Path app,tests,database -Recurse -Include *.php | 
    Select-String "naver_place_id" -SimpleMatch | 
    Where-Object { $_.Path -notlike "*migrations*" }

if ($references) {
    Write-Host "⚠ Found remaining naver_place_id references:" -ForegroundColor Yellow
    $references | ForEach-Object { Write-Host "  - $($_.Path):$($_.LineNumber)" -ForegroundColor Yellow }
} else {
    Write-Host "✓ No remaining naver_place_id references found (except migrations)" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Cleanup Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Review test results above" -ForegroundColor White
Write-Host "2. Test place creation through Filament admin" -ForegroundColor White
Write-Host "3. Test /api/places endpoints in Swagger UI" -ForegroundColor White
Write-Host "4. Verify duplicate coordinate detection works" -ForegroundColor White
Write-Host ""
Write-Host "For details, see: NAVER_PLACE_ID_REMOVAL_MANUAL_STEPS.md" -ForegroundColor Cyan
