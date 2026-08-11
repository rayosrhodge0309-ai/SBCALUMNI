from pathlib import Path
path = Path('resources/views/welcome.blade.php')
text = path.read_text(encoding='utf-8')
print('before', text.count('class="main-wrapper"'))
text = text.replace('class="main-wrapper"', 'class="container-fluid px-5"')
print('after replace', text.count('class="main-wrapper"'))
path.write_text(text, encoding='utf-8')
print('done')
