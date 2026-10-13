<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Cantera: gestión de equipos, jugadores y jornadas de fútbol infantil.">
  <meta name="theme-color" content="#122d29">
  <title>Cantera · Torneo infantil</title>
  <link rel="icon" href="/images/brand.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/css/app.css">
  <script src="/js/domain.js" defer></script>
  <script src="/js/app.js" defer></script>
</head>
<body>
  <div id="app" class="app-shell" hidden>
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="#dashboard"><img src="/images/brand.svg" alt=""><span>cantera<span class="brand-sub">EL JUEGO EMPIEZA AQUÍ</span></span></a>
      <p class="nav-label">TORNEO INFANTIL</p>
      <nav aria-label="Navegación principal" id="navigation"></nav>
      <div class="sidebar-bottom">
        <div class="season"><span class="live-dot"></span><div><strong>Temporada 2026</strong><small>Fútbol infantil · Guatemala</small></div></div>
        <div class="account"><span class="account-avatar" id="account-avatar">A</span><div><strong id="account-name"></strong><small id="account-role"></small></div><button class="icon-button" data-action="logout" title="Cerrar sesión" aria-label="Cerrar sesión" id="logout-icon"></button></div>
      </div>
    </aside>
    <div class="workspace">
      <header class="topbar">
        <button class="icon-button mobile-menu" data-action="menu" aria-label="Abrir menú" id="menu-icon"></button>
        <div class="breadcrumb">Liga infantil <span>/</span> <strong id="breadcrumb">Panel general</strong></div>
        <form id="global-search" class="global-search" role="search"><span id="search-icon"></span><input aria-label="Buscar jugador" name="q" placeholder="Buscar jugador…" autocomplete="off"><kbd>↵</kbd></form>
        <span class="topbar-pill">Temporada 2026</span>
      </header>
      <main id="main-content" tabindex="-1"><div class="loading">Preparando el torneo…</div></main>
      <footer class="main-footer"><span>Cantera · Gestión del torneo infantil</span><span id="mode-label">Proyecto académico · UMG</span></footer>
    </div>
  </div>
  <section id="login" class="login-screen" hidden>
    <div class="login-story"><a class="brand" href="#"><img src="/images/brand.svg" alt=""><span>cantera</span></a><div><span class="eyebrow light">CADA JUGADOR TIENE UNA HISTORIA</span><h1>El talento crece.<br>El torneo se organiza.</h1><p>Equipos, jornadas y pequeños grandes goles.<br>Todo lo que pasa en la cancha, en un solo lugar.</p><div class="login-pitch" aria-hidden="true"><div class="pitch-circle"></div><div class="pitch-midline"></div><div class="pitch-goal left"></div><div class="pitch-goal right"></div><span class="ball">⚽</span></div></div><small>Torneo infantil · Guatemala · 2026</small></div>
    <div class="login-form-panel"><div class="login-form-wrap"><span class="eyebrow">BIENVENIDO A CANTERA</span><h2>Listos para la jornada.</h2><p class="muted">Ingresa para administrar el torneo o consultar los reportes.</p><form id="login-form"><label>Correo electrónico<input type="email" name="email" required maxlength="200" autocomplete="username" placeholder="tu@correo.com"></label><label>Contraseña<div class="password-field"><input type="password" name="password" required maxlength="200" autocomplete="current-password" placeholder="Tu contraseña"><button type="button" class="icon-button" data-action="toggle-password" aria-label="Mostrar contraseña" id="password-icon"></button></div></label><p id="login-error" class="form-error" role="alert"></p><button class="button primary full" type="submit">Ingresar a Cantera <span>→</span></button></form><div id="demo-accounts" class="demo-accounts" hidden><span>CUENTAS DE DEMOSTRACIÓN</span><div><button class="button secondary" data-action="prefill" data-role="admin">Administrador</button><button class="button secondary" data-action="prefill" data-role="referee">Árbitro / consulta</button></div><small>Selecciona una cuenta para completar sus credenciales de prueba.</small></div><p id="login-mode" class="login-mode"></p></div></div>
  </section>
  <div id="startup" class="startup"><img src="/images/brand.svg" alt=""><span>Cargando Cantera…</span></div>
  <dialog id="form-dialog" class="modal"><div id="dialog-content"></div></dialog>
  <dialog id="detail-dialog" class="modal wide"><div id="detail-content"></div></dialog>
  <dialog id="confirm-dialog" class="modal small"><div id="confirm-content"></div></dialog>
  <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
