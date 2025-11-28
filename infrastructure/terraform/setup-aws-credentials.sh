#!/bin/bash

###############################################################################
# AWS認証情報設定スクリプト
#
# 使用方法:
#   source infrastructure/terraform/setup-aws-credentials.sh
#
# または:
#   . infrastructure/terraform/setup-aws-credentials.sh
###############################################################################

echo "=== AWS認証情報設定 ==="
echo ""
echo "注意: このスクリプトは 'source' または '.' で実行してください"
echo "例: source infrastructure/terraform/setup-aws-credentials.sh"
echo ""

# 既存の認証情報確認
if [ ! -z "$AWS_ACCESS_KEY_ID" ] && [ ! -z "$AWS_SECRET_ACCESS_KEY" ]; then
    echo "✅ AWS認証情報が既に設定されています"
    echo "   AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID:0:10}..."
    echo "   AWS_SECRET_ACCESS_KEY: [設定済み]"
    echo ""
    read -p "再設定しますか? (y/N): " RESET
    if [ "$RESET" != "y" ] && [ "$RESET" != "Y" ]; then
        echo "設定をスキップします"
        return 0 2>/dev/null || exit 0
    fi
fi

# AWS CLIの設定確認
if [ -f ~/.aws/credentials ]; then
    echo "ℹ️  AWS CLIの認証情報ファイルが見つかりました"
    echo "   ~/.aws/credentials"
    echo ""
    read -p "AWS CLIの認証情報を使用しますか? (Y/n): " USE_CLI
    if [ "$USE_CLI" != "n" ] && [ "$USE_CLI" != "N" ]; then
        echo "✅ AWS CLIの認証情報を使用します"
        echo "   terraform planを実行してください"
        return 0 2>/dev/null || exit 0
    fi
fi

# 手動設定
echo ""
echo "AWS認証情報を手動で設定します"
echo ""

read -p "AWS Access Key ID: " ACCESS_KEY
read -s -p "AWS Secret Access Key: " SECRET_KEY
echo ""

# 環境変数にエクスポート
export AWS_ACCESS_KEY_ID="$ACCESS_KEY"
export AWS_SECRET_ACCESS_KEY="$SECRET_KEY"

echo ""
echo "✅ AWS認証情報が設定されました"
echo "   AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID:0:10}..."
echo "   AWS_SECRET_ACCESS_KEY: [設定済み]"
echo ""
echo "次のコマンドを実行してください:"
echo "   cd infrastructure/terraform"
echo "   terraform plan"
