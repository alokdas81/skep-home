(function ($) {
  'use strict';

  $(document).ready(function () {
    // Shop Slider
    var shopSlider = $('#shopSlider');
    if (shopSlider.length > 0) {
      shopSlider.owlCarousel({
        loop: true,
        margin: 0,
        items: 6,
        nav: true,
        dots: false
      })
    }

    //Fixed Header
    $(function () {
      var header = $("header");
      $(window).scroll(function () {
        var scroll = $(window).scrollTop();

        if (scroll >= 500) {
          header.addClass("animated fadeInDown fixed");
        } else {
          header.removeClass("animated fadeInDown fixed");
        }
      });
    });


    //File Upload
    $(function () {
      $('body').find("input[type='file']").on('change', function () {
        var fileName = $(this).val();
        // if (fileName.length > 0) {
        //   $(this).parent().children('span').html(fileName);
        // } else {
        //   $(this).parent().children('span').html("Add");
        // }
      });
      //file input preview
      function readURL(input) {
        if (input.files && input.files[0]) {
          var reader = new FileReader();
          reader.onload = function (e) {
            $('body').find('.logoContainer img').attr('src', e.target.result);
          }
          reader.readAsDataURL(input.files[0]);
        }
      }
      $('body').find("input[type='file']").on('change', function () {
        readURL(this);
      });
    });

    // Custom Scroll
    var ChatBox = $('.chat-box-sec');
    if (ChatBox.length > 0) {
      ChatBox.mCustomScrollbar({
        axis: "y"
      });
    }

    //Check Number
    var inputTel = $("body").find('input[type="tel"]');
    if (inputTel.length > 0) {
      var inputTelField = $('#tel'),
        inputTelField2 = $('#tel2');
      inputTelField.mobilePhoneNumber({
        allowPhoneWithoutPrefix: '+1'
      });
      inputTelField2.mobilePhoneNumber({
        allowPhoneWithoutPrefix: '+1'
      });

    }

    
    // Custom popup  
    $(function () {
      //----- OPEN
      $('[pd-popup-open]').on('click', function (e) {
        var targeted_popup_class = jQuery(this).attr('pd-popup-open');
        $('[pd-popup="' + targeted_popup_class + '"]').fadeIn(100);
        $("body").addClass("open");
        e.preventDefault();
      });

      //----- CLOSE
      $('[pd-popup-close]').on('click', function (e) {
        var targeted_popup_class = jQuery(this).attr('pd-popup-close');
        $('[pd-popup="' + targeted_popup_class + '"]').fadeOut(200);
        $("body").removeClass("open");
        e.preventDefault();
      });
    });

    //Avoid pinch zoom on iOS
    document.addEventListener('touchmove', function (event) {
      if (event.scale !== 1) {
        event.preventDefault();
      }
    }, false);
  });
})(jQuery)