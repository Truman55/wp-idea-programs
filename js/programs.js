jQuery.noConflict();
(function( $ ) {
  var methods = {
    showInput: function (e) {
      $(e).attr('type', 'number').focus();
    },
    hideInput: function (e) {
      $(e).attr('type', 'hidden');
    },
    showNotice: function (el, text) {
      el.text(text);
      el.fadeIn();
      setTimeout(function () {
        el.fadeOut()
      }, 1500)
    }
  };
  $(document).ready(function () {
    var priceColumn = $('.ids_price');
    var priceInput = priceColumn.find('input[name="price"]');
    var notice = $('.ids_notice');
    var isMainCheckBox = $('input[name="is_main"]');

    priceColumn.click(function () {
      methods.showInput($(this).find('input[name="price"]'));
    });

    priceInput.mouseleave(function () {
      var staticPrice = $(this).parent().find('span');
      var data = {
        price: $(this).val(),
        course_id: $(this).prev().val()
      };

      staticPrice.text('Цена обновляется...');
      methods.hideInput($(this));

      $.ajax({
        type: 'POST',
        url: ajaxurl,
        datatype: 'json',
        data: {
          formData: data,
          action: 'ids_update_price'
        },
        success:function (response) {
          staticPrice.text(data.price);
          methods.showNotice(notice, response.result);
        },
        error: function (e) {
          alert(e);
        }
      })
    });

    isMainCheckBox.change(function () {
      var isChecked = $(this).is(':checked');

      var data = {
        isChecked: isChecked ? 1 : 0,
        course_id: $(this).parents('tr').find('input[name="course_id"]').val()
      };
      $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
          data: data,
          action: 'ids_update_property_main'
        },
        success: function (response) {
          methods.showNotice(notice, response.result)
        },
        error: function (e) {
          alert(e)
        }
      })
    })
  });
})(jQuery);