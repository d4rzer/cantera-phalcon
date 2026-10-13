'use strict';
// Shared display and demo rules. The real PHP server always validates again.
window.CanteraDomain = (() => {
  const name = p => `${p.first_name} ${p.last_name}`;
  const age = (birth, reference) => {
    const [y,m,d] = birth.split('-').map(Number), [ry,rm,rd] = reference.split('-').map(Number);
    return ry-y-((rm<m || (rm===m && rd<d)) ? 1 : 0);
  };
  const validDate = value => /^\d{4}-\d{2}-\d{2}$/.test(value||'') && !Number.isNaN(Date.parse(value)) && new Date(value+'T00:00:00Z').toISOString().slice(0,10)===value;
  const suspended = (s,id,date) => s.incidents.some(i=>+i.player_id===+id && i.card_type==='red' && i.suspension_date===date);
  const total = (s,id,matchday=0) => s.goals.filter(g=>+g.player_id===+id && (!+matchday || +g.matchday_id===+matchday)).reduce((n,g)=>n+(+g.goals),0);
  const decorate = (s,p,settings) => ({...p,name:name(p),team_name:s.teams.find(t=>+t.id===+p.team_id)?.name||'',age:age(p.birth_date,settings.age_reference_date)});
  const report = (s,kind,f,settings) => {
    let players=s.players.filter(p=>!+f.team_id || +p.team_id===+f.team_id);
    if(kind==='incidents') {
      if(f.date_from && !validDate(f.date_from) || f.date_to && !validDate(f.date_to)) throw new Error('Fecha inválida.');
      if(f.date_from && f.date_to && f.date_from>f.date_to) throw new Error('La fecha inicial debe ser anterior a la final.');
      return s.incidents.filter(i=>players.some(p=>+p.id===+i.player_id) && (!+f.player_id || +i.player_id===+f.player_id) && (!f.card_type || f.card_type===i.card_type) && (!f.date_from || i.incident_date>=f.date_from) && (!f.date_to || i.incident_date<=f.date_to)).map(i=>{
        const p=decorate(s,players.find(p=>+p.id===+i.player_id),settings);
        return {...i,player_name:p.name,team_name:p.team_name};
      }).sort((a,b)=>b.incident_date.localeCompare(a.incident_date)||(+b.id-+a.id));
    }
    const date=s.matchdays.find(m=>+m.id===+f.matchday_id)?.game_date;
    if(kind==='referee' && !date) throw new Error('Seleccione una jornada.');
    const rows=players.map(p=>({...decorate(s,p,settings),...(kind==='referee'?{suspended:suspended(s,p.id,date)}:{total_goals:total(s,p.id,f.matchday_id)})}));
    return rows.sort((a,b)=>kind==='scorers' ? b.total_goals-a.total_goals || a.name.localeCompare(b.name) : a.team_name.localeCompare(b.team_name)||a.name.localeCompare(b.name));
  };
  const player = (s,id,settings) => {
    const p=s.players.find(p=>+p.id===+id);
    if(!p) throw new Error('Jugador no encontrado.');
    return {...decorate(s,p,settings),total_goals:total(s,id),incidents:s.incidents.filter(i=>+i.player_id===+id),history:s.matchdays.map(m=>({...m,goals:total(s,id,m.id),suspended:suspended(s,id,m.game_date)}))};
  };
  const validate = (s,entity,input,id,settings) => {
    const d={...input}, errors={};
    const required=(keys)=>keys.forEach(k=>{if(d[k]===undefined || d[k]===null || String(d[k]).trim()==='') errors[k]='Este campo es obligatorio.';});
    const length=(k,max)=>{if(String(d[k]||'').length>max) errors[k]=`Máximo ${max} caracteres.`;};
    const integer=(k,min,max)=>{if(!/^-?\d+$/.test(String(d[k])) || +d[k]<min || +d[k]>max) errors[k]=`Ingrese un entero entre ${min} y ${max}.`;else d[k]=+d[k];};
    const date=k=>{if(!validDate(d[k])) errors[k]='Fecha inválida.';};
    const unique=k=>{if(s[entity].some(r=>+r.id!==+id && String(r[k]).toLocaleLowerCase()===String(d[k]).toLocaleLowerCase()))errors[k]='Este valor ya está registrado.';};
    if(entity==='teams'){required(['name']);length('name',100);unique('name');integer('active',0,1);}
    if(entity==='players'){
      required(['first_name','last_name','birth_date','team_id']);
      ['first_name','last_name'].forEach(k=>{length(k,100);if(!/^[\p{L}\p{M} ]+$/u.test(d[k]||''))errors[k]='Solo letras y espacios.';});
      date('birth_date');const years=age(d.birth_date||'0-0-0',settings.age_reference_date);
      if(!Number.isFinite(years)||years<settings.age_min||years>settings.age_max)errors.birth_date=`Edad permitida: ${settings.age_min} a ${settings.age_max} años.`;
      integer('team_id',1,2147483647);length('position',50);
      const team=s.teams.find(t=>+t.id===+d.team_id), old=s.players.find(p=>+p.id===+id);
      if(!team || !+team.active && +old?.team_id!==+team.id)errors.team_id='Seleccione un equipo activo.';
      if(old && +old.team_id!==+d.team_id && (s.goals.some(g=>+g.player_id===+id)||s.incidents.some(i=>+i.player_id===+id)))errors.team_id='Un jugador con historial debe conservar su equipo.';
      if(d.shirt_number!==''&&d.shirt_number!=null)integer('shirt_number',1,99);else d.shirt_number=null;
      d.photo_path=d.photo_path||old?.photo_path||'images/player-placeholder.svg';
    }
    if(entity==='matchdays'){
      required(['number','game_date']);integer('number',1,100);date('game_date');unique('number');unique('game_date');
      const old=s.matchdays.find(m=>+m.id===+id);
      if(old && old.game_date!==d.game_date && s.incidents.some(i=>i.suspension_date===old.game_date))errors.game_date='Ajuste primero las suspensiones asociadas.';
      if(s.goals.some(g=>+g.matchday_id===+id && suspended(s,g.player_id,d.game_date)))errors.game_date='Fecha incompatible con goles y suspensiones.';
    }
    if(entity==='goals'){
      required(['player_id','matchday_id','goals']);integer('goals',0,20);integer('player_id',1,2147483647);integer('matchday_id',1,2147483647);
      const m=s.matchdays.find(m=>+m.id===+d.matchday_id);
      if(!s.players.some(p=>+p.id===+d.player_id))errors.player_id='Jugador no encontrado.';
      if(!m)errors.matchday_id='Jornada no encontrada.';
      if(m && suspended(s,d.player_id,m.game_date))errors.player_id='El jugador está suspendido para esa jornada.';
      if(s.goals.some(g=>+g.id!==+id && +g.player_id===+d.player_id && +g.matchday_id===+d.matchday_id))errors.matchday_id='Edite el registro de goles existente.';
    }
    if(entity==='incidents'){
      required(['player_id','description','card_type','incident_date']);integer('player_id',1,2147483647);length('description',500);date('incident_date');
      if(!s.players.some(p=>+p.id===+d.player_id))errors.player_id='Jugador no encontrado.';
      if(!['red','yellow'].includes(d.card_type))errors.card_type='Tarjeta inválida.';
      if(d.card_type==='yellow')d.suspension_date=null;
      if(d.card_type==='red'){
        required(['suspension_date']);date('suspension_date');
        if(d.suspension_date<d.incident_date)errors.suspension_date='La suspensión no puede ser anterior a la incidencia.';
        if(s.goals.some(g=>+g.player_id===+d.player_id && s.matchdays.find(m=>+m.id===+g.matchday_id)?.game_date===d.suspension_date))errors.suspension_date='Corrija primero los goles registrados en esa fecha.';
      }
    }
    if(Object.keys(errors).length){const e=new Error('Revise los campos del formulario.');e.fields=errors;throw e;}
    return d;
  };
  const canDelete=(s,entity,id)=>{
    if(entity==='teams')return !s.players.some(p=>+p.team_id===+id);
    if(entity==='players')return !s.goals.some(g=>+g.player_id===+id) && !s.incidents.some(i=>+i.player_id===+id);
    if(entity==='matchdays')return !s.goals.some(g=>+g.matchday_id===+id) && !s.incidents.some(i=>i.suspension_date===s.matchdays.find(m=>+m.id===+id)?.game_date);
    return true;
  };
  return {name,age,suspended,total,decorate,report,player,validate,canDelete,validDate};
})();
