@extends('layouts.admin')
@section('content')

<section class="content-header">
  <h1>Cleaners Management</h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Edit Cleaners</h3>
        </div>
        <form role="form" method="POST" action="{{ url('/admin/cleaners/' . $user->id) }}" accept-charset="UTF-8"  enctype="multipart/form-data">
            {{ csrf_field() }}
            @if ($errors->any())
                <ul class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
         <form method="POST" action="{{ url('/admin/cleaners/' . $user->id) }}" accept-charset="UTF-8" class="" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            {{ csrf_field() }}
            @include ('admin.cleaners.form', ['submitButtonText' => 'Update'])       
        </form>
      </div>
    </div>
  </div>
</section>   

@endsection

