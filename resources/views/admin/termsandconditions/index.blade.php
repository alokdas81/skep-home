@extends('layouts.admin')

@section('content')
<!-- Content Header (Page header) -->
    <section class="content-header">
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
                <a style="float:right;" href="{{ url('/admin/termsandconditions/create') }}" class="btn btn-success btn-sm" title="Add New Category">
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
                    <th>Title</th>
                    <th>Description</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1;?>
                  @foreach($pages as $page)
                    <tr>
                      <td>{{ $i }}</td>
                      <td>{{ $page->title }}</td>
                      <?php $remove_tags = strip_tags($page->description);?>
                      <td><?php echo substr($remove_tags,0,25);?></td>
                      <td>
                        <a href="<?php echo url('/');?>/admin/termsandconditions/<?php echo $page->id;?>/edit" title="Edit Page"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> </button></a>
                        <form method="POST" action="<?php echo url('/');?>/admin/termsandconditions" accept-charset="UTF-8" style="display:inline">
                          {{ method_field('DELETE') }}
                          {{ csrf_field() }}
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete Page" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i>
                          </button>
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
