$ErrorActionPreference = 'Stop'
& 'C:\Users\felippe.andreata\AppData\Local\Programs\Python\Python311\python.exe' 'C:\xampp\backup-tools\backup_completo.py' --profile maquinas
if ($LASTEXITCODE -ne 0) { throw 'Backup completo falhou. Consulte %LOCALAPPDATA%\BackupsCompletos\work.' }
