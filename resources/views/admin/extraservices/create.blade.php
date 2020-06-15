@extends('layouts.admin')

@section('content')
<section class="content-header">
  <h1>
   Extra Services Management
  </h1>
  <!--<ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="#">Users</a></li>
    <li class="active">Add Users</li>
  </ol>-->
</section>
<!-- Main content -->
<section class="content">
  <div class="row">
    <div class="col-md-9">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Enter Service Details </h3>
        </div>
        <form role="form" method="POST" action="{{ url('/admin/extraservices') }}" accept-charset="UTF-8" enctype="multipart/form-data">
            {{ csrf_field() }}
            @if ($errors->any())
                <ul class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            @include ('admin.extraservices.form')          
        </form>
      </div> 
    </div>
  </div>
</section>   
@endsection
