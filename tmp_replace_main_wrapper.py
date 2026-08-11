from pathlib import Path
paths = [Path('resources/views/welcome.blade.php'), Path('resources/views/layouts/app.blade.php')]
for path in paths:
    text = path.read_text(encoding='utf-8')
    text = text.replace('class="main-wrapper"', 'class="container-fluid px-5"')
    text = text.replace('class="main-wrapper d-flex', 'class="container-fluid px-5 d-flex')
    path.write_text(text, encoding='utf-8')
    print(path, text.count('class="main-wrapper"'))
