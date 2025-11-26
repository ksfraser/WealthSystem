
function ast_var_header_click(symbol,market)
{
    $('var_tip').style.display = 'none';
    $('histogram_image').src = 'ast?process=var-histogram-image&period=daily&symbol=' + symbol + '&market=' + market;
}

function ast_update_riskchart(symbol,market)
{
    $('riskchart').src = 'ast?process=risk-chart&symbol=' + symbol + '&market=' + market;
}

function awf_hide_infobar(name, field)
{
  $(field).style.display = 'none';
  var update = new Ajax.Updater( $(field), 'ast', {method: 'post', parameters: 'process=infobar&name=' + name} );
}

function ast_alert_change()
{
  var alert_type = $('alert_type').value;
  $('alert_form').innerHTML = '<b>Loading...</b>';
  var update = new Ajax.Updater( 'alert_form', 'ast', {method: 'post', parameters: 'process=alerts&action=ajax-append&awf_setvar=true&alert.type=' + alert_type + $('_filter').value } );
}


function ast_popup(url,w,h)
{
  window.open(url, '', 'toolbar=1,scrollbars=0,location=0,statusbar=1,menubar=1,resizable=1,width='+w+',height='+h);
}

function ast_chart_over(price, opn, hi, lo, vol, cur, date)
{
  $("ast_chart_info").innerHTML = 'Date:&nbsp;' + date 
  + '&nbsp;&nbsp;Close:&nbsp;$ ' + price
  + '&nbsp;&nbsp;Open:&nbsp;$ ' + opn
  + '&nbsp;&nbsp;High:&nbsp;$ ' + hi
  + '&nbsp;&nbsp;Low:&nbsp;$ ' + lo
  + '&nbsp;&nbsp;Volume:&nbsp;' + vol
  + '&nbsp;&nbsp;Change to Current:&nbsp;' + (100*(cur-price)/price).toFixed(2) + '&nbsp;%';
}

function ast_chart_more_options()
{
  chart_form.more_options.value='true';
  $('options').style.display='inline';
  $('more').style.display='none';
  $('chart1').style.display='none';
}

function ast_comparison_show_form(frm_name)
{
  $('form1').style.display='none';
  $('form2').style.display='none';
  $('form3').style.display='none';
  $('form4').style.display='none';
  $(frm_name).style.display='inline';
}

var ast_last_focus;

function ast_save_focus(f)
{
  ast_last_focus = f;
}

function ast_get_focus()
{
  return ast_last_focus;
}

function ast_append(f1,f2)
{
  f1.value += f2.value;
  f1.focus();
  f1.setSelectionRange(f1.value.length-1,0);
}

function ws_splitdata10(a1,a2,a3,a4,a5,a6,a7,a8,a9,a10)
{
  var ar = document.getElementsByName(a1)[0].value.split('\t');
  if( ar.length >= 1 )
    document.getElementsByName(a1)[0].value = ar[0];
  if( ar.length >= 2 )
    document.getElementsByName(a2)[0].value = ar[1];
  if( ar.length >= 3 )
    document.getElementsByName(a3)[0].value = ar[2];
  if( ar.length >= 4 )
    document.getElementsByName(a4)[0].value = ar[3];
  if( ar.length() >= 5)
    document.getElementsByName(a5)[0].value = ar[4];
  if( ar.length() >= 6)
    document.getElementsByName(a6)[0].value = ar[5];
  if( ar.length() >= 7)
    document.getElementsByName(a7)[0].value = ar[6];
  if( ar.length() >= 8)
    document.getElementsByName(a8)[0].value = ar[7];
  if( ar.length() >= 9)
    document.getElementsByName(a9)[0].value = ar[8];
  if( ar.length() >= 10)
    document.getElementsByName(a10)[0].value = ar[9];
}

function ws_splitdata9(a1,a2,a3,a4,a5,a6,a7,a8,a9)
{
  var ar = a1.value.split('\t');
  if( ar.length >= 1 )
    a1.value = ar[0];
  if( ar.length >= 2 )
    a2.value = ar[1];
  if( ar.length >= 3 )
    a3.value = ar[2];
  if( ar.length >= 4 )
    a4.value = ar[3];
  if( ar.length() >= 5)
    a5.value = ar[4];
  if( ar.length() >= 6)
    a6.value = ar[5];
  if( ar.length() >= 7)
    a7.value = ar[6];
  if( ar.length() >= 8)
    a8.value = ar[7];
  if( ar.length() >= 9)
    a9.value = ar[8];
}

function sysset_on_change_auth_method()
{
  var val = authform.auth_method.value;
  if( val == 'internal' )
  {
    $('ast_ag1').style = 'display: none';
    $('ast_ag2').style = 'display: none';
    $('ast_ag3').style = 'display: none';
    $('ast_ag4').style = 'display: none';
    $('ast_ag5').style = 'display: none';
    $('ast_ag6').style = 'display: none';
    $('ast_ag7').style = 'display: none';
    $('ast_ag8').style = 'display: none';
    $('ast_ag9').style = 'display: none';
    $('ast_ag10').style = 'display: none';
    $('ast_ag11').style = 'display: none';
    $('ast_ag12').style = 'display: none';
    $('ast_ag13').style = 'display: none';
    $('ast_ag14').style = 'display: none';
  }else if( val == 'sql' )
  {
    $('ast_ag1').style = '';
    $('ast_ag2').style = '';
    $('ast_ag3').style = '';
    $('ast_ag4').style = '';
    $('ast_ag5').style = '';
    $('ast_ag6').style = '';
    $('ast_ag7').style = '';
    $('ast_ag8').style = '';
    $('ast_ag9').style = '';
    $('ast_ag10').style = '';
    $('ast_ag11').style = '';
    $('ast_ag12').style = '';
    $('ast_ag13').style = '';
    $('ast_ag14').style = '';
  }else if( val == 'joomla' )
  {
    $('ast_ag1').style = 'display: none';
    $('ast_ag2').style = 'display: none';
    $('ast_ag3').style = 'display: none';
    $('ast_ag4').style = 'display: none';
    $('ast_ag5').style = '';
    $('ast_ag6').style = '';
    $('ast_ag7').style = 'display: none';
    $('ast_ag8').style = 'display: none';
    $('ast_ag9').style = 'display: none';
    $('ast_ag10').style = 'display: none';
    $('ast_ag11').style = 'display: none';
    $('ast_ag12').style = 'display: none';
    $('ast_ag13').style = 'display: none';
    $('ast_ag14').style = 'display: none';
  }else if( val == 'pligg' )
  {
    $('ast_ag1').style = 'display: none';
    $('ast_ag2').style = 'display: none';
    $('ast_ag3').style = 'display: none';
    $('ast_ag4').style = 'display: none';
    $('ast_ag5').style = '';
    $('ast_ag6').style = '';
    $('ast_ag7').style = 'display: none';
    $('ast_ag8').style = 'display: none';
    $('ast_ag9').style = 'display: none';
    $('ast_ag10').style = 'display: none';
    $('ast_ag11').style = 'display: none';
    $('ast_ag12').style = 'display: none';
    $('ast_ag13').style = 'display: none';
    $('ast_ag14').style = 'display: none';
  }
}
