<!-- Logo -->
<a href="{{url('/admin/dashboard')}}" class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini"><b>S</b> K</span>
    <!-- logo for regular state and mobile devices -->
    <span class="logo-lg"><img src="{{URL::asset('public/images/admin/logo.png')}}" class="user-image"
            alt="Logo"></span>
</a>
<!-- Header Navbar: style can be found in header.less -->
<nav class="navbar navbar-static-top">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </a>

    <div class="navbar-custom-menu">

        <ul class="nav navbar-nav">
            <!-- Notifications: style can be found in dropdown.less -->

            <!-- User Account: style can be found in dropdown.less -->
            <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    @if(Auth::user()->profileImage <> '')
                        <img src="{{URL::asset('public/'.Auth::user()->profileImage)}}" class="user-image"
                            alt="User Image">
                        @endif
                        <span class="hidden-xs">{{ucfirst(Auth::user()->name)}}</span>
                </a>
                <ul class="dropdown-menu">
                    <!-- User image -->
                    <li class="user-header">
                        @if(Auth::user()->profileImage <> '')
                            <img src="{{URL::asset('public/'.Auth::user()->profileImage)}}" class="user-image"
                                alt="User Image">
                            @endif

                            <p>
                                {{ucfirst(Auth::user()->name)}} - Administrator
                                <small>Member since {{date('M, Y',strtotime(Auth::user()->created_at))}}</small>
                            </p>
                    </li>
                    <!-- Menu Body -->

                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <div class="pull-left">
                            <a href="javascript:void(0);" class="btn btn-default btn-flat">Profile</a>
                        </div>
                        <div class="pull-right">
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                            <a href="{{ route('logout') }}" class="btn btn-default btn-flat"
                                onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i
                                    class="fa fa-mail-forward"></i> <span>Logout</span></a>
                        </div>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</nav>