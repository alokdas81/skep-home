@extends('layouts.admin')
@section('content')
<!-- Content Header (Page header) -->

    <section class="content-header">
      <h1>Home Owners Management</h1>
    </section>

    <!-- Main content -->

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">All Users</h3>
                <a style="float:right;" href="{{ url('/admin/homeowners/create') }}" class="btn btn-success btn-sm" title="Add New Category">
                  <i class="fa fa-plus" aria-hidden="true"></i> Add New
                </a>
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Signup Date</th>
                    <th>Govt ID</th>
                    <th>Status</th>
                    <th>Ratings</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1;
                  $url = URL::to("/");?>
                  @foreach($user as $users)
                    <tr>
                      <td>{{ $i }}</td>
                      <td>{{ $users->first_name }}</td>
                      <td>{{ $users->email }}</td>
                      <td>{{ $users->phone_number }}</td>
                      <td>{{ date("Y-m-d",strtotime($users->created_at)) }}</td>
                      @if(!empty($users->government_id_image_front))
                      <td><a class="example-image-link" href="<?php echo $url.('/public/images/authentication_certificates/').$users->government_id_image_front;?>" data-lightbox="Govt image"><img src="<?php echo $url.('/public/images/authentication_certificates/').$users->government_id_image_front;?>" height="100px;" width="100px;"></a></td>
                        @else
                      <td><img src="<?php echo $url.('/public/images/no-image-icon.png');?>" height="50px;" width="50px;"></td>
                      @endif
                      <td class="user_status-<?php echo $users->id;?>"><?php echo (!empty($users->status) && $users->status == 1 && $users->status == 1)?'Approved':'Not Approved';?></td>
                      <td>{{ number_format($users->rating,1) }}</td>
                      <td class="actions_sections"><a title="Suspend Homeowner"><button class="btn btn-primary btn-sm suspend_user-<?php echo $users->id;?>" <?php echo ($users->status == 0)? 'disabled': '';?> onclick="suspendHomeowner({{$users->id}})"><i class="fa fa-ban" aria-hidden="true"></i> </button></a>
                        <a title="Approve Homeowner"> <button class="btn btn-primary btn-sm approve_user-<?php echo $users->id;?>" <?php echo ($users->status == 1)? 'disabled': '';?> onclick="approveHomeowner({{$users->id}})"><i class="fa fa-check" aria-hidden="true"></i> </button></a>
                        <a href="<?php echo url('/');?>/admin/homeowners/<?php echo $users->id;?>" title="Show Homeowner"><button class="btn btn-primary btn-sm"><i class="fa fa-eye" aria-hidden="true"></i> </button></a>
                        <a href="<?php echo url('/');?>/admin/homeowners/<?php echo $users->id;?>/edit" title="Edit Homeowner"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> </button></a>
                        <form method="POST" action="<?php echo url('/');?>/admin/users/<?php echo $users->id;?>" accept-charset="UTF-8" style="display:inline">
                          {{ method_field('DELETE') }}
                          {{ csrf_field() }}
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete Homeowner" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                        </form>
                      </td>
                    </tr>
                    @php $i++;@endphp
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection
<script type="text/javascript">
  function suspendHomeowner(id = ''){
    $.ajax({
        type:'POST',
        url: "<?php echo url('/');?>/admin/homeowner/suspendHomeowner",
        data:{'id':id,'_token':"<?php echo csrf_token();?>"},
        success:function(data) {
          $('.suspend_user-'+id).attr("disabled","disabled");
          $('.approve_user-'+id).prop("disabled",false);
          $('.user_status-'+id).text("Not Approved");
        }
    });
  }

  function approveHomeowner(id = ''){
    $.ajax({
        type:'POST',
        url: "<?php echo url('/');?>/admin/homeowner/approveHomeowner",
        data:{'id':id,'_token':"<?php echo csrf_token();?>"},
        success:function(data) {
          $('.approve_user-'+id).attr("disabled","disabled");
          $('.suspend_user-'+id).prop("disabled",false);
          $('.user_status-'+id).text("Approved");
        }
    });
  }
</script>


