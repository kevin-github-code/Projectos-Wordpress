var cp_token = cincopa_cp_mt_options.api_token;
var authorize_url = cincopa_cp_mt_options.authorize_url;
var bakery_mode = cincopa_cp_mt_options.wp_bakery_build ? true : false;
var elementor_mode = cincopa_cp_mt_options.wp_elementor_build ? true : false;
var cincopaGallery = {
  list_filled: false,
  gallery_block: false,
  cp_user_status :false,
  cp_doing_ajax_reqiest:false,

  init: function () {
    cincopaGallery.gallery_block = jQuery('.cincopa-gallery-block');    
    window.addEventListener("message", receiveMessage, false);

  function receiveMessage(event){  
    if(event.data && event.data.sender == 'cincopa-assets-iframe'){
      if(event.data.action == 'insertedItem' || event.data.action == 'insertedGallery'){     
        cincopaGallery.select(event.data);
      }
    }
  }
  },
  is_visible: function () {
    return cincopaGallery.gallery_block.hasClass('cincopa-gallery-block-visible');
  },
  _show: function () {
    cincopaGallery.gallery_block.addClass('cincopa-gallery-block-visible');
    cincopaGallery.insertButton.parent().addClass('pressed');
    var position = cincopaGallery.insertButton.offset();
    cincopaGallery.gallery_block.offset({ left: position.left - 25, top: position.top + 23 });
  },
  toggle: function () {
    if (cincopaGallery.is_visible())
      cincopaGallery.hide();
    else
      cincopaGallery.show();
  },
  show: function () {
    if (!cincopaGallery.list_filled)
      cincopaGallery.fill();
    else
      cincopaGallery._show();
  },
  hide: function () {
    cincopaGallery.gallery_block.removeClass('cincopa-gallery-block-visible');
    jQuery('.vc_ui-panel-content-container').removeClass('cincopa-iframe-visible');
    cincopaGallery.gallery_block.removeAttr('style');

    jQuery('#cincopa_button').removeClass('pressed');
  },
  select: function (data) {
    var idToEmbed;
    var defaults=  data.defaults;
    if(data.action == 'insertedGallery'){
      idToEmbed = data.fid;
    } else {
      idToEmbed = defaults[data.item.type] + '!' + data.item.rid
    }
    cincopaGallery.hide();

    var elementorGalleryFid = jQuery('.elementor-control-cincopa_gallery_fid textarea');
    var oxygenGalleryFid = cincopaGallery.insertButton.parents('div.ng-scope').find('.oxygen-oxy-cincopa-oxy-cincopa_fid input');
    
    if(elementor_mode && !jQuery('#cincopa_button').length){

      elementorGalleryFid.val(idToEmbed).trigger('input');

    }else if(jQuery('.cincopa-input-insert').length){

      jQuery('.cincopa-input-insert').val(idToEmbed);

    }else if(oxygenGalleryFid.length){

      oxygenGalleryFid.val(idToEmbed).trigger('change');

    }else{

      top.send_to_editor("[cincopa " + idToEmbed + "]");

    }
  },
  _show_contents: function (data) {
    jQuery('.wp-media-buttons img').remove();

    //-------------New Code--------------------
    var pickerIframe = jQuery('<iframe class="cp-library-editor" allow="microphone *; camera *; display-capture *" src="https://www.cincopa.com/media-platform/api/library-editor.aspx?disable_editor=y&api_token=' + cp_token + '" />')
    var login = '<div class="cincopa-login"><a href="https://www.cincopa.com/login.aspx" target="_blank">Login</a> or <a href="https://www.cincopa.com/register.aspx" target="_blank">Register</a> to Cincopa to see this list</div>';
    
    /* not logged in user */
    if (!data.success) {      
      cincopaGallery.gallery_block.html(login);
      cincopaGallery._show();
    } else {
      if(cincopaGallery.gallery_block.find('.cp-library-editor').length){
        pickerIframe.toggleClass('cp-library-editor--visible');
      }else{
        cincopaGallery.gallery_block.html(pickerIframe);
        pickerIframe.addClass('cp-library-editor--visible');
      }
    }
    //---------------End New Code----------------

  },
  _client_fill: function (data) {
    if (data) {
      cincopaGallery._show_contents(data);
      return;
    }
    var userStatusTimer;
    userStatusTimer = setInterval(function() {
      checkStatus();
    }, 2000);
    checkStatus();

    function checkStatus(){
        cincopaGallery.checkLoginStatus({
          success: function (data) {
            if(cincopaGallery.cp_user_status){
              clearInterval(userStatusTimer)
            }
            cincopaGallery._client_fill(data);
          },
          type: 'POST',
          dataType: 'json'
      });
    }

  },
  fill: function () {
    cincopaGallery._show();
    cincopaGallery._client_fill();
  },
  checkLoginStatus: function(options){
    if (cincopaGallery.cp_user_status) {
      if (typeof options.success === "function") {
        setTimeout(function () {
          options.success({ success: true })
        },0)

      }
      return;
    }
    if (cincopaGallery.cp_doing_ajax_reqiest) {
      if (typeof options.error === "function") {
        setTimeout(function () {
          options.success({ success: false })
        },0);
      }
      return;
    }

    cincopaGallery.cp_doing_ajax_reqiest = true;
    jQuery.ajax({
      url: 'https://api.cincopa.com/v2/ping.json?api_token=' + cp_token,
      type: options.type ? options.type : 'GET',
      dataType: options.dataType ? options.dataType : 'json',
      success: function (data) {
        if (data.success) {
          cincopaGallery.cp_user_status = true;
        } else {
          cincopaGallery.cp_user_status = false;
        }
        if (typeof options.success === "function") {
          options.success(data)
        }
      },
      error: function (err) {
        cincopaGallery.cp_user_status = false;
        if (typeof options.error === "function") {
          options.error(err)
        }
      },
      complete: function () {
        cincopaGallery.cp_doing_ajax_reqiest = false;
      }
    })
  }
};

