// DOM interaction checks, without a browser or layout engine.
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {resolve,dirname} from 'node:path';
import assert from 'node:assert/strict';
const {JSDOM}=await import(process.env.JSDOM_PACKAGE||'jsdom');
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=readFileSync(root+'/app/views/index/index.volt','utf8');
const seed=JSON.parse(readFileSync(root+'/database/seed.json','utf8'));
const dom=new JSDOM(html,{url:'https://cantera.example/',runScripts:'outside-only'}),w=dom.window,doc=w.document;
w.fetch=async()=>({ok:true,json:async()=>structuredClone(seed)});
w.CSS={escape:value=>String(value).replace(/[^a-zA-Z0-9_-]/g,'')};
w.HTMLDialogElement.prototype.showModal=function(){this.setAttribute('open','');};
w.HTMLDialogElement.prototype.close=function(){this.removeAttribute('open');};
w.print=()=>{};
const wait=()=>new Promise(r=>setTimeout(r,20));
const click=selector=>{const el=doc.querySelector(selector);assert.ok(el,'Missing '+selector);el.click();};
const field=(form,name,value)=>{doc.querySelector(form).elements.namedItem(name).value=String(value);};
const submit=form=>doc.querySelector(form).dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));
let checks=0;const check=(condition,label)=>{assert.ok(condition,label);checks++;console.log('PASS: '+label);};
try{
 for(const file of ['/public/js/domain.js','/demo/demo-config.js','/public/js/app.js'])w.eval(readFileSync(root+file,'utf8'));
 await wait();check(!doc.querySelector('#login').hidden,'Pantalla de acceso');
 click('[data-action="prefill"][data-role="admin"]');submit('#login-form');await wait();
 check(!doc.querySelector('#app').hidden && doc.querySelector('h1').textContent==='Cada jornada cuenta.','Inicio de administrador y panel');
 for(const view of ['teams','players','matchdays','goals','incidents','reports']){click(`[data-action="navigate"][data-view="${view}"]`);await wait();check(doc.querySelector('#main-content h1').textContent.toLowerCase().includes(view==='reports'?'reportes':{teams:'equipos',players:'jugadores',matchdays:'jornadas',goals:'goles',incidents:'incidencias'}[view]),'Navegación '+view);}
 check(doc.querySelectorAll('#report-region tbody tr').length===20,'Listado arbitral');
 check(doc.querySelector('#report-region').textContent.includes('3 suspendidos'),'Suspensiones en listado');
 click('[data-action="report-tab"][data-kind="scorers"]');await wait();check(doc.querySelector('#report-region tbody tr').textContent.includes('10'),'Goleadores en interfaz');
 click('[data-action="report-tab"][data-kind="incidents"]');await wait();field('#report-form','team_id',1);field('#report-form','player_id',4);submit('#report-form');await wait();check(doc.querySelectorAll('#report-region tbody tr').length===1,'Incidencias por jugador en interfaz');
 click('[data-action="navigate"][data-view="players"]');await wait();check(doc.querySelectorAll('#table-region tbody tr').length===10,'Paginación inicial de jugadores');
 click('[data-action="page"][data-page="2"]');check(doc.querySelector('#table-region').textContent.includes('11–20'),'Página siguiente');
 const search=doc.querySelector('#list-search');search.value='Mateo';search.dispatchEvent(new w.Event('input',{bubbles:true}));
 check(doc.querySelectorAll('#table-region tbody tr').length===1,'Búsqueda sin perder el campo');
 click('#table-region [data-action="detail"]');await wait();check(doc.querySelector('#detail-dialog').open && doc.querySelector('#detail-content').textContent.includes('Historial por jornada'),'Consulta del jugador');click('[data-action="close-detail"]');
 click('[data-action="navigate"][data-view="teams"]');click('[data-action="new"][data-entity="teams"]');
 field('#record-form','name','Equipo DOM temporal');submit('#record-form');await wait();check(doc.querySelector('#main-content').textContent.includes('Equipo DOM temporal'),'Alta desde formulario');
 const data=JSON.parse(w.localStorage.getItem('cantera-demo-v1')),id=data.teams.at(-1).id;
 click(`[data-action="edit"][data-entity="teams"][data-id="${id}"]`);field('#record-form','name','Equipo DOM editado');submit('#record-form');await wait();check(doc.querySelector('#main-content').textContent.includes('Equipo DOM editado'),'Edición desde formulario');
 click(`[data-action="delete"][data-entity="teams"][data-id="${id}"]`);click('[data-action="confirm-delete"]');await wait();check(!doc.querySelector('#main-content').textContent.includes('Equipo DOM editado'),'Confirmación de eliminación');
 click('[data-action="navigate"][data-view="goals"]');click('[data-action="new"][data-entity="goals"]');field('#record-form','player_id',2);field('#record-form','matchday_id',3);field('#record-form','goals',1);submit('#record-form');await wait();check(doc.querySelector('#record-form').textContent.includes('suspendido'),'Error de suspensión en formulario');click('[data-action="close-dialog"]');
 click('[data-action="navigate"][data-view="incidents"]');click('[data-action="new"][data-entity="incidents"]');const card=doc.querySelector('#card-type');card.value='red';card.dispatchEvent(new w.Event('change',{bubbles:true}));check(!doc.querySelector('#suspension-field').hidden && doc.querySelector('#suspension-date').required,'Tarjeta roja exige fecha en interfaz');click('[data-action="close-dialog"]');
 click('[data-action="logout"]');await wait();click('[data-action="prefill"][data-role="referee"]');submit('#login-form');await wait();check(!doc.querySelector('#main-content [data-action="new"]'),'Cuenta de consulta oculta altas');
 click('[data-action="navigate"][data-view="players"]');check(!doc.querySelector('#main-content [data-action="edit"]')&&!doc.querySelector('#main-content [data-action="delete"]'),'Cuenta de consulta oculta edición y eliminación');
 console.log(`\n${checks} verificaciones DOM correctas.`);
}finally{w.close();}
