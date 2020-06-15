<div class="box-body">
    <div class="form-group">
        <label for="exampleInputEmail1" >{{ 'Waiting Time(in sec)' }}</label>
          <input type="hidden" name="id" value="<?php echo (!empty($waiting_time->id))?$waiting_time->id:'';?>">
          <input class="form-control" required name="waiting_time" type="number" id="waiting_time" value="<?php echo (!empty($waiting_time->waiting_time))?$waiting_time->waiting_time:'';?>">
    </div>
    <div class="form-group">
        <label for="price">{{ 'Waiting Time For' }}</label>
         <input required class="form-control" name="waiting_for" type="text" id="waiting_for" value="<?php echo (!empty($waiting_time->waiting_time_for))?$waiting_time->waiting_time_for:'';?>" >
    </div>
</div>
<div class="box-footer">
    <input class="btn btn-primary" type="submit" value="<?php echo (!empty($submitButtonText)?$submitButtonText:'Save')?>">
     <a href="{{ url('/admin/waitingtime') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
</div>