<div class="box-body">
  <div class="form-group">
      <label for="exampleInputEmail1" >{{ 'Name' }}</label>
        <input class="form-control" required name="service_name" type="text" id="name" value="<?php echo (!empty($user->name))?$user->name:'';?>" />
          {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group">
      <label for="price">{{ 'Time Duration' }}</label>
       <input required class="form-control" name="time" type="text" id="time" value="<?php echo (!empty($user->time))?$user->time:'';?>" />
      {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group">
    <label for="price">{{ 'Price' }}</label>
      <input required class="form-control" name="price" type="text" id="price" value="<?php echo (!empty($user->price))?$user->price:'';?>" />
      {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group">
    <label for="price">{{ 'Selected Image' }}</label>
      <input class="form-control" name="image" type="file" id="service_image" required/>
      <img src="" id="profile-img-tag" width="50px" height="50px" />
      <?php if(!empty($user->extra_service_image)){?>
        <img class="upload_image" src="<?php echo $user->extra_service_image; ?>" height="50px;" width="50px;" style="margin-top: 20px; margin-left:10px;" />
     <?php }?>
  </div>
  <div class="form-group">
    <label for="price">{{ 'Unselected Image' }}</label>
      <input class="form-control" name="unselected_image" type="file" id="unselected_image" required/>
      <img src="" id="unselected-img-tag" width="50px" height="50px" />
      <?php if(!empty($user->extra_service_unselectedimage)){?>
        <img class="unselected_image" src="<?php echo $user->extra_service_unselectedimage; ?>" height="50px;" width="50px;" style="margin-top: 20px; margin-left:10px;" />
     <?php }?>
  </div>
  <div class="box-footer">
    <input class="btn btn-primary" type="submit" value="<?php echo (!empty($submitButtonText)?$submitButtonText:'Save')?>">
     <a href="{{ url('/admin/extraservices') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
  </div>
</div>