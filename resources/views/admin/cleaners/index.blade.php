@extends('layouts.admin')
@section('content')

<!-- Content Header (Page header) -->

    <section class="content-header">
      <h1>Cleaners Management</h1>
    </section>

    <!-- Main content -->

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">All Cleaners</h3>
                <a style="float:right;" href="{{ url('/admin/cleaners/create') }}" class="btn btn-success btn-sm" title="Add New Category">
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
                <th>Sr. No.</th>
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
                  $url = URL::to("/");
                  if(($user)){
                  ?>
                  @foreach($user as $users)
                  <?php if($users->id){ ?>
                    <tr>
                      <td>{{ $i }}</td>
                      <td>{{ $users->id }}</td>
                      <td>
                      <?php if(!empty($users->first_name)){ ?>
                        {{ $users->first_name }}
                      <?php } ?>
                      </td>
                      <td>
                      <?php if(!empty($users->email)){ ?>
                      {{ $users->email,0,18 }}</td>
                      <?php } ?>
                      <td>
                      <?php if(!empty($users->phone_number)){ ?>
                      {{ $users->phone_number }}
                      <?php } ?>
                      </td>
                      <td>
                      <?php if(!empty($users->phone_number)){ ?>
                      {{ date("Y-m-d",strtotime($users->created_at)) }}
                      <?php } ?>
                      </td>
                      <?php if(!empty($users->government_id_image_front)){?>
                      <td><a class="example-image-link" href="<?php echo $url.('/public/images/authentication_certificates/').$users->government_id_image_front;?>" data-lightbox="Govt image"><img src="<?php echo $url.('/public/images/authentication_certificates/').$users->government_id_image_front;?>" height="100px;" width="100px;"></a></td>
                      <?php } else{?>
                        <td><img src="<?php echo $url.('/public/images/no-image-icon.png');?>" height="50px;" width="50px;"></td>
                      <?php }?>
                      <td class="user_status-<?php echo $users->id;?>"><?php echo (!empty($users->status) && $users->status == 1)?'Approved':'Not Approved';?></td>
                      <td>{{ number_format($users->rating,1) }}</td>
                      <td class="actions_sections"><a title="Suspend Cleaner"><button class="btn btn-primary btn-sm suspend_user-<?php echo $users->id;?>" <?php echo ($users->status == 0)? 'disabled': '';?> onclick="suspendUser({{$users->id}})"><i class="fa fa-ban" aria-hidden="true"></i> </button></a>
                      <a title="Approve Cleaner"> <button class="btn btn-primary btn-sm approve_user-<?php echo $users->id;?>" <?php echo ($users->status == 1)? 'disabled': '';?> onclick="approveUser({{$users->id}})"><i class="fa fa-check" aria-hidden="true"></i> </button></a>
                      <a title="Remove Supercleaner Status"> <button class="btn btn-primary btn-sm remove_supercleaner-<?php echo $users->id;?>" <?php echo ($users->is_super_cleaner == 0)? 'disabled': '';?> onclick="removeSupercleaner({{$users->id}})"><i class="fa fa-times" aria-hidden="true"></i> </button></a>
                      <a href="<?php echo url('/');?>/admin/cleaners/<?php echo $users->id;?>" title="Show Cleaner"><button class="btn btn-primary btn-sm"><i class="fa fa-eye" aria-hidden="true"></i> </button></a>
                      <a href="<?php echo url('/');?>/admin/cleaners/<?php echo $users->id;?>/edit" title="Edit Cleaner"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> </button></a>
                        <form method="POST" action="<?php echo url('/');?>/admin/cleaners/<?php echo $users->id;?>" accept-charset="UTF-8" style="display:inline">
                          {{ method_field('DELETE') }}
                          {{ csrf_field() }}
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete Cleaner" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                        </form>
                      </td>
                    </tr>
                    <?php $i++;
                      }
                    ?>
                  @endforeach
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection

<script type="text/javascript">
  function suspendUser(id = ''){
    $.ajax({
        type:'POST',
        url: "<?php echo url('/');?>/admin/cleaner/suspendCleaner",
        data:{'id':id,'_token':"<?php echo csrf_token();?>"},
        success:function(data) {
          $('.suspend_user-'+id).attr("disabled","disabled");
          $('.approve_user-'+id).prop("disabled",false);
          $('.user_status-'+id).text("Not Approved");
        }
    });
  }

  function approveUser(id = ''){
    $.ajax({
        type:'POST',
        url: "<?php echo url('/');?>/admin/cleaner/approveCleaner",
        data:{'id':id,'_token':"<?php echo csrf_token();?>"},
        success:function(data) {
          $('.approve_user-'+id).attr("disabled","disabled");
          $('.suspend_user-'+id).prop("disabled",false);
          $('.user_status-'+id).text("Approved");
        }
    });
  }

  function removeSupercleaner(id = ''){
    $.ajax({
        type:'POST',
        url: "<?php echo url('/');?>/admin/cleaner/removeSuperCleanerStatus",
        data:{'id':id,'_token':"<?php echo csrf_token();?>"},
        success:function(data) {
          $('.remove_supercleaner-'+id).attr("disabled","disabled");
        }
    });
  }
</script>