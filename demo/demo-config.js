'use strict';
window.CANTERA_DEMO=true;
window.CanteraTransport=(()=>{
 const key='cantera-demo-v1',userKey='cantera-demo-user';
 const settings={age_min:5,age_max:14,age_reference_date:'2026-10-03',demo_accounts:true};
 const initial=(async()=>{
   if(!localStorage.getItem(key)){const response=await fetch('/seed.json');if(!response.ok)throw new Error('No se pudieron cargar los datos de ejemplo.');localStorage.setItem(key,JSON.stringify(await response.json()));}
 })();
 const read=()=>JSON.parse(localStorage.getItem(key));
 const user=()=>JSON.parse(sessionStorage.getItem(userKey)||'null');
 const clone=data=>JSON.parse(JSON.stringify(data));
 async function photoData(file){
   if(file.size>2*1024*1024||!['image/jpeg','image/png'].includes(file.type))throw new Error('Use una fotografía JPG o PNG de máximo 2 MB.');
   const source=await createImageBitmap(file);
   if(source.width*source.height>25000000){source.close();throw new Error('La fotografía excede 25 megapíxeles.');}
   const ratio=Math.min(1,640/Math.max(source.width,source.height)),canvas=document.createElement('canvas');
   canvas.width=Math.max(1,Math.round(source.width*ratio));canvas.height=Math.max(1,Math.round(source.height*ratio));
   const context=canvas.getContext('2d');context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);context.drawImage(source,0,0,canvas.width,canvas.height);source.close();return canvas.toDataURL('image/jpeg',.8);
 }
 return async(path,options={})=>{
   await initial;const url=new URL(path,location.origin),route=url.pathname,method=options.method||'GET';
   let body=options.body||{};
   if(body instanceof FormData){body=Object.fromEntries(body);if(body.photo instanceof File&&body.photo.size)body.photo_path=await photoData(body.photo);delete body.photo;}
   if(route==='/api/session')return {user:user(),csrf:'demo',settings};
   if(route==='/api/login'){
     const admin=body.email==='admin@cantera.local'&&body.password==='Cantera2026!';
     const referee=body.email==='arbitro@cantera.local'&&body.password==='Arbitro2026!';
     if(!admin&&!referee)throw new Error('Correo o contraseña incorrectos.');
     const account={id:admin?1:2,name:admin?'Administrador':'Árbitro',email:body.email,role:admin?'admin':'referee'};
     sessionStorage.setItem(userKey,JSON.stringify(account));return {user:account,csrf:'demo'};
   }
   if(route==='/api/logout'){sessionStorage.removeItem(userKey);return {user:null,csrf:'demo'};}
   if(!user())throw new Error('Inicie sesión para continuar.');
   const s=read(),D=window.CanteraDomain;
   if(route==='/api/state')return clone(s);
   if(route.startsWith('/api/reports/'))return {rows:D.report(s,route.split('/').at(-1),Object.fromEntries(url.searchParams),settings)};
   if(route.startsWith('/api/player/'))return D.player(s,+route.split('/').at(-1),settings);
   const [, ,entity,idText]=route.split('/'),id=+idText||0;
   if(!['teams','players','matchdays','goals','incidents'].includes(entity))throw new Error('Ruta no encontrada.');
   if(method==='GET')return {items:s[entity],total:s[entity].length};
   if(user().role!=='admin')throw new Error('Su cuenta tiene permisos de consulta.');
   if(id&&!s[entity].some(r=>+r.id===id))throw new Error('El registro no existe.');
   if(method==='DELETE'){
     if(!D.canDelete(s,entity,id))throw new Error('Este registro tiene información asociada. Se protegió su historial.');
     s[entity]=s[entity].filter(r=>+r.id!==id);
   }else{
     for(const [k,v] of Object.entries(body))if(typeof v==='string')body[k]=v.trim();
     const record=D.validate(s,entity,body,id,settings);
     record.id=id||Math.max(0,...s[entity].map(r=>+r.id))+1;
     if(id)s[entity]=s[entity].map(r=>+r.id===id?{...r,...record}:r);else s[entity].push(record);
   }
   try{localStorage.setItem(key,JSON.stringify(s));}catch{throw new Error('No se guardó el cambio: el almacenamiento de este navegador está lleno.');}
   return {message:method==='DELETE'?'Registro eliminado.':'Registro guardado.'};
 };
})();
