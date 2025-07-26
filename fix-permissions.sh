#!/bin/bash

# Get current user ID
CURRENT_UID=$(id -u)
CURRENT_GID=$(id -g)

# Create directories if they don't exist
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/testing
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set ownership
sudo chown -R $CURRENT_UID:$CURRENT_GID .
sudo chown -R $CURRENT_UID:$CURRENT_GID storage
sudo chown -R $CURRENT_UID:$CURRENT_GID bootstrap/cache

# Set permissions
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache

# Ensure log file exists and has correct permissions
touch storage/logs/laravel.log
sudo chmod 664 storage/logs/laravel.log
