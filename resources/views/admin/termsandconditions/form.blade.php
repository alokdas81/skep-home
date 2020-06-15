<div class="box-body">
  <div class="form-group">
      <label for="exampleInputEmail1" >{{ 'Title' }}</label>
      <input class="form-control" required name="title" type="text" id="title" value="<?php echo (!empty($pages->title))?$pages->title:'';?>"/>
      {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="form-group">
      <label for="price">{{ 'Description' }}</label>
      <textarea class="form-control" id="summary-ckeditor" name="description" style="min-height: 300px;"><?php echo (!empty($pages->description))?$pages->description:'';?></textarea>
      {!! $errors->first('price', '<p class="help-block">:message</p>') !!}
  </div>
  <div class="box-footer">
    <input class="btn btn-primary" type="submit" value="<?php echo (!empty($submitButtonText)?$submitButtonText:'Save')?>">
    <a href="{{ url('/admin/termsandconditions') }}" title="Back"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button></a>
  </div>
</div>
<script src="https://cdn.ckeditor.com/ckeditor5/12.1.0/classic/ckeditor.js"></script>

<script>
ClassicEditor
   .create(document.querySelector('#summary-ckeditor'), {
       toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote'],
       heading: {
          options: [{
            model: 'paragraph',
            title: 'Paragraph',
            class: 'ck-heading_paragraph'
          },
          {
            model: 'heading1',
            view: 'h1',
            title: 'Heading 1',
            class: 'ck-heading_heading1'
          },
          {
            model: 'heading2',
            view: 'h2',
            title: 'Heading 2',
            class: 'ck-heading_heading2'
          },
          {
            model: 'heading3',
            view: 'h3',
            title: 'Heading 3',
            class: 'ck-heading_heading3'
          }
        ]
      }
    })
   .catch(error => {
       console.log(error);
   });
</script>
