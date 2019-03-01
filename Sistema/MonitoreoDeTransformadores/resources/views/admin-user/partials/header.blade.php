<ul class="nav navbar-top-links navbar-right">
  <li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
      <i class="fa fa-bell fa-fw"></i><span id="notifications_count" class="badge badge-light"></span>Notificaciones <i class="fa fa-caret-down"></i>
    </a>
    <ul class="dropdown-menu dropdown-alerts">
      <li>
        <div>
          <ul id="notifications"></ul>
        </div>
      </li>
      <li class="divider"></li>
      <li>
        <a class="text-center" href="{{ route('notifications.index') }}">
          <strong>Ver todas las notificaciones</strong>
          <i class="fa fa-angle-right"></i>
        </a>
      </li>
    </ul>
  </li>
  <li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
      <i class="fa fa-user fa-fw"></i> {{ auth()->user()->name }} <i class="fa fa-caret-down"></i>
    </a>
    <ul class="dropdown-menu dropdown-user">
      <li>
        <a href="{{ route('logout') }}"><i class="fa fa-sign-out fa-fw"></i> Salir</a>
      </li>
    </ul>
  </li>
</ul>
