<header class="header header-system" style="left:0px;>
    <div class="logo-container">
        <a href="{{route('system.co-companies')}}" class="logo logo-system pt-2 pt-md-0 mt-3 h5 text-uppercase">
            Panel Reseller
        </a>
        <div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">
            <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
        </div>
    </div>
    <!-- start: search & user box -->
    <div class="header-right">
        <span class="separator"></span>
        <div id="userbox" class="userbox userbox-system">
            <a href="#" data-toggle="dropdown">
                @php
                    $fullName = trim(\Auth::getUser()->name ?? '');
                    $names = preg_split('/\s+/', $fullName);
                    if(count($names) === 1) {
                        $initials = mb_strtoupper(mb_substr($names[0], 0, 2));
                    } else {
                        $initials = mb_strtoupper(mb_substr($names[0], 0, 1) . mb_substr($names[1], 0, 1));
                    }
                @endphp
                <div class="name-initials-container" style="margin-right: 15px" data-lock-name="{{ \Auth::getUser()->email }}" data-lock-email="{{ \Auth::getUser()->email }}">
                    <span class="name-initials">{{ $initials }}</span>
                </div>
                <i class="fa custom-caret"></i>
            </a>
            <div class="dropdown-menu">
                <ul class="list-unstyled mb-0">
                    <li class="user-profile-li py-1">
                        <a href="#">
                            <div class="profile-info ">
                                <span class="name text-left">{{ \Auth::getUser()->name }}</span>
                                <span class="role text-left">{{ \Auth::getUser()->email }}</span>
                            </div>
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a role="menuitem" href="{{ route('system.users.create') }}"> 
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="18"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-user" style="margin-right: 0.5rem"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                            Perfil
                        </a>                        
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a role="menuitem" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="18"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-logout" style="margin-right: 0.5rem"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg>
                            Cerrar Sesión
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- end: search & user box -->
</header>
