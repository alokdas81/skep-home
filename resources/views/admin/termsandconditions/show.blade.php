@extends('layouts.admin')

@section('content')

<section class="content-header">

  <ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="#">Category</a></li>
    <li class="active">View Category</li>
  </ol>

</section>

<!-- Main content -->

<section class="content">
  <div class="row">
   <!-- left column -->

    <div class="col-md-9">
      <!-- general form elements -->
      <div class="box box-primary">
        <!-- /.box-header -->

        <!-- form start -->

         &nbsp; &nbsp;
           <a href="{{ url('/admin/termsandconditions') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>

          <a href="{{ url('/admin/termsandconditions/' . $pages->id . '/edit') }}" title="Edit Pages"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button></a>
          <form method="POST" action="{{ url('/admin/termsandconditions/' . $pages->id . '/edit') }}" accept-charset="UTF-8" style="display:inline">
              {{ method_field('DELETE') }}

              {{ csrf_field() }}
              <button type="submit" class="btn btn-danger btn-sm" title="Delete Page" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
          </form>
          <br/>
          <br/>

        <form role="form" method="POST" action="{{ url('/admin/termsandconditions/' . $pages->id) }}" accept-charset="UTF-8" enctype="multipart/form-data">
          <div class="box-body">
            <div class="form-group {{ $errors->has('title') ? 'has-error' : ''}}">
              <label for="exampleInputEmail1" >{{ 'Title' }}</label>
                <input class="form-control" name="title" type="text" id="title" value="{{ $pages->title or ''}}" >
                {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
            </div>            
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

