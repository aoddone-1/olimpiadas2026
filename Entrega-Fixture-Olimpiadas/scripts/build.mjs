import {readFile,writeFile} from 'node:fs/promises';
const root = new URL('../',import.meta.url);
const out = root;
const source = await readFile(new URL('data/publico-original.html',root),'utf8');
const css = await readFile(new URL('src/maqueta.css',root),'utf8') + '\n' + await readFile(new URL('src/theme-original.css',root),'utf8');
const js = await readFile(new URL('src/maqueta.js',root),'utf8');
const snapshots=JSON.parse(await readFile(new URL('data/snapshots.json',root),'utf8'));
// Keep only public, rendered data. Never execute source-site scripts in the mockup.
for (const name of Object.keys(snapshots)) {
  const html=snapshots[name];
  const start=html.indexOf('<div id="tab-fixture"');
  const end=html.indexOf('<footer>',start);
  if(start<0||end<0)throw new Error('Missing public data in '+name);
  snapshots[name]=html.slice(start,end);
}
const labels={delegacion:'Delegación',deporte:'Deporte',categoria:'Categoría'};
const defaults={delegacion:'Todas las delegaciones',deporte:'Todos los deportes',categoria:'Todas las categorías'};
let fields='';
for(const name of ['delegacion','deporte','categoria']){
  const regex=new RegExp(`<select name="${name}"[\\s\\S]*?<\\/select>`);
  let select=source.match(regex)?.[0];
  if(!select)throw new Error('Missing filter '+name);
  select=select.replace(/ onchange="[^"]*"/g,'').replace(/ id="[^"]*"/g,'').replace('<select ',`<select id="filter-${name}" `).replace(/(<option value="">)[\s\S]*?(<\/option>)/,`$1${defaults[name]}$2`);
  fields+=`<div class="filter-field"><label for="filter-${name}">${labels[name]}</label>${select}</div>`;
}
let stats=source.match(/<div class="resumen">([\s\S]*?)<\/div>\s*<!-- Tabs -->/)[1].replace(/<b>(\d+)\/(\d+)<\/b>/,'<b>$1<small> / $2</small></b>');
const logo='data:image/png;base64,'+(await readFile(new URL('assets/logo_olimpiadas.png',out))).toString('base64');
let html=await readFile(new URL('src/maqueta.html',root),'utf8');
for(const [marker,value] of Object.entries({CSS:css,JS:js,FILTERS:fields,STATS:stats,LOGO:logo,DATA:JSON.stringify(snapshots).replace(/</g,'\\u003c')})){
  html=html.replace(`/*__${marker}__*/`,()=>value);
}
if(/\/\*__\w+__\*\//.test(html))throw new Error('Unreplaced marker');
await writeFile(new URL('maqueta-olimpiadas.html',out),html);
console.log(`Built standalone mockup: ${Buffer.byteLength(html)} bytes; ${Object.keys(snapshots).length} public views embedded.`);
