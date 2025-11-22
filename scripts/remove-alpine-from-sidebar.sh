#!/bin/bash
# sidebar.blade.phpからAlpine.jsディレクティブを削除するスクリプト

FILE="resources/views/components/layouts/sidebar.blade.php"

# x-show="!collapsed" を data-show-when="expanded" に置換（transition削除）
sed -i 's/<span x-show="!collapsed"[^>]*x-transition[^>]*>/<span data-show-when="expanded" class="/g' "$FILE"
sed -i 's/x-transition:[^"]*"[^"]*"//g' "$FILE"

# 残りのx-show="!collapsed"を単純に置換
sed -i 's/x-show="!collapsed"/data-show-when="expanded"/g' "$FILE"

# x-show="collapsed"を置換
sed -i 's/x-show="collapsed"/data-show-when="collapsed"/g' "$FILE"

# x-dataを削除（モバイル部分）
sed -i 's/x-data="{ showSidebar: false, showGeneralMenu: true }"//g' "$FILE"
sed -i 's/x-data="{ portalExpanded: false }"//g' "$FILE"

# @clickを削除（後でdata-*属性で制御）
sed -i 's/@click\.stop="showSidebar = false"//g' "$FILE"
sed -i 's/@click="showSidebar = false"//g' "$FILE"
sed -i 's/@click="portalExpanded = !portalExpanded"/data-action="toggle-portal"/g' "$FILE"
sed -i 's/@click="showGeneralMenu = !showGeneralMenu"/data-action="toggle-general-menu-mobile"/g' "$FILE"

# :classを削除（モバイルのポータルメニュー）
sed -i 's/:class="{ '\''rotate-180'\'': portalExpanded }"/data-portal-icon/g' "$FILE"

# x-showを削除（モバイルの展開メニュー）
sed -i 's/x-show="portalExpanded"/data-portal-submenu/g' "$FILE"
sed -i 's/x-show="showGeneralMenu"/data-general-menu-mobile/g' "$FILE"
sed -i 's/x-show="!showGeneralMenu"/data-icon="general-hide-mobile"/g' "$FILE"

echo "Alpine.js directives removed from sidebar.blade.php"
