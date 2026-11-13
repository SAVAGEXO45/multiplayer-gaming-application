import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { compile } from '@tailwindcss/node';
import { Scanner } from '@tailwindcss/oxide';
import fs from 'node:fs';
import path from 'node:path';

function tailwindInMemoryScannerPlugin() {
    return {
        name: 'tailwindcss-windows-scanner',
        enforce: 'pre',
        async transform(code, id) {
            if (!id.endsWith('.css') || !code.includes('tailwindcss')) {
                return null;
            }

            const baseDir = path.dirname(id.split('?')[0]);
            const compiler = await compile(code, {
                base: baseDir,
                onDependency: () => {},
            });

            const scanDirs = [
                path.resolve(process.cwd(), 'resources/views'),
                path.resolve(process.cwd(), 'resources/js'),
                path.resolve(process.cwd(), 'app'),
            ];

            const filesToScan = [];
            for (const dir of scanDirs) {
                if (!fs.existsSync(dir)) continue;
                const entries = fs.readdirSync(dir, { recursive: true, withFileTypes: true });
                for (const entry of entries) {
                    if (!entry.isFile()) continue;
                    const ext = path.extname(entry.name).slice(1);
                    if (!['php', 'html', 'js', 'ts', 'vue'].includes(ext)) continue;
                    const fullPath = path.join(entry.parentPath || entry.path, entry.name);
                    filesToScan.push({
                        content: fs.readFileSync(fullPath, 'utf8'),
                        extension: ext === 'php' ? 'html' : ext,
                    });
                }
            }

            const scanner = new Scanner({});
            const candidates = scanner.scanFiles(filesToScan);
            const builtCss = compiler.build(candidates);

            return {
                code: builtCss,
                map: null,
            };
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindInMemoryScannerPlugin(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
