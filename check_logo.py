from PIL import Image
try:
    img = Image.open('assets/images/logo.png').convert('RGBA')
    width, height = img.size
    print(f"Size: {width}x{height}")
    print(f"Top-left pixel: {img.getpixel((0,0))}")
    print(f"Center pixel: {img.getpixel((width//2, height//2))}")
except Exception as e:
    print(e)
