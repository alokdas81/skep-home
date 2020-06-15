@extends('layouts.admin')

@section('content')

<section class="content-header">
  <h1>
  Enter Waiting Time Details 
  </h1>
  <!--<ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="#">User</a></li>
    <li class="active">Edit User</li>
  </ol>-->
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Edit Home Owners</h3>
        </div>
        <form role="form" method="POST" action="{{ url('/admin/waitingtime/' . $waiting_time->id) }}" accept-charset="UTF-8"  enctype="multipart/form-data">
            {{ csrf_field() }}
            @if ($errors->any())
                <ul class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
         <form method="POST" action="{{ url('/admin/waitingtime/' . $waiting_time->id) }}" accept-charset="UTF-8" class="" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            {{ csrf_field() }}
            @include ('admin.waitingtime.form', ['submitButtonText' => 'Update'])       
        </form>
      </div>
    </div>
  </div>
</section>   
@endsection
