  <?php 

  $userId = Auth::guard('admin')->user()->id;
  $userRole = Auth::guard('admin')->user()->role;
  ?>
 <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
  			   <h4>SKEP Admin</h4>
        </div>
      </div>
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">MAIN NAVIGATION</li>
        <li class="">
          <a href="{{url('/admin/dashboard')}}">
            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
          </a>
        </li>	
        <li>
          <a href="{{url('admin/cleaners')}}">
            <i class="fa fa-circle-o"></i><span>Cleaner Management</span>
          </a>
        </li> 
        <li>
          <a href="{{url('admin/homeowners')}}">
            <i class="fa fa-circle-o"></i><span>Homeowner Management</span>
          </a>
        </li> 
        <li>
          <a href="{{url('admin/futurejobs')}}">
            <i class="fa fa-circle-o"></i><span>Future Jobs</span>
          </a>
        </li>
        <!--<li class="treeview">
          <a href="#">
            <i class="fa fa-ticket"></i>
            <span>Instant Bookings</span>
            <span class="pull-right-container">
              <span class="label label-primary pull-right"></span>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="{{url('admin/bookingsinstant')}}"><i class="fa fa-circle-o"></i>Bookings List</a></li>
            
          </ul>
        </li>-->
        <li class="treeview">
          <a href="#">
            <i class="fa fa-circle-o"></i>
            <span>Services</span>
            <span class="pull-right-container">
              <span class="label label-primary pull-right"></span>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="">
              <a href="{{url('admin/basicservices')}}"><i class="fa fa-cog"></i> Basic Services</a>
            </li>
            <li class="">
              <a href="{{url('admin/extraservices')}}"><i class="fa fa-cog"></i> Extra Services</a>
            </li> 
          </ul>
        </li>
        <li class="treeview">
          <a href="#">
            <i class="fa fa-circle-o"></i>
            <span>Bookings</span>
            <span class="pull-right-container">
              <span class="label label-primary pull-right"></span>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="">
              <a href="{{url('admin/bookingsinstant')}}"><i class="fa fa-cog"></i> Instant Bookings</a>
            </li>
            <li class="">
              <a href="{{url('admin/bookingsadvanced')}}"><i class="fa fa-cog"></i> Advanced Bookings</a>
            </li> 
          </ul>
        </li>
        <li>
          <a href="{{url('admin/mapsofjobs')}}">
            <i class="fa fa-circle-o"></i><span>Maps Of Jobs</span>
          </a>
        </li>
        <li>
          <a href="{{url('admin/tickets')}}">
            <i class="fa fa-circle-o"></i><span>Ticket Management</span>
          </a>
        </li>
        <li>
          <a href="{{url('admin/areaofwork/index')}}">
            <i class="fa fa-circle-o"></i><span>Area Of Work</span>
          </a>
        </li>
        <li>
          <a href="{{url('admin/termsandconditions')}}">
            <i class="fa fa-circle-o"></i><span>Terms And Conditions</span>
          </a>
        </li>
        <li>
          <a href="{{url('admin/waitingtime')}}"><i class="fa fa-circle-o"></i>Waiting Time</a>
        </li>
      </ul>
    </section>