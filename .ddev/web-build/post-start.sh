#!/bin/bash

## #ddev-generated
## Description: Post-start hook for Laravel setup
## Usage: This runs automatically after ddev start

set -eu -o pipefail

# Run migrations
php artisan migrate --force

echo "✅ Laravel migrations completed!"
