#!/usr/bin/env python3
import subprocess
import sys

def convert_svg_to_png(svg_path, png_path, size):
    """cairosvgを使用してSVGをPNGに変換"""
    try:
        # cairosvgがインストールされているか確認
        subprocess.run(['python3', '-c', 'import cairosvg'], check=True, capture_output=True)
        
        # cairosvgで変換
        subprocess.run([
            'python3', '-c',
            f"""
import cairosvg
cairosvg.svg2png(
    url='{svg_path}',
    write_to='{png_path}',
    output_width={size},
    output_height={size}
)
print('✅ Converted {svg_path} to {png_path} ({size}x{size})')
"""
        ], check=True)
        return True
    except:
        return False

def convert_with_inkscape(svg_path, png_path, size):
    """Inkscapeを使用してSVGをPNGに変換"""
    try:
        subprocess.run([
            'inkscape',
            svg_path,
            '--export-type=png',
            f'--export-filename={png_path}',
            f'--export-width={size}',
            f'--export-height={size}'
        ], check=True, capture_output=True)
        print(f'✅ Converted {svg_path} to {png_path} ({size}x{size}) using Inkscape')
        return True
    except:
        return False

def convert_with_rsvg(svg_path, png_path, size):
    """rsvg-convertを使用してSVGをPNGに変換"""
    try:
        subprocess.run([
            'rsvg-convert',
            '-w', str(size),
            '-h', str(size),
            svg_path,
            '-o', png_path
        ], check=True, capture_output=True)
        print(f'✅ Converted {svg_path} to {png_path} ({size}x{size}) using rsvg-convert')
        return True
    except:
        return False

# 変換対象
conversions = [
    ('assets/icon.svg', 'assets/icon.png', 1024),
    ('assets/icon.svg', 'assets/adaptive-icon.png', 1024),
    ('assets/icon.svg', 'assets/splash-icon.png', 1024),
]

success = False
for svg, png, size in conversions:
    # 複数の方法を試す
    if convert_svg_to_png(svg, png, size):
        success = True
    elif convert_with_rsvg(svg, png, size):
        success = True
    elif convert_with_inkscape(svg, png, size):
        success = True
    else:
        print(f'⚠️ Could not convert {svg} - please install cairosvg, rsvg-convert, or inkscape')
        print('   pip install cairosvg')
        print('   or: sudo apt install librsvg2-bin')
        print('   or: sudo apt install inkscape')

if not success:
    sys.exit(1)
