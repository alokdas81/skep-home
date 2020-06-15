@extends('layouts.admin')
@section('content')


<section class="content-header">
  <h1>Cleaner Management </h1>
</section>

<!-- Main content -->

<section class="content">
  <div class="row">
    <!-- left column -->
    <div class="col-md-9">
      <!-- general form elements -->
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">View Cleaner</h3>
        </div>
        <!-- /.box-header -->

          <a href="{{ url('/admin/cleaners') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>

          <a href="{{ url('/admin/cleaners/' . $user->id . '/edit') }}" title="Edit Category"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button></a>

          <form method="POST" action="{{ url('admin/cleaners' . '/' . $user->id) }}" accept-charset="UTF-8" style="display:inline">

              {{ method_field('DELETE') }}

              {{ csrf_field() }}

              <button type="submit" class="btn btn-danger btn-sm" title="Delete Cleaner" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
          </form>
          <br/>
          <br/>

        <form role="form" method="POST" action="{{ url('/admin/categories/' . $user->id) }}" accept-charset="UTF-8" enctype="multipart/form-data">
          <div class="box-body">
            <div class="form-group {{ $errors->has('email') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Email' }}</label>
              <input disabled class="form-control" name="email" type="text" id="email" value="@php echo (!empty($user->email))?$user->email:''; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('first_name') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'First Name' }}</label>
              <input disabled class="form-control" name="first_name" type="text" id="first_name" value="@php echo (!empty($user->first_name))?$user->first_name:''; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('last_name') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Last Name' }}</label>
              <input disabled class="form-control" name="last_name" type="text" id="last_name" value="@php echo (!empty($user->last_name))?$user->last_name:''; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('phone_number') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Phone Number' }}</label>
              <input disabled class="form-control" name="phone_number" type="text" id="phone_number" value="@php echo (!empty($user->phone_number))?$user->phone_number:''; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('address') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Address' }}</label>
              <input disabled class="form-control" name="address" type="text" id="address" value="@php echo (!empty($user->address))?$user->address:''; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('regions') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Selected Regions' }}</label>
              <input disabled class="form-control" name="regions" type="text" id="regions" value="@php echo (!empty($areas))?$areas:''; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('is_super_cleaner') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Is Super Cleaner' }}</label>
              <input disabled class="form-control" name="super_cleaner" type="text" id="super_cleaner" value="@php echo (!empty($user->is_super_cleaner) && ($user->is_super_cleaner == 1))? 'Yes': 'No'; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('work_status') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Authenticate Status' }}</label>
              <input disabled class="form-control" name="authenticate_status" type="text" id="authenticate_status" value="@php echo (!empty($user->authenticate_status) && ($user->authenticate_status == 1))? 'Approve': 'Not Approve'; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <div class="form-group {{ $errors->has('work_status') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Work Status' }}</label>
              <input disabled class="form-control" name="work_status" type="text" id="work_status" value="@php echo (!empty($user->work_status) && ($user->work_status == 1))? 'Online': 'Offline'; @endphp">
              {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>
            <?php if(!empty($user->profile_pic)){
            $url = URL::to("/");?>
            
          <?php } ?>
          <?php if(!empty($user->selfie_image)){
            $url = URL::to("/");?>
            <div class="form-group {{ $errors->has('selfie_image') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Selfie Image' }}</label>
              <a class="example-image-link" href="@php echo $url.('/public/images/selfie_verification/').$user->selfie_image; @endphp" data-lightbox="Profile Pic"><img src="@php echo $url.('/public/images/selfie_verification/').$user->selfie_image; @endphp" height="50px;" width="50px;"></a>
            </div>
          <?php }else{ ?>
            <div class="form-group {{ $errors->has('first_name') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'No Selfie image uploaded' }}</label>              
            </div>
          <?php 
          }
          
          if(!empty($user->government_id_image_front)){
            $url = URL::to("/");?>
            <div class="form-group {{ $errors->has('government_id_image_front') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Govt Front Image' }}</label>
              <a class="example-image-link" href="@php echo $url.('/public/images/authentication_certificates/').$user->government_id_image_front; @endphp" data-lightbox="Government front image">
              <img src="@php echo $url.('/public/images/authentication_certificates/').$user->government_id_image_front; @endphp" height="50px;" width="50px;">
              </a>
            </div>
          <?php }else{ ?>
            <div class="form-group {{ $errors->has('first_name') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'No Government front image uploaded' }}</label>              
            </div>
          <?php 
          }
          
          if(!empty($user->government_id_image_back)){
            $url = URL::to("/");?>
            <div class="form-group {{ $errors->has('government_id_image_back') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Govt Back Image' }}</label>
              <a class="example-image-link" href="@php echo $url.('/public/images/authentication_certificates/').$user->government_id_image_back; @endphp" data-lightbox="Government back image">
              <img src="@php echo $url.('/public/images/authentication_certificates/').$user->government_id_image_back; @endphp" height="50px;" width="50px;">
              </a>
            </div>
          <?php }else{ ?>
            <div class="form-group {{ $errors->has('first_name') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'No Government back image uploaded' }}</label>              
            </div>
          <?php 
            }
          ?>

          </div>
        </form>
      </div>
      <!-- /.box -->  
    </div>
    <!--/.col (left) -->
  </div>
  <!-- /.row -->
</section>   

@endsection

