@extends('layouts.admin')
@section('content')

<section class="content-header">
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box box-primary">
        <form role="form" method="POST" action="{{ url('/admin/termsandconditions/' . $pages->id) }}" accept-charset="UTF-8"  enctype="multipart/form-data">
            {{ csrf_field() }}
            @if ($errors->any())
                <ul class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
         <form method="POST" action="{{ url('/admin/termsandconditions/' . $pages->id) }}" accept-charset="UTF-8" class="" enctype="multipart/form-data">
            {{ method_field('PATCH') }}

            {{ csrf_field() }}

            @include ('admin.termsandconditions.form', ['submitButtonText' => 'Update'])       
        </form>
      </div>
    </div>
  </div>
</section>   
@endsection

