<br>
<div class="navbar-default sidebar" role="navigation">
  <a href="{{ route('home') }}"><img src="{{ asset('images/logotipo-confiamex.png') }}" class="img-responsive center-block" width="70"></a>
  <div class="sidebar-nav navbar-collapse">
    <ul class="nav" id="side-menu">
      <li class="sidebar-search"></li>
      <li>
        <a href="{{ route('home') }}"><i class="fa fa-desktop fa-fw"></i> Monitores</span></a>
      </li>
      <li>
        <a href="{{ route('notifications.index') }}"><i class="fa fa-bell fa-fw"></i> Notificaciones</a>
      </li>
      <li>
        <a href="{{ route('reports.index') }}"><i class="fa fa-file-text-o fa-fw"></i> Reportes</a>
      </li>
      <li>
        <a href="{{ route('databases.index') }}"><i class="fa fa-database fa-fw"></i> Bases de datos</a>
      </li>
    </ul>
  </div>
</div>
