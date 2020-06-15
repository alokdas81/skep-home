@extends('layouts.admin')

@section('content')
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Tickets Management
      </h1>
    </section>
    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">All Tickets</h3>
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
                    <th>Ticket ID</th>
                    <th>Email</th>
                    <th>Job ID</th>
                    <th>User type</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1;?>
                  @foreach($tickets as $ticket)
                    <tr>
                      <td>{{ $i }}</td>
                      <?php if(!empty($ticket->status == 1)){?>
                        <td><span class="open_ticket_status" id="open_ticket_span-<?php echo $ticket->id;?>" style="display:block;">{{ $ticket->ticket_number }}</span>
                        <span class="closed_ticket_status" id="close_ticket_span-<?php echo $ticket->id;?>" style="display:none;">{{ $ticket->ticket_number }}</span></td>
                      <?php } else{ ?>
                        <td><span class="closed_ticket_status" id="close_ticket_span-<?php echo $ticket->id;?>" style="display:block;">{{ $ticket->ticket_number }}</span>
                        <span class="open_ticket_status" id="open_ticket_span-<?php echo $ticket->id;?>" style="display:none;">{{ $ticket->ticket_number }}</span></td>
                      <?php }?>
                      <td>{{ $ticket->email }}</td>
                      <td>{{ $ticket->job_id }}</td>
                      <td>{{ $ticket->user_type }}</td>
                      <td>{{ $ticket->title }}</td>
                      <?php if(!empty($ticket->status == 1)){?>
                        <td><button class="closed_ticket_status" id="close_ticket-<?php echo $ticket->id;?>" onclick="closeTicket({{$ticket->id}})" style="display:block;"><?php echo 'closed';?></button>
                        <button class="open_ticket_status" id="open_ticket-<?php echo $ticket->id;?>"onclick="openTicket({{$ticket->id}})" style="display:none;"><?php echo 'open';?></button></td>
                      <?php } else{?>
                        <td><button class="closed_ticket_status" id="close_ticket-<?php echo $ticket->id;?>" onclick="closeTicket({{$ticket->id}})" style="display:none;"><?php echo 'closed';?></button>
                        <button class="open_ticket_status" id="open_ticket-<?php echo $ticket->id;?>"onclick="openTicket({{$ticket->id}})" style="display:block;"><?php echo 'open';?></button></td>
                      <?php }?>
                      <td>
                        <a href="<?php echo url('/');?>/admin/tickets/<?php echo $ticket->id;?>" title="View Tickets"><button class="btn btn-primary btn-sm"><i class="fa fa-eye" aria-hidden="true"></i> </button></a>
                        <a href="<?php echo url('/');?>/admin/tickets/<?php echo $ticket->id;?>/edit" title="Edit Tickets"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> </button></a>
                        <form method="POST" action="<?php echo url('/');?>/admin/tickets/<?php echo $ticket->id;?>" accept-charset="UTF-8" style="display:inline">
                          {{ method_field('DELETE') }}
                          {{ csrf_field() }}
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete User" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
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
<script type="text/javascript">
  function closeTicket(id = ''){
    $.ajax({
        type:'POST',
        url: "<?php echo url('/');?>/admin/tickets/closeTicketStatus",
        data:{'id':id,'_token':"<?php echo csrf_token();?>"},
        success:function(data) {
          $('#open_ticket-'+id).css('display','block');
          $('#close_ticket-'+id).css('display','none');
          $('#close_ticket_span-'+id).css('display','block');
          $('#open_ticket_span-'+id).css('display','none');
          
        }
    });
  }

  function openTicket(id = ''){
    $.ajax({
        type:'POST',
        url: "<?php echo url('/');?>/admin/tickets/openTicketStatus",
        data:{'id':id,'_token':"<?php echo csrf_token();?>"},
        success:function(data) {
          $('#close_ticket-'+id).css('display','block');
          $('#open_ticket-'+id).css('display','none');
          $('#open_ticket_span-'+id).css('display','block');
          $('#close_ticket_span-'+id).css('display','none');
        }
    });
  }
</script>
