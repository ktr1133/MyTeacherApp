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
    fonts-ipafont \
    fonts-ipaexfont \
    fonts-noto-cjk \
    fonts-noto-cjk-extra \
    fontconfig \
    # --- Browsershot/Puppeteer Dependencies ---
    chromium \
    chromium-driver \
    ca-certificates \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libc6 \
    libcairo2 \
    libcups2 \
    libdbus-1-3 \
    libexpat1 \
    libfontconfig1 \
    libgbm1 \
    libgcc1 \
    libglib2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    lsb-release \
    wget \
    xdg-utils \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl \
    && fc-cache -fv \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# =============================================================================
# Node.js 20.x + Puppeteer インストール（Browsershot用）
# =============================================================================
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g puppeteer@24.31.0 \
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
# フロントエンドアセットビルド
# =============================================================================
RUN echo "[Docker Build] Building frontend assets with Vite..." \
    && npm ci --production=false \
    && npm run build \
    && rm -rf node_modules \
    && npm cache clean --force \
    && echo "[Docker Build] Frontend assets built successfully"

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
    && head -5 /usr/local/bin/entrypoint.sh \
    && echo "[Docker Build] Testing entrypoint.sh can execute..." \
    && /usr/local/bin/entrypoint.sh --test || echo "[Docker Build] WARNING: entrypoint.sh test failed"

# =============================================================================
# デバッグ用スタートアップラッパー作成
# =============================================================================
# ECSがENTRYPOINTをバイパスしているか検証するため、複数の検証層を用意
RUN echo "[Docker Build] Creating startup-debug.sh wrapper..." \
    && echo '#!/bin/bash' > /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] ========================================" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] Container started at $(date)" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] Script: $0" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] Args: $@" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] PID: $$" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] PWD: $(pwd)" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] USER: $(whoami)" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] Testing entrypoint.sh existence..." >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'ls -la /usr/local/bin/entrypoint.sh >&2 || echo "[STARTUP-DEBUG] ERROR: entrypoint.sh not found!" >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'echo "[STARTUP-DEBUG] Executing entrypoint.sh..." >&2' >> /usr/local/bin/startup-debug.sh \
    && echo 'exec /usr/local/bin/entrypoint.sh "$@"' >> /usr/local/bin/startup-debug.sh \
    && chmod +x /usr/local/bin/startup-debug.sh \
    && echo "[Docker Build] startup-debug.sh created:" \
    && cat /usr/local/bin/startup-debug.sh

# =============================================================================
# 起動コマンド設定
# =============================================================================
# デバッグラッパー経由でentrypoint.shを呼び出す
# これにより、ECSが何を実行しているか（またはスキップしているか）が明確になる
ENTRYPOINT ["/usr/local/bin/startup-debug.sh"]
CMD ["apache2-foreground"]

# デバッグ: イメージのメタデータを確認可能にする
LABEL entrypoint.wrapper="startup-debug.sh" \
      entrypoint.target="entrypoint.sh" \
      debug.level="maximum"
