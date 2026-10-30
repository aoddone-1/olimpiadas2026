(() => {
  'use strict';
  const snapshots = JSON.parse(document.getElementById('snapshot-data').textContent);
  const parser = new DOMParser();
  const models = new Map();
  const $ = selector => document.querySelector(selector);
  const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
  const clean = text => text.replace(/\s+/g, ' ').trim();
  const el = (tag, className, text) => { const node = document.createElement(tag); if (className) node.className = className; if (text !== undefined) node.textContent = text; return node; };
  const sportNames = {'MTB':'MTB','BASQUET 3X3':'Básquet 3×3','BASQUET 5X5':'Básquet 5×5','PADEL':'Pádel','NATACION':'Natación','FUTBOL 8':'Fútbol 8','POOL (dobles)':'Pool (dobles)','TENIS (Single)':'Tenis (single)','TENIS (DOBLES)':'Tenis (dobles)','TENIS DE MESA (Single)':'Tenis de mesa (single)','TENIS DE MESA (DOBLES)':'Tenis de mesa (dobles)'};
  const pretty = text => sportNames[text] || text.charAt(0).toLocaleUpperCase('es-AR') + text.slice(1).toLocaleLowerCase('es-AR');
  const prettyVenue = text => text.toLocaleLowerCase('es-AR').replace(/\p{L}[\p{L}\p{M}]*/gu,(word,offset)=>offset>0&&['de','del','la','el','los','las','y'].includes(word)?word:word.charAt(0).toLocaleUpperCase('es-AR')+word.slice(1));
  const pin = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
  const delegation = $('[name=delegacion]');
  const sport = $('[name=deporte]');
  const category = $('[name=categoria]');
  const search = $('#search');
  const originalCategoryLabels = new Map([...category.options].map(o => [o.value, clean(o.textContent)]));
  const params = new URLSearchParams(location.search);
  let selectedDay = params.get('dia') || '';
  let expanded = false;
  const openDisciplines = new Set();
  let activePanel = ['tab-resultados','tab-medallero'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'tab-fixture';

  function readModel(name) {
    if (models.has(name)) return models.get(name);
    const doc = parser.parseFromString(snapshots[name] || snapshots[''], 'text/html');
    const days = [];
    const items = [];
    doc.querySelectorAll('#tab-fixture .grupo-dia').forEach((group, dayIndex) => {
      const dayTitle = clean(group.querySelector('h3').textContent);
      const dayId = dayTitle.match(/\d{2}\/\d{2}/)?.[0] || String(dayIndex);
      days.push({id:dayId,title:dayTitle});
      group.querySelectorAll('.partido').forEach((card, index) => {
        const chip = card.querySelector('.deporte-chip');
        const categoryNode = card.querySelector('.categoria-linea > span[style]');
        const hourNode = card.querySelector('.hora-inicio');
        const meta = card.querySelector('.meta > span');
        const sportName = clean(chip?.textContent || 'Competencia');
        const categoryName = clean(categoryNode?.textContent || '');
        const hour = clean(hourNode?.textContent.replace(/[^0-9:]/g,'') || '');
        const venueNode = meta?.cloneNode(true);
        venueNode?.querySelector('.hora-inicio')?.remove();
        const venue = clean(venueNode?.textContent.replace(/^\s*·\s*/, '') || '');
        const badge = card.querySelector('.badge');
        const status = clean(badge?.textContent || '');
        const text = normalize(card.textContent);
        if (hourNode) hourNode.textContent = hour;
        if (meta) { meta.replaceChildren(); if(hourNode) meta.append(hourNode); meta.append(el('span','venue-name',prettyVenue(venue))); }
        if (chip) chip.textContent = pretty(sportName);
        items.push({dayId,dayTitle,html:card.outerHTML,sport:sportName,category:categoryName,hour,venue,status,badge:badge?.outerHTML || '',mass:card.classList.contains('masivo'),text,key:`${dayId}-${index}`});
      });
    });
    const model = {days,items,results:doc.querySelector('#tab-resultados')?.innerHTML || '',medals:doc.querySelector('#tab-medallero')?.innerHTML || ''};
    models.set(name,model);
    return model;
  }

  function syncCategories() {
    const selectedSport = sport.selectedOptions[0]?.textContent.trim() || '';
    [...category.options].forEach(option => {
      if (!option.value) return;
      const allowed = !sport.value || option.dataset.deporte === sport.value;
      option.hidden = !allowed;
      option.disabled = !allowed;
      const original = originalCategoryLabels.get(option.value);
      const prefix = `${selectedSport} - `;
      option.textContent = sport.value && original.startsWith(prefix) ? original.slice(prefix.length) : original;
    });
    if (category.selectedOptions[0]?.disabled) category.value = '';
    [delegation,sport,category].forEach(select => select.classList.toggle('has-filter',!!select.value));
  }

  function updateURL() {
    const url = new URL(location.href);
    for (const [key,value] of [['delegacion',delegation.value],['deporte',sport.value],['categoria',category.value],['dia',selectedDay],['q',search.value]]) {
      if(value) url.searchParams.set(key,value); else url.searchParams.delete(key);
    }
    url.hash = activePanel === 'tab-fixture' ? '' : activePanel;
    try { history.replaceState(null,'',url); } catch { /* file: previews can restrict history changes. */ }
  }

  function buildMass(items) {
    const first = items[0];
    const group = el('article','mass-group');
    const top = el('div','mass-top');
    const time = el('div','mass-time');
    time.append(el('strong','',first.hour || '—'),el('span','','HORARIO'));
    const info = el('div','mass-title');
    info.append(el('h4','',pretty(first.sport)),el('p','',`${items.length} ${items.length === 1 ? 'categoría' : 'categorías'}`));
    const venue = el('p','venue');
    venue.innerHTML = pin;
    venue.append(el('span','',prettyVenue(first.venue)));
    info.append(venue); top.append(time,info); group.append(top);
    if(first.badge) group.insertAdjacentHTML('beforeend',first.badge);
    const rows = el('div','category-rows');
    rows.innerHTML = items.map(item => item.html).join('');
    group.append(rows);
    return group;
  }

  function syncDisclosureButton() {
    const disclosures = [...$('#schedule').querySelectorAll('.discipline-group')];
    const button = $('#expand-disciplines');
    button.hidden = !disclosures.length;
    button.textContent = disclosures.some(details => details.open) ? 'Contraer disciplinas ↑' : 'Desplegar disciplinas ↓';
  }

  function buildDiscipline(items, dayId) {
    const first = items[0];
    const key = JSON.stringify([dayId,first.sport]);
    const details = el('details','discipline-group');
    details.dataset.discipline = first.sport;
    details.open = expanded || openDisciplines.has(key) || !!search.value.trim() || !!sport.value || !!category.value;
    const summary = el('summary','discipline-summary');
    const symbol = el('span','discipline-symbol');
    symbol.setAttribute('aria-hidden','true');
    symbol.innerHTML = '<svg viewBox="0 0 24 24"><path d="M5 20V5m0 0c5-4 9 4 14 0v9c-5 4-9-4-14 0"/></svg>';
    const label = el('span','discipline-label');
    const name = el('span','discipline-name',pretty(first.sport));
    const times = [...new Set(items.map(item=>item.hour).filter(Boolean))].sort();
    const timeRange = times.length > 1 ? `${times[0]}–${times[times.length-1]}` : times[0] || 'Horario a confirmar';
    const venues = [...new Set(items.map(item=>item.venue).filter(Boolean))];
    const locationText = venues.length === 1 ? prettyVenue(venues[0]) : `${venues.length} sedes`;
    label.append(name,el('span','discipline-description',`${timeRange} · ${locationText}`));
    const count = el('span','discipline-count',`${items.length} ${items.length===1?'competencia':'competencias'}`);
    const arrow = el('span','discipline-arrow');
    arrow.setAttribute('aria-hidden','true');
    arrow.innerHTML = '<svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>';
    summary.append(symbol,label,count,arrow);
    const body = el('div','discipline-body');
    const grid = el('div',items.every(item=>item.mass)?'cards mass-cards':'cards');
    const massGroups = new Map();
    items.filter(item=>item.mass).forEach(item=>{
      const massKey=JSON.stringify([item.hour,item.venue,item.status]);
      if(!massGroups.has(massKey))massGroups.set(massKey,[]);
      massGroups.get(massKey).push(item);
    });
    const rendered = new Set();
    items.forEach(item=>{
      if(item.mass){
        const massKey=JSON.stringify([item.hour,item.venue,item.status]);
        if(!rendered.has(massKey)){grid.append(buildMass(massGroups.get(massKey)));rendered.add(massKey);}
      } else grid.insertAdjacentHTML('beforeend',item.html);
    });
    body.append(grid);details.append(summary,body);
    summary.addEventListener('click',()=>{
      if(!details.open && !expanded && !search.value.trim() && !sport.value && !category.value){
        details.parentElement?.querySelectorAll(':scope > .discipline-group[open]').forEach(other=>{if(other!==details)other.open=false;});
      }
      if(details.open)openDisciplines.delete(key);else openDisciplines.add(key);
    });
    details.addEventListener('toggle',()=>{
      if(!details.isConnected)return;
      if(details.open){
        openDisciplines.add(key);
      } else openDisciplines.delete(key);
      syncDisclosureButton();
    });
    return details;
  }

  function renderDays(days) {
    const nav = $('#day-nav'); nav.replaceChildren();
    days.forEach(day => {
      const parts = day.title.split(' ');
      const button = el('button','day-button');
      button.type = 'button'; button.dataset.day = day.id;
      button.setAttribute('aria-pressed', String(day.id === selectedDay));
      button.setAttribute('aria-label',day.title);
      button.append(el('span','',parts[0].slice(0,3)),el('strong','',parts.slice(1).join(' ')));
      nav.append(button);
    });
    if (days.length) {
      const all = el('button','day-button all-days');all.type='button';all.dataset.day='all';all.innerHTML='Todos<br>los días';
      all.setAttribute('aria-pressed',String(selectedDay === 'all'));nav.append(all);
    }
  }

  function emptyPanel(targetId,title,description,icon) {
    const target = $(targetId);
    target.replaceChildren();
    const empty = el('div','empty-state');
    const symbol = el('div','empty-icon'); symbol.setAttribute('aria-hidden','true');symbol.innerHTML=icon;
    empty.append(symbol,el('h3','',title),el('p','',description));
    const button = el('button','secondary-button','Ver fixture');button.type='button';button.addEventListener('click',()=>setTab('tab-fixture'));
    empty.append(button);target.append(empty);
  }

  function render(chooseDay = false) {
    syncCategories();
    const model = readModel(delegation.value);
    const sportText = clean(sport.selectedOptions[0]?.textContent || '');
    const categoryText = originalCategoryLabels.get(category.value);
    const filtered = model.items.filter(item => (!sport.value || normalize(item.sport) === normalize(sportText)) && (!category.value || normalize(`${item.sport} - ${item.category}`) === normalize(categoryText)));
    const days = model.days.filter(day => filtered.some(item => item.dayId === day.id));
    if(!selectedDay || (chooseDay && selectedDay !== 'all' && !days.some(day => day.id === selectedDay))) selectedDay=days[0]?.id || 'all';
    renderDays(days);
    const query = normalize(search.value);
    const matches = filtered.filter(item => (selectedDay === 'all' || item.dayId === selectedDay) && (!query || item.text.includes(query)));
    const schedule = $('#schedule'); schedule.replaceChildren();
    model.days.forEach(day => {
      const records = matches.filter(item => item.dayId === day.id);if(!records.length)return;
      const group = el('section','day-group');group.setAttribute('aria-label',day.title);
      group.append(el('h3','day-heading',day.title));
      const disciplines = new Map();
      records.forEach(item=>{if(!disciplines.has(item.sport))disciplines.set(item.sport,[]);disciplines.get(item.sport).push(item);});
      const list = el('div','discipline-list');
      disciplines.forEach(items=>list.append(buildDiscipline(items,day.id)));
      group.append(list);schedule.append(group);
    });
    $('#search-empty').hidden = matches.length > 0;
    const scope = selectedDay === 'all' ? 'Todos los días' : (model.days.find(day=>day.id===selectedDay)?.title || 'Sin competencias');
    const disciplineCount = new Set(matches.map(item=>item.sport)).size;
    $('#fixture-count').textContent = `${scope} · ${disciplineCount} ${disciplineCount===1?'disciplina':'disciplinas'} · ${matches.length} ${matches.length === 1 ? 'competencia' : 'competencias'}`;
    $('#fixture-description').textContent = delegation.value ? `Agenda de ${delegation.value}. Abrí una disciplina para ver sus competencias.` : 'Abrí una disciplina para ver horarios, categorías y encuentros.';
    syncDisclosureButton();
    if (model.results.includes('class="vacio"')) {
      emptyPanel('#results-content','Todavía no hay resultados','En los datos de referencia aún no se habían cargado resultados. Podés recorrer el fixture de competencias.',$('#button-resultados svg').outerHTML);
    } else { $('#results-content').innerHTML = model.results; }
    if (model.medals.includes('class="vacio"')) {
      emptyPanel('#medals-content','Las medallas están por llegar','En los datos de referencia aún no se habían entregado medallas. El medallero conserva su lugar en el panel.',$('#button-medallero svg').outerHTML);
    } else { $('#medals-content').innerHTML = model.medals; }
    updateURL();
  }

  function setTab(id, focus = false) {
    activePanel = id;
    document.querySelectorAll('.primary-tabs button').forEach(button=>{
      const active=button.dataset.tab===id;button.setAttribute('aria-selected',String(active));button.tabIndex=active?0:-1;if(active&&focus)button.focus();
    });
    document.querySelectorAll('main .panel').forEach(panel=>panel.hidden=panel.id!==id);
    updateURL();
  }
  delegation.value = Object.hasOwn(snapshots,params.get('delegacion')) ? params.get('delegacion') : '';
  sport.value = [...sport.options].some(o=>o.value===params.get('deporte')) ? params.get('deporte') : '';
  category.value = [...category.options].some(o=>o.value===params.get('categoria')) ? params.get('categoria') : '';
  search.value=params.get('q')||'';
  $('#filters-form').addEventListener('submit',event=>event.preventDefault());
  [delegation,sport,category].forEach(select=>select.addEventListener('change',()=>render(true)));
  search.addEventListener('input',()=>render());
  $('#day-nav').addEventListener('click',event=>{const button=event.target.closest('[data-day]');if(!button)return;selectedDay=button.dataset.day;render();});
  $('#reset-filters').addEventListener('click',()=>{delegation.value='';sport.value='';category.value='';search.value='';selectedDay='';expanded=false;openDisciplines.clear();render(true);});
  $('#clear-search').addEventListener('click',()=>{delegation.value='';sport.value='';category.value='';search.value='';selectedDay='all';expanded=false;openDisciplines.clear();render(true);});
  $('#expand-disciplines').addEventListener('click',()=>{
    const disclosures=[...$('#schedule').querySelectorAll('.discipline-group')];
    expanded=!disclosures.some(details=>details.open);
    disclosures.forEach(details=>details.open=expanded);
    if(!expanded)openDisciplines.clear();
    syncDisclosureButton();
  });
  document.querySelectorAll('.primary-tabs button').forEach((button,index,buttons)=>{
    button.addEventListener('click',()=>setTab(button.dataset.tab));
    button.addEventListener('keydown',event=>{let next;if(event.key==='ArrowRight')next=(index+1)%buttons.length;else if(event.key==='ArrowLeft')next=(index-1+buttons.length)%buttons.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=buttons.length-1;else return;event.preventDefault();setTab(buttons[next].dataset.tab,true);});
  });
  render(true);setTab(activePanel);
})();
