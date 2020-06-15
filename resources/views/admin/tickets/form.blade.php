<div class="box-body">
    <div class="form-group">
        <label for="exampleInputEmail1" >{{ 'Email' }}</label>
          <input class="form-control" required name="email" type="email" id="email" value="<?php echo (!empty($tickets[0]->email))?$tickets[0]->email:'';?>" <?php echo (!empty($tickets[0]->email))?'disabled':'';?>>
            {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
        <label for="price">{{ 'User Type' }}</label>
         <input required class="form-control" name="user_type" type="text" id="user_type" value="<?php echo (!empty($tickets[0]->user_type))?$tickets[0]->user_type:'';?>" <?php echo (!empty($tickets[0]->user_type))?'disabled':'';?>>
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
      <label for="price">{{ 'Title' }}</label>
        <input required class="form-control" name="title" type="text" id="title" value="<?php echo (!empty($tickets[0]->title))?$tickets[0]->title:'';?>" >
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
    <div class="form-group">
      <label for="price">{{ 'Description' }}</label>
        <textarea class="form-control" name="description" id="description"><?php echo $tickets[0]->description;?></textarea>
        {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
    </div>
</div>
<div class="box-footer">
    <input class="btn btn-primary" type="submit" value="<?php echo (!empty($submitButtonText)?$submitButtonText:'Save')?>">
     <a href="{{ url('/admin/tickets') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
</div>