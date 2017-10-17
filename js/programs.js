jQuery.noConflict();
(function( $ ) {
  var methods = {
    showInput: function (e) {
      $(e).attr('type', 'number').focus();
    },
    hideInput: function (e) {
      $(e).attr('type', 'hidden');
    },
    showNotice: function (el, text, isError) {
      var visibleTime = 1500;
      if(isError) {
        el.css('background', '#F08080');
        visibleTime = 2500;
      }
      el.text(text);
      el.fadeIn();
      setTimeout(function () {
        el.fadeOut();
        el.removeAttr('style');
      }, visibleTime)
    },
    checkLength: function (e) {
      console.log($(e).find('input:checked').length);
      return $(e).find('input:checked').length;
    }
  };

  var elementsOnMain = {
    robots: 0,
    english: 0
  };
  $(document).ready(function () {
    var priceColumn = $('.ids_price');
    var priceInput = priceColumn.find('input[name="price"]');
    var notice = $('.ids_notice');
    var isMainCheckBox = $('input[name="is_main"]');
    var roboticsOnMain = $('td[data-cat="Робототехника"]');
    var englishOnMain = $('td[data-cat="Английский язык"]');

    elementsOnMain.robots = methods.checkLength(roboticsOnMain);
    elementsOnMain.english = methods.checkLength(englishOnMain);

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

    function checkboxHandler (element, isChecked) {
      var catName = $(element).parent().attr('data-cat');
      console.log(catName);
      var errMsg = 'Нельзя показывать больше трех курсов из одной категории на главной';
      function setCheckbox(e) {
        if (isChecked) {
          $(e).attr('checked', true);
        }

        if (!isChecked) {
          $(e).attr('checked', false);
        }
      }

      if (catName === 'Робототехника') {
        if (elementsOnMain.robots >= 3 && !isChecked) {
          setCheckbox(element);
          methods.showNotice(notice, errMsg, true);
          return false
        } else {
          elementsOnMain.robots = methods.checkLength(roboticsOnMain);
        }
      }

      if (catName === 'Английский язык' && !isChecked) {
        if (elementsOnMain.english >= 3) {
          setCheckbox(element);
          methods.showNotice(notice, errMsg, true);
          return false
        } else {
          elementsOnMain.english = methods.checkLength(englishOnMain);
        }
      }

      console.log(elementsOnMain, isChecked);
      return true
    }

    isMainCheckBox.change(function () {
      var isChecked = $(this).is(':checked');

      if (!checkboxHandler($(this), !isChecked)) {
        return
      }

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