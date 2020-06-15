@extends('layouts.admin')

@section('content')

<section class="content-header">
  <h1>Instant Booking Management</h1>
</section>

<!-- Main content -->

<section class="content">
  <div class="row">
    <!-- left column -->
    <div class="col-md-9">
      <!-- general form elements -->
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Add Booking</h3>
        </div>
        <!-- form start -->
        <form role="form" method="POST" action="{{ url('/admin/bookingsinstant') }}" accept-charset="UTF-8" enctype="multipart/form-data">
            {{ csrf_field() }}

            @if ($errors->any())
                <ul class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            @include ('admin.bookingsinstant.form')          
        </form>
      </div>
      <!-- /.box -->  
    </div>
    <!--/.col (left) -->
  </div>
  <!-- /.row -->
</section>   

@endsection

