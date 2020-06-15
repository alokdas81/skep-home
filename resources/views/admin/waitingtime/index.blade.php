@extends('layouts.admin')

@section('content')
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Waiting Time Management
      </h1>
      <!--<ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Users</a></li>
      </ol>-->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">All Timings</h3>
                <!--<a style="float:right;" href="{{ url('/admin/waitingtime/create') }}" class="btn btn-success btn-sm" title="Add New Category">
                  <i class="fa fa-plus" aria-hidden="true"></i> Add New
                </a>-->
            </div>
            <!-- /.box-header -->
            @if ($message = Session::get('flash_message'))
              <div class="alert alert-success alert-block">
                <button type="button" class="close" data-dismiss="alert">×</button>
                  <strong>{{ $message }}</strong>
              </div>
            @endif
            <div class="box-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Id</th>
                    <th>Waiting Time(in sec)</th>
                    <th>Waiting For</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1;?>
                  @foreach($waiting_time as $timings)
                    <tr>
                      <td>{{ $i }}</td>
                      <td>{{ $timings->waiting_time }}</td>
                      <td>{{ $timings->waiting_time_for }}</td>
                      <td><a href="<?php echo url('/');?>/admin/waitingtime/<?php echo $timings->id;?>/edit" title="Edit User"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> </button></a>
                        <form method="POST" action="<?php echo url('/');?>/admin/waitingtime/<?php echo $timings->id;?>" accept-charset="UTF-8" style="display:inline">
                          {{ method_field('DELETE') }}
                          {{ csrf_field() }}
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete User" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                        </form>
                      </td>
                    </tr>
                  <?php $i++;?>
                 @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection
