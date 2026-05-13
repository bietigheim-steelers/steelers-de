const { spawnSync } = require('node:child_process');
const path = require('node:path');

const tailwindBinary = path.join(
  process.cwd(),
  'node_modules',
  '.bin',
  process.platform === 'win32' ? 'tailwindcss.cmd' : 'tailwindcss',
);

const result = spawnSync(tailwindBinary, process.argv.slice(2), {
  shell: process.platform === 'win32',
  encoding: 'utf8',
});

if (result.stdout) {
  process.stdout.write(result.stdout);
}

if (result.stderr) {
  process.stderr.write(result.stderr);
}

const output = `${result.stdout ?? ''}\n${result.stderr ?? ''}`;
const hasCssSyntaxError = output.includes('CssSyntaxError:');
const hasExitError = result.status !== 0 || result.error;

if (hasExitError || hasCssSyntaxError) {
  process.exit(result.status ?? 1);
}
