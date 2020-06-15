<div class="box-body">
  <div class="form-group">
      <label for="exampleInputEmail1" >{{ 'Name' }}</label>
        <input class="form-control" required name="name" type="text" id="name" value="<?php echo (!empty($basicservice->name))?$basicservice->name:'';?>" />
          {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group">
      <label for="price">{{ 'Time Duration' }}</label>
       <input required class="form-control" name="time" type="text" id="time" value="<?php echo (!empty($basicservice->time))?$basicservice->time:'';?>" />
      {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group">
    <label for="price">{{ 'Price' }}</label>
      <input required class="form-control" name="price" type="text" id="price" value="<?php echo (!empty($basicservice->price))?$basicservice->price:'';?>" />
      {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="box-footer">
    <input class="btn btn-primary" type="submit" value="<?php echo (!empty($submitButtonText)?$submitButtonText:'Save')?>">
     <a href="{{ url('/admin/basicservices') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
  </div>
</div>