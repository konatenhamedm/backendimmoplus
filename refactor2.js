const fs = require('fs');
const path = require('path');

const apiSrcFolder = '/Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_version_api/src';

function findFiles(dir, exts) {
    let results = [];
    const list = fs.readdirSync(dir);
    for (const file of list) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            results = results.concat(findFiles(fullPath, exts));
        } else if (exts.some(ext => file.endsWith(ext))) {
            results.push(fullPath);
        }
    }
    return results;
}

const entityFiles = findFiles(path.join(apiSrcFolder, 'Entity'), ['.php']);
const propertyMap = {};

// It is safe to build the property map by looking at getters and setters because they have names like getLibType which corresponded to LibType.
// Actually, earlier we already know what was replaced! Let's just find `\this->([A-Z][a-zA-Z0-9_]*)\b` and lower case the first letter.
// Wait, is it safe? If there is `$this->ContratLocations`, we change to `$this->contratLocations`. Yes, properties should all be camelCase.
// Let's do it safely.

for (const file of entityFiles) {
    let content = fs.readFileSync(file, 'utf8');
    let original = content;

    content = content.replace(/\$this->([A-Z][a-zA-Z0-9_]*)\b/g, (match, p1) => {
        const newName = p1.charAt(0).toLowerCase() + p1.slice(1);
        return `$this->${newName}`;
    });

    if (content !== original) {
        fs.writeFileSync(file, content, 'utf8');
    }
}
console.log('Fixed $this->OldName occurrences.');
