<?php
$url_webview = "https://webview.cliqueretire.com.br/";
$currencySymbol = "R$";
?>

<div id='cliqueretire-box'></div>

<style>
  .ui-dialog { z-index: 9999 !important; margin-top: auto !important; top:auto; }

  .cr_box-cliqueretire{
  margin-bottom: 10px;
  margin-top: 20px;
  cursor: pointer;
  overflow: hidden;
  position: relative;
  border: 1px solid #0e3cdc;
  padding: 15px;
  width: 100%;
  box-sizing: border-box;
  border-radius: 10px;
  text-align: left;
  text-transform: none;
  }

  .cr_box-cliqueretire h4 {
  margin: 0px;
  font-size: 18px;
  font-weight: bold;
  line-height: 20px;
  }

  .cr_box-cliqueretire div.cr_box-item {
  margin-top: 15px;
  }

  .cr_box-cliqueretire .cr_cliqueretire-image {
  text-align: center;
  background: url('https://cliqueretiremail.s3.sa-east-1.amazonaws.com/mapa_v1_400.jpg');
  background-repeat: 'no-repeat';
  background-size: auto 100px;
  }

  .cr_box-cliqueretire p {
  margin-top: 2px;
  margin-bottom: 2px;
  font-family: "Helvetica Neue", Verdana, Arial, sans-serif;
  color: #636363;
  font-size: 13px;
  font-weight: 400;
  line-height: 1.5;
  /* line-height: 14px; */
  }

  #cr_buttonOpen{
  margin-top: 20px;
  margin-bottom: 20px;
  font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
  text-decoration: none;
  display: inline-block;
  line-height: 20px;
  text-align: center;
  vertical-align: middle;
  cursor: pointer;
  border: 1px solid #ccc;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);
  padding: 11px 19px;
  border-radius: 6px;
  color: #fff;
  text-shadow: 0 -1px 0 rgba(0,0,0,0.25);
  background-color: #5bb75b;
  background-image: linear-gradient(to bottom,#62c462,#51a351);
  background-repeat: repeat-x;
  border-color: rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);
  font-size: 16px;
  margin-top: 20px;
  margin-bottom: 20px;
  }

  .cr_box-cliqueretire .cr_box-tag {
  background: #0e3cdc;
  color: #fff;
  text-align: center;
  padding: 5px 0px;
  font-size: 12px;
  -webkit-transform: rotate(45deg);
  -khtml-transform: rotate(45deg);
  -moz-transform: rotate(45deg);
  -ms-transform: rotate(45deg);
  -o-transform: rotate(45deg);
  transform: rotate(45deg);
  filter: progid:DXImageTransform.Microsoft.Matrix(M11=0, M12=0, M21=0, M22=0, sizingMethod='auto expand');
  zoom: 1;
  position: absolute;
  right: 0px;
  top: 12px;
  z-index: 99;
  width: 170px;
  font-size: 11px;
  margin-right: -55px;
  margin-top: 5px;
  }

  #cr_cleanerLocker {
  border: white  1px solid;
  border-radius: .5vh;
  color: white;
  flex: 1;
  padding: .5vh;
  background-color: darkgrey;
  }

  #cr_buttonOpen {
  margin-top:20px;
  margin-bottom:20px;
  }

  #cr_dialog {
  outline: none;
  background-color: white;
  border: 1px solid #ccc;
  border-radius: 0.5vh;z-index:9999;
  }

  .cr_dialogTitle {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color:#0e3cdc;
  margin-bottom:5px;
  }

  #cr_textDialog {
  font-weight: 400;
  font-size: 16px;
  color: white;
  height: 100%;
  margin-left: 16px;
  }

  #cr_closeIframe {
  font-weight: 500;
  margin-right: 16px;
  background-color: #0e3cdc; border: none;
  font-size: 28px;
  color: white;
  outline: none;
  }

  #cr_iframe {
  background-color: white;
  width: 100%;
  outline: none;
  height: 600px;
  width: 100%
  }
  .fixDialog { max-width: 1000px !important; }
</style>

