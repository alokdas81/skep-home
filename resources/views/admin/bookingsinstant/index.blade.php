@php 
use App\Http\Controllers\Api\v1\BookingController;
$conObj = new BookingController($request);
@endphp
@extends('layouts.admin')
@section('content')

<!-- Content Header (Page header) -->

    <section class="content-header">
      <h1>Instant Bookings Management</h1>
    </section>
    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">All Bookings</h3>
              <a style="float:right;" href="{{ url('/admin/bookingsinstant/create') }}" class="btn btn-success btn-sm" title="Add New Category"><i class="fa fa-plus" aria-hidden="true"></i> Add New</a>
            </div>
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
                    <th>Sr.No</th>
                    <th>Job Id</th>
                    <th>Booking Date</th>
                    <th>Booking Timings</th>
                    <th>Homeowner Name</th>
                    <th>Cleaner Name</th>
                    <th>Homemowner rating earned</th>
                    <th>Cleaner rating earned</th>
                    <th>Booking Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($bookings as $item)
                  @php $timings = date("h:i a",strtotime($item->service_start_time));
                    $booking_status = $conObj->getBookingStatusInAdmin($item);
                    
                    $home_owner_rating = $cleaner_rating = '';
                    if($booking_status == 'Completed' && $item->ratingGivenByCleaner == 1){
                      
                      $home_owner_rating = $conObj->getBookingRating($item->id,$item->service_provider_id,$item->user_id);
                    }
                    if($booking_status == 'Completed' && $item->ratingGivenByHomeOwner == 1){
                      
                      $cleaner_rating = $conObj->getBookingRating($item->id,$item->user_id,$item->service_provider_id);
                    }
                    
                   @endphp
                    <tr>
                      <td>{{$loop->iteration}}</td>
                      <td>{{ $item->job_id }}</td>
                      <td>{{$item->booking_date }}</td>
                      <td>{{ $timings }}</td>
                      <td>{{$item->homeowner_name }}</td>
                      <td>{{$item->cleaner_name }}</td>  
                      <td>{{$home_owner_rating }}</td>  
                      <td>{{$cleaner_rating }}</td>                      
                      <td>{{$booking_status }}</td>                           
                      <td>
                        <a href="<?php echo url('/');?>/admin/bookingsinstant/<?php echo $item->id;?>/edit" title="Edit Booking"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> </button></a>
                        <form method="POST" action="<?php echo url('/');?>/admin/bookingsinstant/<?php echo $item->id;?>" accept-charset="UTF-8" style="display:inline">
                          {{ method_field('DELETE') }}
                          {{ csrf_field() }}
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete Booking" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
        </div>
      </div>
    </section>
@endsection

