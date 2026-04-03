---
description: Sincronización automática de cambios en Git
---

Este workflow asegura que todos los cambios realizados por la IA se registren y suban al repositorio remoto de forma inmediata y lineal.

// turbo-all
1. Verificar cambios actuales:
   `git status`

2. Si hay cambios, añadirlos y hacer commit:
   `git add .`
   `git commit -m "Auto-sync: [Breve descripción de los cambios realizados]"`

3. Sincronizar con el remoto (Pull para evitar conflictos y Push para actualizar):
   `git pull origin main --rebase`
   `git push origin main`

4. Informar al usuario sobre el éxito de la sincronización.
