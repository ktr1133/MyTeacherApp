# =============================================================================
# MyTeacher Production Dockerfile
# =============================================================================
# Base: PHP 8.3 with Apache
# Purpose: Laravel 12 application deployment
# =============================================================================

FROM php:8.3-apache

# Build引数
ARG BUILD_DATE
ARG VCS_REF
LABEL org.opencontainers.image.created=$BUILD_DATE \
      org.opencontainers.image.revision=$VCS_REF \
      org.opencontainers.image.title="MyTeacher App" \
      org.opencontainers.image.description="Laravel 12 AI Task Management Platform"

# =============================================================================
# システムパッケージインストール
# =============================================================================
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# =============================================================================
# Composerインストール
# =============================================================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# =============================================================================
# Apache設定
# =============================================================================
RUN a2enmod rewrite headers

# DocumentRootを/var/www/html/publicに設定
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHPエラーログを標準エラー出力にリダイレクト
RUN echo "error_log = /proc/self/fd/2" >> /usr/local/etc/php/php.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/php.ini \
    && echo "[Docker Build] PHP error logging configured to stderr"

# =============================================================================
# 作業ディレクトリ設定
# =============================================================================
WORKDIR /var/www/html

# =============================================================================
# Laravelアプリケーションコピー
# =============================================================================
# Composer依存関係ファイルを先にコピー（キャッシュ効率化）
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# アプリケーション全体をコピー
COPY . .

# =============================================================================
# storage構造の作成（.dockerignoreで除外されたディレクトリを復元）
# =============================================================================
RUN mkdir -p storage/logs \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/testing \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/app/public

# =============================================================================
# Laravel最適化
# =============================================================================
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# =============================================================================
# パーミッション設定
# =============================================================================
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# =============================================================================
# ヘルスチェック
# =============================================================================
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# =============================================================================
# ポート公開
# =============================================================================
EXPOSE 80

# =============================================================================
# Entrypoint設定（起動時の初期化処理）
# =============================================================================
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && echo "[Docker Build] entrypoint.sh copied and made executable" \
    && ls -la /usr/local/bin/entrypoint.sh \
    && head -5 /usr/local/bin/entrypoint.sh

# =============================================================================
# 起動コマンド検証
# =============================================================================
# デバッグ: ENTRYPOINT/CMDが正しく設定されているか確認
RUN echo "[Docker Build] Verifying ENTRYPOINT will be used..." \
    && echo "[Docker Build] ENTRYPOINT: /usr/local/bin/entrypoint.sh" \
    && echo "[Docker Build] CMD: apache2-foreground"

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]

# デバッグ: イメージのメタデータを確認可能にする
LABEL entrypoint.test="should_see_entrypoint_logs_in_cloudwatch"