jQuery(document).ready(function () {

  jQuery(document).on('click', '.cp-show-library', function () { 
      cincopaGallery.gallery_block = jQuery(this).next('.cincopa-gallery-block');
      cincopaGallery.insertButton = jQuery(this);
      cincopaGallery.toggle(); 
  });  

  jQuery(document).on('click', '.elementor-control-insert_cincopa button', function () { 
      jQuery(this).after('<div class="cincopa-gallery-block" />');
      cincopaGallery.gallery_block = jQuery(this).next('.cincopa-gallery-block');
      cincopaGallery.insertButton = jQuery(this);
      cincopaGallery.toggle(); 
  });
  
  jQuery(document).on('click', '.elementor-control-login_cincopa button', function () { 
    window.location.href = authorize_url;
  });

  jQuery(document).on('click', '.btn_insert_from_cincopa', function () {     
    jQuery(this).after('<div class="cincopa-gallery-block" />');
    cincopaGallery.gallery_block = jQuery(this).next('.cincopa-gallery-block');
    cincopaGallery.insertButton = jQuery(this);
    cincopaGallery.toggle(); 

    if (jQuery('.cincopa-gallery-block').hasClass('cincopa-gallery-block-visible')){
      jQuery(this).parents('.vc_ui-panel-content-container').addClass('cincopa-iframe-visible');
    }
  })

  jQuery(document).on('click', '.btn_login_cincopa', function () { 
    window.location.href = authorize_url;
  });

  jQuery(document).on('click', '.oxygen-oxy-cincopa-oxy-cincopa_insert_btn', function () { 
    jQuery(this).after('<div class="cincopa-gallery-block" />');
    cincopaGallery.gallery_block = jQuery(this).next('.cincopa-gallery-block');
    cincopaGallery.insertButton = jQuery(this);
    cincopaGallery.toggle(); 
  });

  jQuery(document).on('click', '.oxygen-oxy-cincopa-oxy-cincopa_login_btn', function () { 
    window.location.href = authorize_url;
  });

  jQuery('body').on('click', doGalleryClick);

})



function doGalleryClick(event) {
  if (jQuery(event.target).closest('#cincopa_button').length == 0){
    cincopaGallery.hide();
    return;
  }
}
jQuery(function () {
  cincopaGallery.init();
});