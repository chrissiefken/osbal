#!/usr/bin/env bash
# OSbal Integration & Optionality Test Runner

# Move to the project root directory
cd "$(dirname "$0")/.." || exit 1

echo "========================================================="
echo "    Executing OSbal Automated Integration Tests          "
echo "========================================================="

# Execute PHP assertions script
php tests/verify-optionality.php
exitCode=$?

if [ $exitCode -eq 0 ]; then
    echo "SUCCESS: All core functions and optionality checks passed."
    exit 0
else
    echo "ERROR: Integration tests failed validation check."
    exit 1
fi
