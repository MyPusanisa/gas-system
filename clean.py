import re

with open('/tmp/gas_system.sql', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# ?? DEFAULT curdate() ??? DEFAULT CURRENT_DATE ???????
content = re.sub(r'DEFAULT\s+curdate\(\)', '', content, flags=re.IGNORECASE)
content = re.sub(r'DEFAULT\s+CURRENT_DATE', '', content, flags=re.IGNORECASE)

# ?? Non-breaking space ??????????????????
content = content.replace('\xa0', ' ')

with open('/tmp/gas_system_clean.sql', 'w', encoding='utf-8') as f:
    f.write(content)

print("Cleaned SQL file successfully!")
