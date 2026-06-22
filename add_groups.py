import os
import re

files = [
    "Site.php",
    "Terrain.php",
    "ClientTerrain.php",
    "VenteTerrain.php",
    "DocumentVenteTerrain.php",
    "EchancierTerrain.php",
    "VersementTerrain.php",
    "DemarcheAdministrative.php",
    "EtapeDemarche.php"
]

base_dir = "/Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_version_api/src/Entity"

for filename in files:
    filepath = os.path.join(base_dir, filename)
    if not os.path.exists(filepath):
        continue
    
    with open(filepath, 'r') as f:
        content = f.read()
    
    if "Symfony\\Component\\Serializer\\Annotation\\Groups" not in content:
        # Add import after other imports
        content = re.sub(r'(use Doctrine\\ORM\\Mapping as ORM;)', r'\1\nuse Symfony\\Component\\Serializer\\Annotation\\Groups;', content)
    
    # Add #[Groups(["group1"])] above all private properties that don't already have it
    # We look for lines starting with '    private '
    lines = content.split('\n')
    new_lines = []
    for i, line in enumerate(lines):
        if line.strip().startswith('private '):
            prev_line = lines[i-1].strip() if i > 0 else ""
            if not prev_line.startswith('#[Groups'):
                new_lines.append('    #[Groups(["group1"])]')
        new_lines.append(line)
        
    with open(filepath, 'w') as f:
        f.write('\n'.join(new_lines))
    print(f"Updated {filename}")
