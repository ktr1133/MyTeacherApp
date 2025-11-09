
# ImageMagickを使用してSVGからPNGを生成
convert -background none -resize 16x16 public/favicon.svg public/favicon-16x16.png
convert -background none -resize 32x32 public/favicon.svg public/favicon-32x32.png
convert -background none -resize 192x192 public/favicon.svg public/android-chrome-192x192.png
convert -background none -resize 512x512 public/favicon.svg public/android-chrome-512x512.png
convert -background none -resize 180x180 public/favicon.svg public/apple-touch-icon.png

# ICO形式も生成（複数サイズを含む）
convert public/favicon-16x16.png public/favicon-32x32.png public/favicon.ico

echo "Favicons generated successfully!"