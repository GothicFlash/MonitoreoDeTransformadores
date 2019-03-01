<br>
  <div class="navbar-default sidebar" role="navigation">
    <a href="{{ route('home') }}"><img src="{{ asset('images/logotipo-confiamex.png') }}" class="img-responsive center-block" width="70"></a>
    <div class="sidebar-nav navbar-collapse">
      <ul class="nav" id="side-menu">
        <li class="sidebar-search"></li>
        <li>
          <a href="#"><i class="fa fa-desktop fa-fw"></i> Monitores<span class="fa arrow"></span></a>
          <ul class="nav nav-second-level">
            <li>
              <a href="{{ route('monitors.create') }}"><i class="fa fa-plus fa-fw"></i> Agregar</a>
              <a href="{{ route('home') }}"><i class="fa fa-eye fa-fw"></i> Listar monitores</a>
            </li>
          </ul>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="{{ route('notifications.index') }}"><i class="fa fa-bell fa-fw"></i> Notificaciones</a>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="#"><i class="fa fa-cloud fa-fw"></i> Gases<span class="fa arrow"></span></a>
          <ul class="nav nav-second-level">
            <li>
              <a href="{{ route('gases.create') }}"><i class="fa fa-plus fa-fw"></i> Agregar</a>
              <a href="{{ route('gases.index') }}"><i class="fa fa-eye fa-fw"></i> Listar gases</a>
            </li>
          </ul>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="#"><i class="fa fa-user fa-fw"></i> Usuarios<span class="fa arrow"></span></a>
          <ul class="nav nav-second-level">
            <li>
              <a href="{{ route('users.create') }}"><i class="fa fa-user-plus fa-fw"></i> Agregar</a>
              <a href="{{ route('users.index') }}"><i class="fa fa-eye fa-fw"></i> Listar usuarios</a>
            </li>
          </ul>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="{{ route('scans.index') }}"><i class="fa fa-clock-o fa-fw"></i> Poleos</a>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="{{ route('reports.index') }}"><i class="fa fa-file-text-o fa-fw"></i> Reportes</a>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="{{ route('backs.index') }}"><i class="fa fa-folder fa-fw"></i> Respaldos</a>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="{{ route('databases.index') }}"><i class="fa fa-database fa-fw"></i> Bases de datos</a>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="{{ route('imports.index') }}"><i class="fa fa-upload fa-fw"></i> Importar registros</a>
          <!-- /.nav-second-level -->
        </li>
        <li>
          <a href="{{ route('probabilities.index') }}"><i class="fa fa-line-chart fa-fw"></i> Probabilidad de falla</a>
          <!-- /.nav-second-level -->
        </li>
      </ul>
    </div>
    <!-- /.sidebar-collapse -->
</div>
<!-- /.navbar-static-side -->
