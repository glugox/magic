// scripts/generate-ui-index.mjs
import { readdirSync, statSync, writeFileSync } from "fs";
import { resolve, extname, join } from "path";

const uiDir = resolve("resources/js/components/ui");
const outFile = resolve(uiDir, "index.ts");

const entries = readdirSync(uiDir);

const exports = [];

for (const entry of entries) {
    const fullPath = join(uiDir, entry);
    const stat = statSync(fullPath);

    if (stat.isFile() && extname(entry) === ".vue") {
        // single file component
        exports.push(`export { default as ${pascalCase(entry)} } from "./${entry}"`);
    } else if (stat.isDirectory()) {
        // subfolder (assume it has its own index.ts)
        exports.push(`export * from "./${entry}"`);
    }
}

writeFileSync(outFile, exports.join("\n") + "\n");
console.log(`✅ Generated ${outFile}`);

function pascalCase(filename) {
    return filename
        .replace(/\.vue$/, "")
        .replace(/(^\w|-\w)/g, (s) => s.replace("-", "").toUpperCase());
}
