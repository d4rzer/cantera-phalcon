// Run against a local test instance: node tests/http-smoke.mjs http://localhost:8080
// Creates temporary records and removes them before finishing.
import assert from 'node:assert/strict';
const origin=process.argv[2]||'http://localhost:8080';
let csrf='',checks=0;const cookies=new Map();
const test=(condition,name)=>{assert.ok(condition,name);checks++;console.log('PASS: '+name);};
async function request(path,{method='GET',body,token=true}={}){
 const headers={Cookie:[...cookies].map(([k,v])=>k+'='+v).join('; ')};
 if(method!=='GET'&&token)headers['X-CSRF-Token']=csrf;
 if(body && !(body instanceof FormData)){headers['Content-Type']='application/json';body=JSON.stringify(body);}
 const response=await fetch(origin+path,{method,headers,body});
 for(const cookie of response.headers.getSetCookie()){const [name,...value]=cookie.split(';')[0].split('=');cookies.set(name,value.join('='));}
 const text=await response.text();let data;try{data=JSON.parse(text);}catch{data=text;}
 if(data.csrf)csrf=data.csrf;
 return {status:response.status,data,headers:response.headers};
}
const created=[];
async function create(entity,body){const result=await request('/api/'+entity,{method:'POST',body});test(result.status===201,'Alta de '+entity+' ('+result.status+': '+(result.data.error||'correcto')+')');created.push([entity,result.data.record.id]);return result.data.record;}
try{
 let r=await request('/api/health');test(r.status===200 && r.data.framework.startsWith('Phalcon 5.'),'Servidor Phalcon y conexión de datos');
 r=await request('/');test(r.status===200 && r.data.includes('Cantera · Torneo infantil') && r.headers.get('content-security-policy'),'Página Volt y cabeceras de seguridad');
 r=await request('/api/session');test(r.status===200 && r.data.user===null && !!csrf,'Sesión inicial y token CSRF');
 r=await request('/api/state');test(r.status===401,'Datos protegidos sin sesión');
 r=await request('/api/login',{method:'POST',body:{email:'admin@cantera.local',password:'incorrecta'}});test(r.status===401,'Credenciales incorrectas');
 r=await request('/api/login',{method:'POST',body:{email:'arbitro@cantera.local',password:'Arbitro2026!'}});test(r.status===200 && r.data.user.role==='referee','Inicio de sesión del árbitro');
 r=await request('/api/state');test(r.status===200 && r.data.players.length===20,'Consulta de datos por árbitro');
 r=await request('/api/teams',{method:'POST',body:{name:'No autorizado',active:1}});test(r.status===403,'Árbitro no puede modificar datos');
 r=await request('/api/logout',{method:'POST',body:{}});test(r.status===200,'Cierre de sesión');
 r=await request('/api/login',{method:'POST',body:{email:'admin@cantera.local',password:'Cantera2026!'}});test(r.status===200 && r.data.user.role==='admin','Inicio de sesión del administrador');
 r=await request('/api/teams',{method:'POST',token:false,body:{name:'Sin CSRF',active:1}});test(r.status===419,'Mutaciones requieren CSRF');
 r=await request('/api/players?page=2');test(r.status===200 && r.data.items.length===10 && r.data.page===2,'Paginación Phalcon');
 r=await request('/api/players?q=Mateo&team_id=1');test(r.status===200 && r.data.total===1,'Búsqueda y filtro en servidor');
 r=await request('/api/reports/referee?matchday_id=3');test(r.status===200 && r.data.rows.filter(p=>p.suspended).length===3,'Reporte arbitral con suspensiones');
 r=await request('/api/reports/scorers');test(r.status===200 && r.data.rows[0].total_goals===10,'Reporte de goleadores');
 r=await request('/api/reports/incidents?team_id=1&card_type=red');test(r.status===200 && r.data.rows.length===1,'Reporte filtrado de incidencias');
 r=await request('/api/reports/incidents?team_id=1&player_id=4');test(r.status===200 && r.data.rows.length===1 && +r.data.rows[0].player_id===4,'Reporte de un jugador del equipo');
 r=await request('/api/player/2');test(r.status===200 && r.data.history.length===5 && r.data.incidents.length===1,'Consulta completa del jugador');
 r=await request('/api/goals',{method:'POST',body:{player_id:2,matchday_id:3,goals:1}});test(r.status===409,'API bloquea goles de suspendido');
 r=await request('/api/teams/1',{method:'DELETE'});test(r.status===409,'API protege equipo con jugadores');
 const team=await create('teams',{name:'Equipo HTTP temporal',active:1});
 const file=new FormData();for(const [key,value]of Object.entries({first_name:'Óscar',last_name:'Álvarez',birth_date:'2015-04-02',team_id:team.id,shirt_number:11,position:'Defensa'}))file.set(key,String(value));
 const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAC0lEQVQImWNgQAYAAA4AAbGa6gYAAAAASUVORK5CYII=','base64');
 file.set('photo',new Blob([png],{type:'image/png'}),'prueba.png');
 const p=await create('players',file);test(/^uploads\/players\/[a-f0-9]{32}\.jpg$/.test(p.photo_path),'Fotografía validada y convertida a JPEG');
 r=await request('/'+p.photo_path);test(r.status===200 && r.headers.get('content-type').startsWith('image/jpeg'),'Fotografía disponible por URL');
 const day=await create('matchdays',{number:99,game_date:'2026-12-26'});
 const goal=await create('goals',{player_id:p.id,matchday_id:day.id,goals:2});
 r=await request('/api/goals/'+goal.id,{method:'POST',body:{player_id:p.id,matchday_id:day.id,goals:3}});test(r.status===200,'Edición HTTP de goles');
 const inc=await create('incidents',{player_id:p.id,description:'Amonestación de prueba HTTP',card_type:'yellow',incident_date:'2026-12-26'});
 r=await request('/api/player/'+p.id);test(r.status===200 && r.data.total_goals===3 && r.data.incidents.length===1,'Altas y edición reflejadas en ficha');
 const invalid=new FormData();for(const [key,value]of Object.entries({first_name:'Mala',last_name:'Imagen',birth_date:'2015-04-02',team_id:team.id}))invalid.set(key,String(value));
 invalid.set('photo',new Blob(['<script>malicioso</script>'],{type:'image/png'}),'imagen.png');
 r=await request('/api/players',{method:'POST',body:invalid});test(r.status===422,'Se rechaza archivo con MIME falsificado');
 r=await request('/api/no-existe');test(r.status===404,'Ruta inexistente devuelve 404');
}finally{
 for(const [entity,id] of [...created].sort((a,b)=>['goals','incidents','players','matchdays','teams'].indexOf(a[0])-['goals','incidents','players','matchdays','teams'].indexOf(b[0]))){const r=await request('/api/'+entity+'/'+id,{method:'DELETE'});test(r.status===200,'Limpieza HTTP de '+entity);}
}
console.log(`\n${checks} verificaciones HTTP correctas.`);
