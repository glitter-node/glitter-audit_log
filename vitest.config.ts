import fs from 'fs';
import path from 'path';
import { defineConfig } from 'vitest/config';

function findProjectRoot(startDir: string): string {
    let directory = startDir;
    while (directory !== path.dirname(directory)) {
        if (fs.existsSync(path.join(directory, 'artisan'))) return directory;
        directory = path.dirname(directory);
    }

    return path.resolve(startDir, '../..');
}

const projectRoot = findProjectRoot(__dirname);

export default defineConfig({
    root: __dirname,
    cacheDir: path.resolve(projectRoot, 'node_modules/.vite/glitter-audit-log'),
    test: {
        globals: true,
        environment: 'jsdom',
        include: ['resources/js/**/*.{test,spec}.{ts,tsx}'],
        exclude: ['node_modules/', 'dist/'],
    },
    resolve: {
        alias: {
            '@core': path.resolve(projectRoot, 'resources/js/core'),
        },
    },
});
