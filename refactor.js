const fs = require('fs');
const path = require('path');

const apiSrcFolder = '/Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_version_api/src';
const frontAppFolder = '/Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_front/app';

// Find all PHP entity files
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

// Map of OldPropertyName -> newPropertyName
const propertyMap = {};

// Step 1: Detect all properties in entities that start with a capital letter
const propertyRegex = /private\s+(?:\?[a-zA-Z0-9_\\]+\s+|[a-zA-Z0-9_\\]+\s+)?\$([A-Z][a-zA-Z0-9_]*)\s*(?:=|;)/g;

for (const file of entityFiles) {
    const content = fs.readFileSync(file, 'utf8');
    let match;
    while ((match = propertyRegex.exec(content)) !== null) {
        const oldName = match[1];
        // camelCase it: lowercase only the first character
        const newName = oldName.charAt(0).toLowerCase() + oldName.slice(1);
        if (oldName !== newName) {
            propertyMap[oldName] = newName;
        }
    }
}

console.log(`Found ${Object.keys(propertyMap).length} properties to rename.`);

// Step 2: Global Replace Function
function replaceInFile(filePath, regexesBuilder) {
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;

    // Apply updates
    for (const [oldName, newName] of Object.entries(propertyMap)) {
        const regexes = regexesBuilder(oldName, newName);
        for (const regexObj of regexes) {
            content = content.replace(regexObj.regex, regexObj.replacer);
        }
    }

    if (content !== original) {
        fs.writeFileSync(filePath, content, 'utf8');
    }
}

// PHP Files replacements
const phpFiles = findFiles(apiSrcFolder, ['.php']);
for (const file of phpFiles) {
    replaceInFile(file, (oldName, newName) => {
        return [
            // Variable declarations: $OldName -> $newName
            { regex: new RegExp(`\\$${oldName}\\b`, 'g'), replacer: `$${newName}` },
            // Array keys or string literals: 'OldName' -> 'newName' or "OldName" -> "newName"
            { regex: new RegExp(`['"]${oldName}['"]`, 'g'), replacer: (match) => match[0] + newName + match[match.length - 1] },
            // Doctrine properties in specific names or annotations Name="OldName"
            { regex: new RegExp(`name:\\s*['"]${oldName}['"]`, 'g'), replacer: (match) => match.replace(oldName, newName) },
            // MappedBy / inversedBy
            { regex: new RegExp(`mappedBy:\\s*['"]${oldName}['"]`, 'g'), replacer: (match) => match.replace(oldName, newName) },
            { regex: new RegExp(`inversedBy:\\s*['"]${oldName}['"]`, 'g'), replacer: (match) => match.replace(oldName, newName) },
        ];
    });
}
console.log('PHP files updated.');

// Frontend JSX/TSX replacements
const frontFiles = findFiles(frontAppFolder, ['.ts', '.tsx', '.js', '.jsx']);
for (const file of frontFiles) {
    replaceInFile(file, (oldName, newName) => {
        return [
            // dot notation: obj.OldName -> obj.newName
            { regex: new RegExp(`\\.${oldName}\\b`, 'g'), replacer: `.${newName}` },
            // destructuring: { OldName } -> { newName }
            { regex: new RegExp(`\\b${oldName}\\b\\s*(:|}|,)`, 'g'), replacer: (m) => m.replace(oldName, newName) },
            // String literals used as keys (like in table columns or form models)
            { regex: new RegExp(`['"]${oldName}['"]`, 'g'), replacer: (m) => m.replace(oldName, newName) },
        ];
    });
}
console.log('Frontend files updated.');
