<div class="box-body">
  <div class="form-group {{ $errors->has('name') ? 'has-error' : ''}}">
    <label for="exampleInputEmail1" >{{ 'Booking Date' }}</label>
    <input class="form-control" required type="text" id="booking_date" name="booking_date" value="<?php echo (!empty($booking->booking_date))?$booking->booking_date:'';?>" <?php echo (!empty($booking->booking_date))?'disabled':'';?> />
    {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group {{ $errors->has('name') ? 'has-error' : ''}}">
    <label for="exampleInputEmail1" >{{ 'Service Start Time' }}</label>
    <input class="form-control" required name="service_start_time" type="text" id="service_start_time" value="<?php echo (!empty($booking->service_start_time))?$booking->service_start_time:'';?>" <?php echo (!empty($booking->service_start_time))?'disabled':'';?> />
    {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group {{ $errors->has('description') ? 'has-error' : ''}}">
    <label for="content" class="">{{ 'Service End Time' }}</label>
    <input class="form-control" required name="service_end_time" type="text" id="service_end_time" value="<?php echo (!empty($booking->service_start_time))?$booking->service_end_time:'';?>" <?php echo (!empty($booking->service_end_time))?'disabled':'';?> />
      {!! $errors->first('description', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group {{ $errors->has('description') ? 'has-error' : ''}}">
    <label for="content" class="">{{ 'Booking Price' }}</label>
    <input class="form-control" required name="booking_price" type="text" id="" value="<?php echo (!empty($booking->booking_price))?$booking->booking_price:'';?>" />
      {!! $errors->first('description', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group {{ $errors->has('description') ? 'has-error' : ''}}">
    <label for="content" class="">{{ 'Homeowner' }}</label>
    <select class="form-control" name="user_id" required id="user_id">
      <option value="">Select One</option>
      @foreach($users as $user)
        <option value="{{ $user->id }}" <?php echo (!empty($booking->user_id) && ($user->id == $booking->user_id))?'selected':'';?>>{{ $user->first_name }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group {{ $errors->has('description') ? 'has-error' : ''}}">
    <label for="content" class="">{{ 'Cleaner' }}</label>
    <select class="form-control" name="service_provider_id" required id="service_provider_id">
      <option value="">Select One</option>
      @foreach($cleaner as $cleaners)
        <option value="{{ $cleaners->id }}" <?php echo (!empty($booking->service_provider_id) && ($cleaners->id == $booking->service_provider_id))?'selected':'';?>>{{ $cleaners->first_name }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label for="price">{{ 'Extra Services' }}</label>
      <select class="form-control" name="extra_services[]" id="extra_services" mutiple="multiple" multiple="" tabindex="-1" aria-hidden="true">
          <?php $i = 1;?>
          @foreach($all_services as $extras)
            <option value="<?php echo $extras;?>" <?php echo (!empty($values) && in_array($extras,$values))?'selected':'';?>><?php echo $extras;?></option>
            <?php $i++;?>
          @endforeach
      </select>
  </div>
  <div class="form-group">
    <label for="price">{{ 'Myspace' }}</label>
      <select class="form-control" name="space_id" id="space_id" tabindex="-1" aria-hidden="true" required>
          <option>Select Space</option>
          @foreach($myspace_details as $myspace)
          <option value="{{ $myspace->id }}" user-id="{{ $myspace->user_id }}" <?php echo (!empty($booking->space_id) && ($booking->space_id == $myspace->id))? 'selected':'';?>>{{ $myspace->name }}</option>
          @endforeach
      </select>
  </div>
  <div class="form-group">
    <label for="price">{{ 'Special Instructions' }}</label>
      <textarea class="form-control" name="special_instructions" id="special_instructions"><?php echo (!empty($booking->special_instructions))?$booking->special_instructions:'';?></textarea> 
  </div>
</div>
<div class="box-footer">
    <input class="btn btn-primary" type="submit" value="<?php echo (!empty($submitButtonText)?$submitButtonText:'Save')?>">
     <a href="{{ url('/admin/categories') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
</div>
<style>
  li.select2-selection__choice{
    color:#000 !important;
  }
  .select2-results__option[aria-selected=true] {
    display: none;
  }
</style>