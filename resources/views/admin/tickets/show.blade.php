@extends('layouts.admin')
@section('content')
<section class="content-header">
  <h1>
  Tickets Management 
  </h1>
</section>
<!-- Main content -->
<section class="content">
  <div class="row">
    <!-- left column -->
    <div class="col-md-9">
      <!-- general form elements -->
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">View Ticket Details</h3>
        </div>
        <!-- /.box-header -->
        <!-- form start -->
         &nbsp; &nbsp;
         <button type="button" class="btn bg-teal btn-sm" data-toggle="modal" data-target="#modalResponse" id="reply_modal">Send Reply</button>
         <div id="modalResponse" class="modal fade" role="dialog">
          <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Enter Message For Response</h4>
              </div>
              <div class="modal-body">
                <form name="reponseValues" method="POST" style="padding:17px;">
                  <input type="hidden" class="user_email" value="<?php echo $tickets[0]->email;?>">
                  <input type="hidden" id="job_id" value="<?php echo $tickets[0]->job_id;?>">
                  <input type="hidden" id="user_id" value="<?php echo $tickets[0]->user_id;?>">
                  <div class="form-group">
                    <div class="row">
                      <div class="col-md-12">
                        <label name="title_value">Title</label>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-12">
                        <input type="text" class="title_value" name="title_value" style="width:100%;" required; value="TICKET NUMBER: <?php echo $tickets[0]->ticket_number;?> ">
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="row">
                      <div class="col-md-12">
                        <label name="description_value">Description</label>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-12">
                        <textarea rows="4" cols="50" class="description_value" name="description_value" style="width:100%;">TICKET NUMBER: <?php echo $tickets[0]->ticket_number;?> </textarea>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="send_message" style="margin:0 auto; display:block;">Send Message</button>
              </div>
            </div>

          </div>
        </div>
          <a href="{{ url('/admin/tickets') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
          <a href="{{ url('/admin/tickets/' . $tickets[0]->id . '/edit') }}" title="Edit Tickets"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button></a>
          <form method="POST" action="{{ url('admin/tickets' . '/' . $tickets[0]->id) }}" accept-charset="UTF-8" style="display:inline">
              {{ method_field('DELETE') }}
              {{ csrf_field() }}
              <button type="submit" class="btn btn-danger btn-sm" title="Delete Ticket" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
          </form>
          <br/>
          <br/>
          <form role="form" method="POST" action="{{ url('/admin/tickets/' . $tickets[0]->id) }}" accept-charset="UTF-8" enctype="multipart/form-data">
            <div class="box-body">
              <div class="form-group {{ $errors->has('title') ? 'has-error' : ''}}">
                <label for="exampleInputEmail1" >{{ 'Email' }}</label>
                <input disabled class="form-control" name="email" type="email" id="title" value="<?php echo $tickets[0]->email;?>" >
                {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
              </div>
              <div class="form-group {{ $errors->has('title') ? 'has-error' : ''}}">
                <label for="exampleInputEmail1" >{{ 'User Type' }}</label>
                <input disabled class="form-control" name="user_type" type="text" id="title" value="<?php echo $tickets[0]->user_type;?>" >
                {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
              </div>
              <div class="form-group {{ $errors->has('title') ? 'has-error' : ''}}">
                <label for="exampleInputEmail1" >{{ 'Title' }}</label>
                <input disabled class="form-control" name="title" type="text" id="title" value="<?php echo $tickets[0]->title;?>" >
                {!! $errors->first('title', '<p class="help-block">:message</p>') !!}
              </div>
              <div class="form-group {{ $errors->has('title') ? 'has-error' : ''}}">
                <label for="exampleInputEmail1" >{{ 'Description' }}</label>
                <input disabled class="form-control" name="title" type="text" id="title" value="<?php echo $tickets[0]->description;?>" >
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

