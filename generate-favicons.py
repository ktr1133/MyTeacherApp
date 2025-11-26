#!/usr/bin/env python3
"""
Famicoロゴから各サイズのfaviconを生成するスクリプト
"""
from PIL import Image
import os

# 入力画像
source = "public/images/famico-logo-20251123041515.png"
output_dir = "public"

# 生成するサイズ
sizes = {
    "favicon-16x16.png": (16, 16),
    "favicon-32x32.png": (32, 32),
    "apple-touch-icon.png": (180, 180),
    "android-chrome-192x192.png": (192, 192),
    "android-chrome-512x512.png": (512, 512),
}

# 元画像を開く
print(f"元画像を読み込み: {source}")
img = Image.open(source)

# 各サイズに変換
for filename, size in sizes.items():
    output_path = os.path.join(output_dir, filename)
    resized = img.resize(size, Image.Resampling.LANCZOS)
    resized.save(output_path, "PNG")
    print(f"✓ {filename} ({size[0]}x{size[1]})")

# favicon.icoも生成（複数サイズを含む）
favicon_ico_path = os.path.join(output_dir, "favicon.ico")
img_16 = img.resize((16, 16), Image.Resampling.LANCZOS)
img_32 = img.resize((32, 32), Image.Resampling.LANCZOS)
img_48 = img.resize((48, 48), Image.Resampling.LANCZOS)
img_16.save(favicon_ico_path, format='ICO', sizes=[(16, 16), (32, 32), (48, 48)])
print(f"✓ favicon.ico (16x16, 32x32, 48x48)")

# SVG版も作成（簡易版 - 実際にはベクター版が必要）
print(f"\n✓ favicon生成完了！")
print(f"  出力先: {output_dir}/")
