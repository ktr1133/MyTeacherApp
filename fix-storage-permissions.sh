#!/bin/bash
# storage権限修正スクリプト

echo "Fixing storage permissions for development environment..."

# storageディレクトリの所有者をktrに変更（開発環境用）
sudo chown -R ktr:ktr storage/
sudo chown -R ktr:ktr bootstrap/cache/

# 書き込み権限を付与
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/

# www-dataグループに追加（Webサーバーからのアクセス用）
sudo usermod -a -G www-data ktr

echo "Done! Please logout and login again to apply group changes."
echo "Or run: newgrp www-data"
