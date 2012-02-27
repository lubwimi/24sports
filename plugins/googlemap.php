<?php
/*
Plugin Name: Google Map
Description: Add a Google map on page.
Version: 0.7
Revision: 01/April/2011
Author: singulae
Author URI: http://www.singulae.com/
*/



# get correct id for plugin
$thisfile=basename(__FILE__, ".php");
$gmap_file=GSDATAOTHERPATH .'googlemap.xml';


# register plugin
register_plugin(
	$thisfile, 													# ID of plugin, should be filename minus php
	'Google map', 											# Title of plugin
	'0.7', 															# Version of plugin
	'singulae',													# Author of plugin
	'http://www.singulae.com/',		 			# Author URL
	'Add a Google map on page.', 				# Plugin Description
	'plugins', 													# Page type of plugin
	'gmap_show_config' 	 								# Function that displays content
);

# activate filter
add_filter('content','gmap_show'); 

# hooks
add_action('plugins-sidebar','createSideMenu',array($thisfile,'Google Map')); 
add_action('index-pretemplate','gmap_include_check',array());


# functions
/**
 * Checks to see if an googlemap exists in page and add includes
 */
function gmap_include_check()
{
	global $data_index;
	
	if (strpos($data_index->content, '(%googlemap%)') === false && strpos($data_index->content, '(% googlemap %)')=== false )
	{
		return false;
	}
	
	add_action('theme-header','gmap_header',array());
}

function gmap_show($contents){    	
  $tmpContent = $contents;
  $div ='<div id="gmapcontainer"></div>';
	$tmpContent = preg_replace('/\(%(.*)googlemap(.*)%\)/i',$div,$tmpContent);     
  return $tmpContent;
};

