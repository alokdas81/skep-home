<div class="box-body">
    <div class="form-group">
        <label for="exampleInputEmail1" >{{ 'Email' }}</label>
          <input class="form-control" required name="email" type="email" id="email" value="<?php echo (!empty($user->email))?$user->email:'';?>" <?php echo (!empty($user->email))?'disabled':'';?>>
            {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
        <label for="price">{{ 'First Name' }}</label>
         <input required class="form-control" name="first_name" type="text" id="first_name" value="<?php echo (!empty($user->first_name))?$user->first_name:'';?>" >
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
      <label for="price">{{ 'Last Name' }}</label>
        <input required class="form-control" name="last_name" type="text" id="last_name" value="<?php echo (!empty($user->last_name))?$user->last_name:'';?>" >
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
      <label for="price">{{ 'Phone Number' }}</label>
        <input required class="form-control" name="phone_number" type="number" id="phone_number" value="<?php echo (!empty($user->phone_number))?$user->phone_number:'';?>" >
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
      <label for="price">{{ 'Gender' }}</label>
        <select class="form-control" name="gender" id = "gender">
          <option value="">Select Gender</option>
          <option value="male" <?php echo (!empty($user->gender) && $user->gender == 'male')?'selected':'';?>>Male</option>
          <option value="female" <?php echo (!empty($user->gender) && $user->gender == 'female')?'selected':'';?>>Female</option>
        </select>
    </div>
    <div class="form-group">
      <label for="price">{{ 'Address' }}</label>
        <input required class="form-control" name="address" type="text" id="address" value="<?php echo (!empty($user->address))?$user->address:'';?>" >
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
      <label for="dob">{{ 'Date Of Birth' }}</label>
        <input class="form-control" name="date_of_birth" type="text" id="date_of_birth" value="<?php echo (!empty($user->date_of_birth))?$user->date_of_birth:'';?>" required>
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
        <label for="price">{{ 'Password' }}</label>
         <input class="form-control" name="password" type="password" id="password" value="" <?php echo (empty($user->password))?'required':'';?>>
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div> 
    <div class="form-group">
        <label for="price">{{ 'Profile Pic' }}</label>
        <input type="file" name="profile_pic">
    </div> 
</div>
<div class="box-footer">
    <input class="btn btn-primary" type="submit" value="<?php echo (!empty($submitButtonText)?$submitButtonText:'Save')?>">
     <a href="{{ url('/admin/users') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
</div>