#!/bin/bash

# Fetch latest changes
git fetch origin

# Checkout ke branch deploy dan merge dari swoole
git checkout deploy
git merge origin/swoole --no-edit

# Push hasil merge ke remote
git push origin deploy

# Kembali ke branch swoole
git checkout swoole
