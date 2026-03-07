import { build, context } from 'esbuild';

const isWatch = process.argv.includes('--watch');

const buildOptions = {
  entryPoints: ['assets/components/mxeditorjs/js/src/mxeditorjs.ts'],
  bundle: true,
  outfile: 'assets/components/mxeditorjs/js/mxeditorjs.js',
  format: 'iife',
  globalName: 'MxEditorJs',
  target: ['es2020'],
  minify: !isWatch,
  sourcemap: isWatch,
};

if (isWatch) {
  const ctx = await context(buildOptions);
  await ctx.watch();
  console.log('Watching for changes...');
} else {
  await build(buildOptions);
  console.log('Build complete.');
}
