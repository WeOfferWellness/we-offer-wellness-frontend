import { build } from 'vite';
import { mkdtemp, readFile, readdir, cp, rename, chmod, rm, mkdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { resolve, join } from 'node:path';
import { randomUUID } from 'node:crypto';

// Compile away from the live directory. Existing pages keep their hashed assets
// throughout the build; publish the new manifest only after all files are ready.
const staging = await mkdtemp(join(tmpdir(), 'wow-frontend-build-'));
const live = resolve('public/build');
try {
    await build({ build: { outDir: staging, emptyOutDir: true } });
    const manifest = JSON.parse(await readFile(join(staging, 'manifest.json'), 'utf8'));
    for (const entry of Object.values(manifest)) {
        for (const file of [entry.file, ...(entry.css || []), ...(entry.assets || [])].filter(Boolean)) {
            await readFile(join(staging, file));
        }
    }
    await mkdir(live, { recursive: true });
    for (const entry of await readdir(staging)) {
        if (entry === 'manifest.json') continue;
        await cp(join(staging, entry), join(live, entry), { recursive: true });
    }
    const manifestTemp = join(live, `.manifest-${randomUUID()}.json`);
    await cp(join(staging, 'manifest.json'), manifestTemp);
    await chmod(manifestTemp, 0o644);
    await rename(manifestTemp, join(live, 'manifest.json'));
    console.log('Published frontend assets; live manifest remained available throughout the build.');
} finally {
    // Only our temporary compilation directory is removed, never live assets.
    await rm(staging, { recursive: true, force: true });
}