<script>
  var loadScript = (url, callback) => {
    var script = document.createElement('script')
    script.type = 'text/javascript'

    // If the browser is Internet Explorer.
    if (script.readyState) {
      script.onreadystatechange = function () {
        if (script.readyState === 'loaded' || script.readyState === 'complete') {
          script.onreadystatechange = null
          callback()
        }
      }
      // For any other browser.
    } else {
      script.onload = function () {
        callback()
      }
    }

    script.src = url
    document.getElementsByTagName('head')[0].appendChild(script)
  }
  var crBoxCliqueRetire = (locker) => `<div class="cr_box-cliqueretire">
      <h4>Receba fora de casa</h4>
      <div class="cr_box-item col-1-2">
      ${locker == null
          ? `<div class="box-item col-1-2 cr_cliqueretire-image">
          <a href="#" id="cr_buttonOpen">Escolher local para retirada</a>
          </div>`
          : `<p>Você escolheu retirar sua compra no e-box ${locker.name} (${locker.orderNo})</p>
          <p><b>Prazo de Entrega:</b> ${locker.shippingData.estimateDays} dias úteis</p>
          <p><b>Valor Frete:</b> <?php echo $currencySymbol; ?> ${locker.shippingData.absoluteValue.toFixed(2)}</p>

      <button id="cr_cleanerLocker">Limpar ou alterar o local</button>
      </div>`}
      <div class="box-item col-1-2">
        <div class="content">
          <div class="inner-content">
            <p>Você não precisa ter alguem em casa para receber.</p>
            <p>Utilize um e-Box da Clique Retire para retirar sua compra.</p>
            <p>
              <a href="#" id="cr_InfoOpen" class="click-como-funciona"> Como Funciona </a>
            </p>
            </div>
          <div class="cr_box-tag">Recomendado</div>
        </div>
      </div>
      </div>
      <div class="clearfix"></div>
      </div>`

  var crDialogTemplate = `
      <div id="cr_dialog">
          <div class="cr_dialogTitle">
              <span id="cr_textDialog">Clique Retire</span>
              <button id="cr_closeIframe">x</button>
          </div>
          <iframe
            id="cr_iframe"
            frameborder="0"
            marginheight="0" allowfullscreen></iframe>
      </div>`

  var UpdateCliqueRetire = (renderLayout, locker) => {
    jQuery('#cr_trButton').remove()
    jQuery('#cr_price').html('')
    if (!jQuery('#cr_trButton').length) { renderLayout(locker) }
  }

  var setItemToStorage = (name, data) => {
    if (data) {
      window.localStorage.setItem(
        name,
        JSON.stringify(data)
      )
    } else {
      window.localStorage.setItem(
        name,
        null
      )
    }
  }
  var setShippingAddress = (locker) => {
    let lastAddress = null
    if (locker) {
      lastAddress = {
        shippingFirstName: (document.getElementById('shipping_first_name').value.length>0) ? document.getElementById('shipping_first_name').value : (document.getElementById('billing_first_name').value.length>0) ? document.getElementById('billing_first_name').value : null,
        shippingLastName: (document.getElementById('shipping_last_name').value.length>0) ? document.getElementById('shipping_last_name').value : (document.getElementById('billing_last_name').value.length>0) ? document.getElementById('billing_last_name').value : null,
        shippingStreet1: document.getElementById('shipping_address_1').value,
        shippingStreet2: document.getElementById('shipping_address_2').value,
        shippingCity: document.getElementById('shipping_city').value,
        shippingRegion: document.getElementById('shipping_state').value,
        shippingPostCode: document.getElementById('shipping_postcode').value,
        shippingSameAsBilling: document.getElementById('ship-to-different-address-checkbox').checked
      }
      if (!document.getElementById('shipping_address_2').value.startsWith('CR0')) { setItemToStorage('cr_lastaddress', lastAddress) }
    } else {
      lastAddress = JSON.parse(window.localStorage.getItem('cr_lastaddress'))
    }

    document.getElementById('shipping_first_name').value = (locker) ? (lastAddress.shippingFirstName) ? lastAddress.shippingFirstName : null : (document.getElementById('shipping_first_name').value.length>0) ? document.getElementById('shipping_first_name').value : null
    document.getElementById('shipping_last_name').value = (locker) ? (lastAddress.shippingLastName) ? lastAddress.shippingLastName : null : (document.getElementById('shipping_last_name').value.length>0) ? document.getElementById('shipping_last_name').value : null
    
    document.getElementById('shipping_address_1').value = (locker) ? locker.location.street : lastAddress.shippingStreet1
    document.getElementById('shipping_address_2').value = (locker) ? locker.orderNo + ' ' + locker.location.reference : lastAddress.shippingStreet2
    document.getElementById('shipping_city').value = (locker) ? locker.location.city : lastAddress.shippingCity
    document.getElementById('shipping_state').value = (locker) ? locker.location.state : lastAddress.shippingRegion
    document.getElementById('shipping_postcode').value = (locker) ? locker.location.zip_code : lastAddress.shippingPostCode
    document.getElementById('ship-to-different-address-checkbox').checked = (locker) ? true : lastAddress.shippingSameAsBilling
    // jQuery('#ship-to-different-address-checkbox').click();
    jQuery('body').trigger('update_checkout');
  }


  var getShippingRates = async (zipCode) => {
    var url = `<?php echo $url_api; ?>shippingRates/${window.location.origin.replace(/(^\w+:|^)\/\//, '')}/${zipCode}`
    var res = await fetch(url)
    var data = await res.json()
    return data
  }


  var CRCheckout = async ($) => {
    let srcModal = null

    let locker = ($('#shipping_address_2').val().length > 0) ? $('#shipping_address_2').val().startsWith('CR0') ? JSON.parse(window.localStorage.getItem('cr_selectedLocker')) : null : null
    // let locker = null

    function renderLayout (locker) {
      // if (locker != null) { document.getElementById('cr_price').innerHTML = `<?php echo $currencySymbol; ?>  ${locker.shippingData.absoluteValue.toFixed(2)}` }
      $('#cliqueretire-box').append('<div id="cr_trButton"></div>')

      $('#cr_trButton').append(crBoxCliqueRetire(locker))

      $('#cr_InfoOpen').click(function (e) {
        e.preventDefault()
        srcModal = 'faq'
        !$('#cr_dialog').dialog('isOpen') ? $('#cr_dialog').dialog('open') : $('#cr_dialog').dialog('close')
      })

      $('#cr_buttonOpen').click(function (e) {
        e.preventDefault()
        srcModal = 'map?selectLocker=true'
        !$('#cr_dialog').dialog('isOpen') ? $('#cr_dialog').dialog('open') : $('#cr_dialog').dialog('close')
      })

      $('#cr_cleanerLocker').click(function (e) {
        e.preventDefault()
        UpdateCliqueRetire(renderLayout, null)
        window.localStorage.removeItem('cr_selectedLocker')
        setShippingAddress(null)
      })
    }

    if (!jQuery('#cr_dialog').length) {
      $('html').append(crDialogTemplate)
    }

    UpdateCliqueRetire(renderLayout, locker || null)
    console.log('check place order');
    if ($('#place_order').length > 0) {
        var shippingButton = $('#place_order')
        var shippingMethod = document.querySelectorAll('.shipping_method')
        let cliqueretireEnviaMethod = null
        shippingMethod.forEach(method => {
        if (method.id === 'shipping_method_0_ebox_cliqueretire') {
            method.checked = true
            cliqueretireEnviaMethod = method
        }
        })

        shippingButton.prop("disabled",true)
        setInterval(() => {
            var location = $('#cr_cleanerLocker').length
            var checkbox = $('#shipping_method_0_ebox_cliqueretire')
            if (location > 0 && cliqueretireEnviaMethod && checkbox.is(':checked') === false) {
                shippingButton.prop("disabled",true)
                jQuery('#cr_cleanerLocker').click()
            } else if (location > 0 && cliqueretireEnviaMethod && checkbox.is(':checked') === true) {
                shippingButton.prop("disabled",false)
            } else if (location === 0 && cliqueretireEnviaMethod && checkbox.is(':checked') === false) {
                shippingButton.prop("disabled",false)
            } else if (location === 0 && cliqueretireEnviaMethod && checkbox.is(':checked') === true) {
                shippingButton.prop("disabled",true)
            }
        }, 1000)
    }
    window.addEventListener('message', async (e) => {
      if (e.data.length > 0) {
        locker = JSON.parse(e.data)
        locker.shippingData = await getShippingRates(locker.location.zip_code)
        setItemToStorage('cr_selectedLocker', locker)
        UpdateCliqueRetire(renderLayout, locker)
        $('#cr_dialog').dialog('close')
        setShippingAddress(locker)
      }
    })
    $('.ui-dialog').attr('style', function(i,s) { return (s || '') + 'max-width: 1000px !important;max-height: 100vh !important;' });

    $('#cr_dialog').dialog({
        width: /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) ? 400 : 1000,
        autoOpen: false,
        // dialogClass: "cr_box-cliqueretire",
        modal: true,
        responsive: true,
      open: function () {

        $('#cr_dialog').css('display', 'absolute')
        $('#cr_textDialog').text(
            `${srcModal === 'faq' ? 'Saiba mais sobre a Clique Retire' : 'Selecione o melhor local para retirar seu pedido'}`
        )
        $('#cr_iframe').attr('src', '<?php echo $url_webview; ?>' + srcModal)
      },
      close: function () {
        $('#cr_dialog').dialog('close')
      }
    })

    $('.ui-dialog-titlebar').css('display', 'none')
    $('.ui-dialog').css('margin-top', '100px')

    $('#cr_closeIframe').click(function () {
      $('#cr_dialog').dialog('close')
    })
  }

  loadScript('https://code.jquery.com/ui/1.12.1/jquery-ui.min.js', () => {
    if (jQuery('#place_order').length > 0) CRCheckout(jQuery)
  })

</script>