function gmap_header(){    	


  $gmap_config = gmap_getXMLdata();
	
	$infowindow = '';
	
	if($gmap_config['infotext'])
	{
		$infowindow = 'var contentString ="'.str_replace(array("\r\n", "\r", "\n"), "<br />",$gmap_config['infotext']).'";
          var infowindow = new google.maps.InfoWindow({content: contentString});
          infowindow.open(map,marker); 
          google.maps.event.addListener(marker, \'click\', function() { infowindow.open(map,marker);});';
	};

	echo '	
<!-- Google map Plugin : start -->

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
<!--
var gmap = {
  // unobtrusive way for call	window.onload
  addEvent: function (obj, evType, fn){ 
    if (obj.addEventListener){ 
      obj.addEventListener(evType, fn, false); 
      return true; 
    } else if (obj.attachEvent){ 
      var r = obj.attachEvent("on"+evType, fn); 
      return r; 
    } else { 
      return false; 
    } 
  },

  init: function ()
  {
    var gmapid = document.getElementById("gmapcontainer");
    if(gmapid)
    {
      var options = {
          zoom: '.$gmap_config['zoom'].',
          address: \''.$gmap_config['address'].'\',
          center: \'0,0\',
          streetViewControl:true,
          scaleControl: false,
          mapTypeId: google.maps.MapTypeId.'.$gmap_config['maptype'].',
          mapTypeControl: true,
          mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR }
          };
      var geocoder = new google.maps.Geocoder();      
      geocoder.geocode( { \'address\': options.address}, function(results, status) 
      {
        if (status === google.maps.GeocoderStatus.OK) 
        {
          options.center = results[0].geometry.location;
          map = new google.maps.Map(gmapid,options);
          var image = \''.$gmap_config['image'].'\';
          var marker = new google.maps.Marker({ map: map,  position: options.center, title: options.address, icon: image });
          '.$infowindow.'
        } else {
          alert(status);
        }
      });
    }
  }
};
  gmap.addEvent(window, \'load\', gmap.init);
-->
</script>
<style type="text/css">
  div#gmapcontainer {width:'.$gmap_config['width'].'px;height:'.$gmap_config['height'].'px}
</style>

<!-- Google map Plugin : end -->
';

};



/*
 * get XML data
 */

function gmap_getXMLdata(){
  global $gmap_file;

	if (file_exists($gmap_file)) {
	
		$gmap = array();
		
		$x = getXML($gmap_file);
		$gmap['width']		 	= $x->width;
		$gmap['height']		 	= $x->height;
		$gmap['address']		= $x->address;
		$gmap['zoom']  			= $x->zoom;
		$gmap['maptype']  	= $x->maptype;
		$gmap['image']  		= $x->image;
		$gmap['infotext'] 	= $x->infotext;
	} else {
		$gmap['width']		 	= 600;
		$gmap['height']		 	= 400;
		$gmap['address']		= 'Granollers, Barcelona, Spain';
		$gmap['zoom'] 			= 12;
		$gmap['maptype']  	= 'ROADMAP';
		$gmap['image']  		= "http://www.singulae.com/favicon.ico";
		$gmap['infotext'] 	= '<b>Hello word</b>';
	};
	
	return $gmap;
};



/*
 * Plugin page in admin
 */

function gmap_check($val = false){
	if(!isset($val)){ $val = ''; };
	return $val;
};



function gmap_show_config() 
{
	global $gmap_file, $success, $error, $LANG;

	i18n_merge('googlemap') || i18n_merge('googlemap','en_US');

	
	// submitted form
	if (isset($_POST['submit'])) {
		$gmap_config['width']		  = gmap_check($_POST['gmap_width']);
		$gmap_config['height']		= gmap_check($_POST['gmap_height']);
		$gmap_config['address'] 	= gmap_check($_POST['gmap_addr']);
		$gmap_config['zoom'] 			= gmap_check($_POST['gmap_zoom']);
		$gmap_config['maptype']		= gmap_check($_POST['gmap_maptype']);
		$gmap_config['image']  		= gmap_check($_POST['gmap_image']);
		$gmap_config['infotext'] 	= gmap_check($_POST['gmap_infotxt']);
	
	// save
		$xml = @new SimpleXMLExtended('<?xml version="1.0" encoding="ISO-8859-15"?><item></item>');
		$xml->addChild('width', 		$gmap_config['width']);
		$xml->addChild('height', 		$gmap_config['height']);
		$xml->addChild('address', 	htmlspecialchars($gmap_config['address'],ENT_QUOTES,"ISO-8859-15"));
		$xml->addChild('zoom', 			$gmap_config['zoom']);
		$xml->addChild('maptype', 	$gmap_config['maptype']);
		$xml->addChild('image', 		$gmap_config['image']);
		$xml->addChild('infotext', 	htmlspecialchars($gmap_config['infotext'],ENT_QUOTES,"ISO-8859-15"));
	
	//read
		if (! $xml->asXML($gmap_file)) {
			$error = i18n_r('CHMOD_ERROR');
		} else {
			$x = getXML($gmap_file);
			$gmap_config['width']		 	= $x->width;
			$gmap_config['height']		= $x->height;
			$gmap_config['address']		= $x->address;
			$gmap_config['zoom'] 			= $x->zoom;
			$gmap_config['maptype'] 	= $x->maptype;
			$gmap_config['image'] 		= $x->image;
			$gmap_config['infotext'] 	= $x->infotext;
			$success = i18n_r('SETTINGS_UPDATED');
		};
		
	}else{
		$gmap_config = gmap_getXMLdata();
	};

?>
	<style type="text/css">
	form[name="formmap"] fieldset {width:510px;padding:5px;font-weight:bold;border: 1px solid #aaa;}
	form[name="formmap"] fieldset legend{color:#222}
	form[name="formmap"] fieldset label {line-height:10px;font-weight:normal;}
	form[name="formmap"] fieldset label input[type="radio"]{margin:4px;vertical-align: middle;}
	form[name="formmap"] fieldset label span {font-size:0.8em;}
	form[name="formmap"] input.text.small {width:152px}
	</style>
	
  <h3><?php i18n('googlemap/TITLE');?></h3>
	
	<p><?php i18n('googlemap/INTRO');?></p>


	<?php 
	if($success) { 
		echo '<p style="color:#669933;"><b>'. $success .'</b></p>';
	};
	if($error) { 
		echo '<p style="color:#cc0000;"><b>'. $error .'</b></p>';
	};

	?>


	
	<p>&nbsp;</p>
	
  <form method="post" name="formmap" action="<?php	echo $_SERVER ['REQUEST_URI']?>">  
		
		<p style="float:left;margin-right:15px"><label for="gmap_width"><?php i18n('googlemap/WIDTH');?></label><input id="gmap_width" name="gmap_width" class="text small" value="<?php echo $gmap_config['width']; ?>" /></p>
    <p style="float:left;margin-right:15px"><label for="gmap_height"><?php i18n('googlemap/HEIGHT');?></label><input id="gmap_height" name="gmap_height" class="text small" value="<?php echo $gmap_config['height']; ?>" /></p>
		<p style="float:left;margin-right:15px"><label for="gmap_zoom"><?php i18n('googlemap/ZOOM');?></label><input id="gmap_zoom" name="gmap_zoom" class="text small" value="<?php echo $gmap_config['zoom']; ?>" /></p>
		
		<p style="clear:both"></p>
		
    <p><label for="gmap_addr"><?php i18n('googlemap/ADDRESS');?></label><input id="gmap_addr" name="gmap_addr" class="text" value="<?php echo $gmap_config['address']; ?>" /></p>
    <p><label for="gmap_image"><?php i18n('googlemap/IMAGE');?></label><input id="gmap_image" name="gmap_image" class="text" value="<?php echo $gmap_config['image']; ?>" /></p>
		
		<fieldset>
		<legend><?php i18n('googlemap/MAPTYPE');?></legend>		
		<label for="ROADMAP"><input type="radio" name="gmap_maptype" id="ROADMAP" value="ROADMAP" <?php if($gmap_config['maptype']=="ROADMAP"){ echo 'checked="checked"';}; ?>/><?php i18n('googlemap/ROADMAP');?><span> <?php i18n('googlemap/DESC_ROADMAP');?></span></label>
		<label for="SATELLITE"><input type="radio" name="gmap_maptype" id="SATELLITE" value="SATELLITE" <?php if($gmap_config['maptype']=="SATELLITE"){ echo 'checked="checked"';};	?>/><?php i18n('googlemap/SATELLITE');?><span> <?php i18n('googlemap/DESC_SATELLITE');?></span></label>
		<label for="HYBRID"><input type="radio" name="gmap_maptype" id="HYBRID" value="HYBRID" <?php if($gmap_config['maptype']=="HYBRID"){ echo 'checked="checked"';};	?>/><?php i18n('googlemap/HYBRID');?><span> <?php i18n('googlemap/DESC_HYBRID');?></span></label>
		<label for="TERRAIN"><input type="radio" name="gmap_maptype" id="TERRAIN" value="TERRAIN" <?php if($gmap_config['maptype']=="TERRAIN"){ echo 'checked="checked"';};	?>/><?php i18n('googlemap/TERRAIN');?><span> <?php i18n('googlemap/DESC_TERRAIN');?></span></label>
		</fieldset>
		
		<p></p>
		
		<p><label for="gmap_infotxt"><?php i18n('googlemap/INFO');?></label><textarea id="gmap_infotxt" name="gmap_infotxt" style="height:100px;width:510px;"><?php echo $gmap_config['infotext']; ?></textarea>
		<br/><span style="font-size:0.8em"><?php i18n('googlemap/REQUIRED');?></span></p>
		  
	 <p><input type="submit" id="submit" class="submit" value="<?php i18n('BTN_SAVESETTINGS'); ?>" name="submit" /></p>
  </form>
	

	
	<?php

	};
	?>