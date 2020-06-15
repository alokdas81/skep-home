@php 
use App\Http\Controllers\Api\v1\BookingController;
$conObj = new BookingController($request);
@endphp

@extends('layouts.admin')

@section('content')
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Future Jobs Management</h1>
    </section>
    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Future Jobs</h3>
                <!--<a style="float:right;" href="{{ url('/admin/futurejobs/create') }}" class="btn btn-success btn-sm" title="Add New Category">
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
                    <th>Job No</th>
                    <th>Job Id</th>
                    <th>Address</th>
                    <th>Cleaner Name</th>
                    <th>Homeowner Name</th>
                    <th>Cost</th>
                    <th>Booking Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                  <?php $i = 1;?>
                  @foreach($bookings as $booking) 
                  @php 
                  
                    $booking_status = $conObj->getBookingStatusInAdmin($booking);
                  @endphp

                    <tr>
                      <td>{{ $i }}</td>
                      <td>{{ $booking->id }}</td>
                      <td>{{ $booking->job_id }}</td>
                      <td>{{ $booking->booking_address }}</td>
                      <td>{{ $booking->cleaner_name }}</td>
                      <td>{{ $booking->homeowner_name }}</td>
                      <td>{{ $booking->booking_price }}</td>
                      <td>{{$booking_status }}</td>                           
                      <td>
                        <a href="<?php echo url('/');?>/admin/futurejobs/<?php echo $booking->id;?>/edit" title="Edit Service"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> </button></a>
                        <form method="POST" action="<?php echo url('/');?>/admin/futurejobs/<?php echo $booking->id;?>" accept-charset="UTF-8" style="display:inline">
                          {{ method_field('DELETE') }}
                          {{ csrf_field() }}
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete Service" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
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
