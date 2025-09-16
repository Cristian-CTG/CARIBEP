<header class="header">
    <div class="logo-container">
        <div class="sidebar-toggle p-1" data-toggle-class="sidebar-left-collapsed" data-target="html"
            data-fire-event="sidebar-left-toggle">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panel-left" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M9 3v18"></path></svg>
        </div>
        <div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">
            <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
        </div>
        <div class="d-md-none ml-1 d-lg-block" style="height: inherit;">
            @if(in_array('documents', $vc_modules))
            <a href="{{ route('tenant.co-documents.create') }}"
                title="Nueva Factura Electrónica"
                class="topbar-links"
                data-placement="bottom"
                data-toggle="tooltip">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text mb-1">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>NFE</span>
            </a>
            @endif
            @if(in_array('pos', $vc_modules))
            <a href="{{ route('tenant.pos.index') }}"
                title="POS"
                class="topbar-links"
                data-placement="bottom"
                data-toggle="tooltip">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cash-register mb-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M21 15h-2.5c-.398 0 -.779 .158 -1.061 .439c-.281 .281 -.439 .663 -.439 1.061c0 .398 .158 .779 .439 1.061c.281 .281 .663 .439 1.061 .439h1c.398 0 .779 .158 1.061 .439c.281 .281 .439 .663 .439 1.061c0 .398 -.158 .779 -.439 1.061c-.281 .281 -.663 .439 -1.061 .439h-2.5"></path><path d="M19 21v1m0 -8v1"></path><path d="M13 21h-7c-.53 0 -1.039 -.211 -1.414 -.586c-.375 -.375 -.586 -.884 -.586 -1.414v-10c0 -.53 .211 -1.039 .586 -1.414c.375 -.375 .884 -.586 1.414 -.586h2m12 3.12v-1.12c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-2"></path><path d="M16 10v-6c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-4c-.53 0 -1.039 .211 -1.414 .586c-.375 .375 -.586 .884 -.586 1.414v6m8 0h-8m8 0h1m-9 0h-1"></path><path d="M8 14v.01"></path><path d="M8 17v.01"></path><path d="M12 13.99v.01"></path><path d="M12 17v.01"></path></svg>
                <span>POS</span>
            </a>
            @endif
            @if(in_array('purchases', $vc_modules))
            <a href="{{ route('tenant.purchases.create') }}"
                title="Generar Compra"
                class="topbar-links"
                data-placement="bottom"
                data-toggle="tooltip">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag mb-1">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"></path>
                    <path d="M9 11v-5a3 3 0 0 1 6 0v5"></path>
                </svg>
                <span>GCP</span>
            </a>
            @endif
            @if(in_array('inventory', $vc_modules))
            <a href="{{ route('tenant.items.index') }}"
                title="Productos"
                class="topbar-links"
                data-placement="bottom"
                data-toggle="tooltip">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M14 4h6v6h-6z"></path><path d="M4 14h6v6h-6z"></path><path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path><path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path></svg>
                <span>PRO</span>
            </a>
            @endif
            @if(in_array('reports', $vc_modules))
            <a href="{{ route('tenant.persons.index', ['type' => 'customers']) }}"
                title="Nuevo Cliente"
                class="topbar-links"
                data-placement="bottom"
                data-toggle="tooltip">
                <svg  xmlns="http://www.w3.org/2000/svg"  width="18"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-users mb-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                <span>CLI</span>
            </a>
            @endif
        </div>
    </div>
    <div class="header-right d-flex align-items-center mr-4">
        @inject('systemUser', 'Modules\Factcolombia1\Models\System\User')
        @php $supportUser = $systemUser::first(); @endphp
        @if($supportUser && ($supportUser->whatsapp_number || $supportUser->phone || $supportUser->address_contact))
        <li class="d-inline-block mr-2">
        <a role="menuitem"  class="nav-link btn-suport btn-header"  onclick="toggleSupportSidebar()" title="Soporte" data-toggle="tooltip">
            <svg width="22" height="22" viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g id="support" fill="currentColor" transform="translate(42.666667, 42.666667)">
                        <path d="M379.734355,174.506667 C373.121022,106.666667 333.014355,-2.13162821e-14 209.067688,-2.13162821e-14 C85.1210217,-2.13162821e-14 45.014355,106.666667 38.4010217,174.506667 C15.2012632,183.311569 -0.101643453,205.585799 0.000508304259,230.4 L0.000508304259,260.266667 C0.000508304259,293.256475 26.7445463,320 59.734355,320 C92.7241638,320 119.467688,293.256475 119.467688,260.266667 L119.467688,230.4 C119.360431,206.121456 104.619564,184.304973 82.134355,175.146667 C86.4010217,135.893333 107.307688,42.6666667 209.067688,42.6666667 C310.827688,42.6666667 331.521022,135.893333 335.787688,175.146667 C313.347976,184.324806 298.68156,206.155851 298.667688,230.4 L298.667688,260.266667 C298.760356,283.199651 311.928618,304.070103 332.587688,314.026667 C323.627688,330.88 300.801022,353.706667 244.694355,360.533333 C233.478863,343.50282 211.780225,336.789048 192.906491,344.509658 C174.032757,352.230268 163.260418,372.226826 167.196286,392.235189 C171.132153,412.243552 188.675885,426.666667 209.067688,426.666667 C225.181549,426.577424 239.870491,417.417465 247.041022,402.986667 C338.561022,392.533333 367.787688,345.386667 376.961022,317.653333 C401.778455,309.61433 418.468885,286.351502 418.134355,260.266667 L418.134355,230.4 C418.23702,205.585799 402.934114,183.311569 379.734355,174.506667 Z M76.8010217,260.266667 C76.8010217,269.692326 69.1600148,277.333333 59.734355,277.333333 C50.3086953,277.333333 42.6676884,269.692326 42.6676884,260.266667 L42.6676884,230.4 C42.6676884,224.302667 45.9205765,218.668499 51.2010216,215.619833 C56.4814667,212.571166 62.9872434,212.571166 68.2676885,215.619833 C73.5481336,218.668499 76.8010217,224.302667 76.8010217,230.4 L76.8010217,260.266667 Z M341.334355,230.4 C341.334355,220.97434 348.975362,213.333333 358.401022,213.333333 C367.826681,213.333333 375.467688,220.97434 375.467688,230.4 L375.467688,260.266667 C375.467688,269.692326 367.826681,277.333333 358.401022,277.333333 C348.975362,277.333333 341.334355,269.692326 341.334355,260.266667 L341.334355,230.4 Z"></path>
                    </g>
                </g>
            </svg>
        </a>
        </li>
        @endif
        <span class="separator"></span>
        <li class="d-inline-block">
            <a role="menuitem" class="nav-link btn-header mr-2" id="fullscreen-btn" title="Pantalla completa" style="font-size: 16px" data-toggle="tooltip">
                <i id="fullscreen-icon" class="fas fa-expand"></i>
            </a>
        </li>
        <span class="separator"></span>
        <div id="userbox" class="userbox">
            <a href="#" data-toggle="dropdown">
                @php
                    $names = explode(' ', trim($vc_user->name));
                    if(count($names) === 1) {
                        $initials = mb_strtoupper(mb_substr($names[0], 0, 2));
                    } else {
                        $initials = mb_strtoupper(mb_substr($names[0], 0, 1) . mb_substr($names[1], 0, 1));
                    }
                @endphp
                <div class="name-initials-container" data-lock-name="{{ $vc_user->email }}" data-lock-email="{{ $vc_user->email }}">
                    <span class="name-initials">{{ $initials }}</span>
                </div>
                <i class="fa custom-caret"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-desktop">
                <ul class="list-unstyled mb-0">
                    <li class="user-profile-li py-1">
                        <a href="#">
                            <div class="profile-info ">
                                <span class="name text-left">{{ $vc_user->name }}</span>
                                <span class="role text-left">{{ $vc_user->email }}</span>
                            </div>
                        </a>                        
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a id="styleSwitcherOpen" role="menuitem" href="#" onclick="event.preventDefault(); document.getElementById('styleSwitcher')?.classList.toggle('open'); document.getElementById('styleSwitcher')?.scrollIntoView({behavior: 'smooth'});">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-brush mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21v-4a4 4 0 1 1 4 4h-4" /><path d="M21 3a16 16 0 0 0 -12.8 10.2" /><path d="M21 3a16 16 0 0 1 -10.2 12.8" /><path d="M10.6 9a9 9 0 0 1 4.4 4.4" /></svg>
                            Estilos y temas
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a role="menuitem" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-logout mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg>
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
</header>

<!-- Sidebar de Soporte -->
<div id="supportSidebar" class="support-sidebar">
        @inject('systemUser', 'Modules\Factcolombia1\Models\System\User')
        @php
            $user = $systemUser::first();
        @endphp
    <div class="support-header">
        Soporte al Cliente
        <button class="close-btn" onclick="toggleSupportSidebar()">&times;</button>
    </div>
    <div class="support-body">
        <div class="support-intro richtext">
            {!! $user->introduction !!}
        </div>

    @if($user && $user->whatsapp_number)
    <div class="support-container support-whatsapp">
            <div class="icon-support-container support-left">
                <div>
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="28"  height="28"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-brand-whatsapp"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                </div>
            </div>
            <div class="support-right">
                <strong>WhatsApp</strong>
                <a class="support-link  support-link-whatsapp d-flex flex-column" href="https://wa.me/{{ $user->whatsapp_number }}" target="_blank">
                    {{ $user->whatsapp_number }}
                </a>
            </div>
    </div>
    @endif

    @if($user && $user->address_contact)
    <div class="support-container support-email">   
            <div class="icon-support-container support-left">
                <div>
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="28"  height="28"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-mail"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                </div>                
            </div>         
            <div class="support-right">
                <strong>Correo Electrónico</strong>
                <a class="support-link support-link-email d-flex flex-column" href="https://mail.google.com/mail/?view=cm&fs=1&to={{ $user->address_contact }}" target="_blank">
                    {{ $user->address_contact }}
                </a>
            </div>
    </div>
    @endif

    @if($user && $user->phone)
    <div class="support-container support-phone">
            <div class="icon-support-container support-left">
                <div>
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="28"  height="28"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-phone"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                </div>
            </div>
            <div class="support-right">
                <strong>Teléfono</strong>
                <a class="support-link support-link-phone d-flex flex-column" href="tel:{{ $user->phone }}">
                    {{ $user->phone }}
                </a>
            </div>
    </div>
    @endif
        
    </div>
</div>
<div id="supportBackdrop" class="support-backdrop" onclick="toggleSupportSidebar()"></div>



<script>
function toggleSupportSidebar() {
    const sidebar = document.getElementById('supportSidebar');
    const backdrop = document.getElementById('supportBackdrop');
    sidebar.classList.toggle('show');
    backdrop.classList.toggle('show');
}
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('fullscreen-btn');
    const icon = document.getElementById('fullscreen-icon');

    function updateIcon() {
        if (document.fullscreenElement) {
            icon.classList.remove('fa-expand');
            icon.classList.add('fa-compress');
        } else {
            icon.classList.remove('fa-compress');
            icon.classList.add('fa-expand');
        }
    }

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });

    document.addEventListener('fullscreenchange', updateIcon);
});
</script>