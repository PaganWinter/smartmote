<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once("bootstrap.php");

if ($db->connect_errno > 0){
  die('Unable to connect to database [' . $db->connect_error . ']');
}


function print_channels($db) {
  $statement = $db->prepare("SELECT chl_id,chl_name,chl_no,chl_cat_id,chl_logo FROM remote_channel ORDER BY chl_id");
  $statement->execute();
  $statement->bind_result($chl_id, $chl_name, $chl_no, $chl_cat_id, $chl_logo);
  while($statement->fetch()) {
  $chl_logo = str_replace(" ", "-", strtolower($chl_name)) . ".gif";

    $logo_img = '<img src="images/logos/'.htmlspecialchars($chl_logo).'" width="90px" />';
    if (!file_exists('images/logos/'.$chl_logo)) {
      $logo_img = '<img src="images/logos/clear.gif" width="90px" height="50px" />';
    }
    print '<li id="chl-'.htmlspecialchars($chl_no).'" class="chl cat-'.htmlspecialchars($chl_cat_id).' clearfix" data-num="'.htmlspecialchars($chl_no).'" data-id="'.htmlspecialchars($chl_id).'" data-name="'.htmlspecialchars($chl_name).'">
		<div class="chl-logo"><div class="chl-no">'.htmlspecialchars($chl_no).'</div>'.$logo_img.'</div>
		<div class="chl-details clearfix">
		  <div class="chl-details-top">
		    <div class="chl-name stb">'.htmlspecialchars($chl_name).'</div>
		    <div class="chl-controls"><div class="chl-control-fav"></div></div>
		  </div>
		  <div class="chl-programs not-loaded">Touch to load programs list</div>
		</div>
	</li>';
  }
}

function print_categories($db) {
  $statement = $db->prepare("SELECT cat_id,cat_name FROM remote_category");
  $statement->execute();
  $statement->bind_result($cat_id, $cat_name);
  while($statement->fetch()){
    print '<li class="">
			<label class="cat" for="cat_'.htmlspecialchars($cat_id).'">
				<span>'.htmlspecialchars($cat_name).'</span>
				<input type="checkbox" id="cat_'.htmlspecialchars($cat_id).'" value="'.htmlspecialchars($cat_id).'" class="cat-tick" />
			</label>
		</li>';
  }
}


?>
<!doctype html>
<html>
<head>
<title>Smartmote</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

<!-- <link rel="stylesheet" type="text/css" href="css/jquery.mobile-1.4.5.min.css"> -->
<!-- <link rel="stylesheet" type="text/css" href="css/jquery.mobile.checkradio.structure.min.css"> -->
<link rel="stylesheet" type="text/css" href="css/jquery.materialripple.css">
<link rel="stylesheet" type="text/css" href="css/main.css?v=1.47">

<!-- fonts -->
<link href='http://fonts.googleapis.com/css?family=Roboto+Condensed' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Roboto+Slab' rel='stylesheet' type='text/css'>

<!-- <script type="text/javascript" src="js/jquery.mobile.checkradio.min.js"></script> -->
<!-- <script type="text/javascript" src="js/jquery.mobile-1.4.5.min.js"></script> -->
<script type="text/javascript" src="js/jquery.js?v=1.2"></script>
<script type="text/javascript" src="js/jquery.materialripple.js"></script>


<script>
	function setCookie(name, value, days) {
		var expires;

		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = "; expires=" + date.toGMTString();
		} else {
			expires = "";
		}
		document.cookie = escape(name) + "=" + escape(value) + expires + "; path=/";
	}

	function getCookie(name) {
		var nameEQ = escape(name) + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) === ' ') c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) === 0) return unescape(c.substring(nameEQ.length, c.length));
		}
		return null;
	}

	function deleteCookie(name) {
		setCookie(name, "", -1);
	}

	function push_recent(num) {
		if (jQuery.inArray(num, recents_queue) == -1) {
			recents_queue.unshift(num);
			if (recents_queue.length > 5) {
				recents_queue.pop();
			}
			jQuery.each(recents_queue, function(i, num) {
				img = $("#chl-"+num).find("img").attr("src");
				$("#recent-tab-"+(i+1)).attr("data-num", num);
				$("#recent-tab-"+(i+1)).find("img").attr("src", img);
			});
		}
	}

	function filter_by_cat() {
		selected_cats = [];
		$("li.chl").hide();
		$("input.cat-tick:checked").each(function() {
			cat = $(this).val();
			selected_cats.push(cat);
			$("li.chl.cat-"+cat).show();
		});
		if (selected_cats.length == 0) {
			$("li.chl").show();
		}
	}

	function close_controls() {
		$panel_controls.css({top: $(window).height()});
	}
	function close_filter() {
		$panel_filter.css({top: $(window).height()});
	}
	function close_search() {
		$panel_search.css({top: (0 - $panel_search.height() - 20)});
	}


	var favorites = [];
	var recents_queue = [];


	/*** onready ***/
	$( document ).ready(function() {
		var selected_cats = new Array();


		/* Caching jQuery elements */
		$container = $("#container");
		$result = $("#result");

		$panel_controls = $("#panel-controls");
		$panel_filter = $("#panel-filter");
		$panel_search = $("#panel-search");

		$tab_controls = $("#tab-controls");
		$tab_channels = $("#tab-channels");
		$tab_filter = $("#tab-filter");
		$tab_favs = $("#tab-favs");
		$tab_search = $("#tab-search");

		$search_box = $("#search-box");
		$recents_bar = $("#recents-bar");
		$channels_list = $("#channels");

		media_query="screen and (max-width: 500px)";
		if (window.matchMedia(media_query).matches) {
			// console.log("mobile");
		}
		else {
			// console.log("desktop");
			$container.css({width: "500px"});
		}


		/*** Syncing state ***/
		if (getCookie("favorites") === null) {
			favorites = [];
		}
		else {
			favorites = JSON.parse(getCookie("favorites"));
		}
		if (favorites != null && favorites.length != 0) {
			jQuery.each(favorites, function(i, num) {
				$("#channels li[data-num='"+num+"']").addClass("fav");
			});
		}
		$(".cat-tick").each(function(index) {
			if ($(this).is(':checked')) {
				filter_by_cat();
				$(this).parent().parent().addClass("cat-selected");
			}
		});


		/*** Setting CSS defaults ***/
		$("#channels").css({
			"margin-bottom": $("#tab-bar").height()
		});
		$(".chl-details").css({
			"width": ($container.width()-90)
		});
		$(".chl-name").css({
			"width": ($container.width()-115)
		});
		$(".panel").css({
			"top": $(window).height()
		});
		$panel_search.css({
			"top": (0 - $panel_search.height() - 20)
		});
		$recents_bar.width($container.width ());
		$("#tab-bar").width($container.width());
		$("#panel-controls").width($container.width());
		$("#panel-filter").width($container.width());
		$("#panel-search").width($container.width());
		$(".tab").width(($container.width()-4)/5);
		$("#search-box").width($container.width() - 70);



		/*** Load EPG ***/
//		$(".chl-programs.not-loaded").on("click", function() {
		$(".chl-details").on("click", function() {
			var id = $(this).parent().data("id");
			$result.text(id);
			var $prog_div = $(this).find(".chl-programs.not-loaded");
			$prog_div.addClass('loading');

			$.ajax({
				url: "epg.php?chl_id="+id,
				error: function(){
					$prog_div.html("Error loading programs");
				},
				success: function(epg_json){
					$prog_div.removeClass('loading');
					var epg = JSON.parse(epg_json);
					if (epg.status = 1 && epg.data.prog.length > 0) {
						$prog_div.removeClass('not-loaded');
						$prog_div.addClass('loaded');
						$prog_div.html('<ul class="chl-programs-container" style="width: '+(epg.data.prog.length * 205)+'px"></ul>');
						var $prog_ul = $prog_div.find('ul');
						$.each(epg.data.prog, function(index, value) {
							$prog_ul.append('<li class="chl-programs-prog"><div class="prog-name">'+value.prog_name+'</div><div class="prog-desc">'+value.prog_desc.substring(0, 80)+'</div><div class="prog-time">'+value.start_time_fmt+'</div></li>');
						});
					}
					else {
						$prog_div.html("Error loading programs");
					}
				},
				timeout: 10000
			});
		});


		/* Setting favorites */
		$(".chl-control-fav").on("click", function() {
			num = $(this).parent().parent().parent().parent().attr('data-num');
			fav_index = jQuery.inArray(num, favorites);
			if (fav_index == -1) {
				favorites.push(num);
				$(this).parent().parent().parent().parent().addClass("fav");
			}
			else {
				favorites.splice(fav_index, 1);
				$(this).parent().parent().parent().parent().removeClass("fav");
			}
			setCookie("favorites", JSON.stringify(favorites), 20*365);
		});


		/* Selecting Tabs */
		$("#tab-controls").on("click", function() {
			if ($tab_controls.hasClass("tab-selected")) {
				$channels_list.show();
				$recents_bar.show();
				$panel_controls.animate({top: $(window).height()}, 200, function() {
					$tab_controls.removeClass("tab-selected");
				});
			}
			else {
				close_filter();
				close_search();
				$panel_controls.animate({top: 0}, 200, function() {
					$(".tab-selected").removeClass("tab-selected");
					$tab_controls.addClass("tab-selected");
					$channels_list.hide();
					$recents_bar.hide();
				});
			}
		});

		$("#tab-filter").on("click", function() {
			if ($tab_filter.hasClass("tab-selected")) {
				$channels_list.show();
				$recents_bar.show();
				$panel_filter.animate({top: $(window).height()}, 200, function() {
					$tab_filter.removeClass("tab-selected");
				});
			}
			else {
				close_controls();
				close_search();
				$panel_filter.animate({top: 0}, 200, function() {
					$(".tab-selected").removeClass("tab-selected");
					$tab_filter.addClass("tab-selected");
					$channels_list.hide();
					$recents_bar.hide();
				});
			}
			filter_by_cat();
		});


		$("#tab-search").on("click", function() {
			if ($tab_search.hasClass("tab-selected")) {
				$channels_list.show();
				$recents_bar.show();
				$panel_search.animate({top: (0 - $panel_search.height() - 20)}, 200, function() {
					$tab_search.removeClass("tab-selected");
				});
			}
			else {
				close_controls();
				close_filter();
				$channels_list.show();
				$recents_bar.show();
				$panel_search.animate({top: 0}, 200, function() {
					$(".tab-selected").removeClass("tab-selected");
					$tab_search.addClass("tab-selected");
					$recents_bar.hide();
					$('#search-box').focus();
				});
			}

		});
		$('#search-box').keyup(function(){
			var search_text = $(this).val().toLowerCase();
			$("#channels>li").each(function(){
				var channel_name = $(this).data("name").toLowerCase();
				(channel_name.indexOf(search_text) >= 0) ? $(this).show() : $(this).hide();            
			});
		});
		$("#clear-search").on( "click", function() {
			$search_box.val("");
			$("#channels>li").show();
		});

		$("#tab-favs").on("click", function() {
			$(".tab-selected").removeClass("tab-selected");
			$tab_favs.addClass("tab-selected");
			$channels_list.show();
			$recents_bar.show();

			close_controls();
			close_filter();
			close_search();

			$panel_filter.animate({top: $(window).height()}, 200);
			$panel_controls.animate({top: $(window).height()}, 200);
			$panel_search.animate({top: (0 - $panel_search.height() - 20)}, 200);

			$("li.chl").hide();
			$(".fav").show();
		});

		$("#tab-channels").on("click", function() {
			close_controls();
			close_filter();
			close_search();

			$(".tab-selected").removeClass("tab-selected");
			$tab_channels.addClass("tab-selected");
			$channels_list.show();
			$recents_bar.show();

			$(".chl").show();
		});


		/* Clicking Buttons */
		$( ".btn" ).on( "click", function() {
			key = $(this).data("key");
			$result.text(key);
			$.get("send.php?remote=stb&key="+key);
		});

		$( ".chl-logo" ).on( "click", function() {
			num = $(this).parent().data("num");
			$result.text(num);
			$.get("send.php?remote=stb&chl="+num);
			if (!$(this).parent().hasClass("recent-tab")) {
				push_recent(num);
			}
		});


		/* Filtering */
		$( ".cat-tick" ).on( "click", function() {
			filter_by_cat();
			if ($(this).is(':checked')) {
				$(this).parent().parent().addClass("cat-selected");
			}
			else {
				$(this).parent().parent().removeClass("cat-selected");
			}
		});


		/* Material Ripple */
		$('.btn').materialripple();
		$('.tab').materialripple();
		$('#panel-filter li').materialripple();

	});
</script>

</head>
<body>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-60335508-1', 'auto');
  ga('send', 'pageview');

</script>

<div id="container">
<!-- <div id="result"></div> -->
<div id="recents-bar">
	<div id="recent-tab-1" class="recent-tab chl"><img class="chl-logo" /></div>
	<div id="recent-tab-2" class="recent-tab chl"><img class="chl-logo" /></div>
	<div id="recent-tab-3" class="recent-tab chl"><img class="chl-logo" /></div>
	<div id="recent-tab-4" class="recent-tab chl"><img class="chl-logo" /></div>
	<div id="recent-tab-5" class="recent-tab chl"><img class="chl-logo" /></div>
</div>


<ul id="channels">
<?php
  print_channels($db);
?>
</ul>


<table id="panel-controls" class="panel" style="bottom: 60px;">
  <tr>
    <td><a id="stb_red" class="btn stb" data-key="STB_RED"></a></td>
    <td><a id="stb_green" class="btn stb" data-key="STB_GREEN"></a></td>
    <td><a id="stb_yellow" class="btn stb" data-key="STB_YELLOW"></a></td>
    <td><a id="stb_blue" class="btn stb" data-key="STB_BLUE"></a></td>
  </tr>
  <tr>
    <td><a id="stb_channel" class="btn stb" data-key="STB_CHANNEL">chl</a></td>
    <td><a id="stb_messages" class="btn stb" data-key="STB_MESSAGES"></a></td>
    <td><a id="stb_list" class="btn stb" data-key="STB_LIST"></a></td>
    <td><a id="stb_favorites" class="btn stb" data-key="STB_FAVORITES"></a></td>
  </tr>
  <tr>
	<td><a id="stb_power" class="btn stb" data-key="STB_POWER"></a></td>
    <td><a id="stb_menu" class="btn stb" data-key="STB_MENU"></a></td>
    <td><a id="stb_info" class="btn stb" data-key="STB_INFO"></a></td>
	<td><a id="stb_mute" class="btn stb" data-key="STB_MUTE"></a></td>
  </tr>
  <tr>
    <td><a id="stb_1" class="btn stb" data-key="STB_1">1</a></td>
    <td><a id="stb_2" class="btn stb" data-key="STB_2">2</a></td>
    <td><a id="stb_3" class="btn stb" data-key="STB_3">3</a></td>
    <td><a id="stb_vol_up" class="btn stb" data-key="STB_VOLUMEUP">+</a></td>
  </tr>
  <tr>
    <td><a id="stb_4" class="btn stb" data-key="STB_4">4</a></td>
    <td><a id="stb_5" class="btn stb" data-key="STB_5">5</a></td>
    <td><a id="stb_6" class="btn stb" data-key="STB_6">6</a></td>
    <td><a id="stb_vol_down" class="btn stb" data-key="STB_VOLUMEDOWN">-</a></td>
  </tr>
  <tr>
    <td><a id="stb_7" class="btn stb" data-key="STB_7">7</a></td>
    <td><a id="stb_8" class="btn stb" data-key="STB_8">8</a></td>
    <td><a id="stb_9" class="btn stb" data-key="STB_9">9</a></td>
    <td><a id="stb_ch_next" class="btn stb" data-key="STB_NEXT"></a></td>
  </tr>
  <tr>
    <td><a id="stb_ok" class="btn stb" data-key="STB_OK">OK</a></td>
    <td><a id="stb_0" class="btn stb" data-key="STB_0">0</a></td>
    <td><a id="stb_back" class="btn stb" data-key="STB_BACK"></a></td>
    <td><a id="stb_ch_prev" class="btn stb" data-key="STB_PREVIOUS"></a></td>
  </tr>
</table>

<!--
<div style="position: fixed; margin-top: -1px; left: 12%; width: 0; height: 0; padding: 0; border-left: 7px solid transparent; border-right: 7px solid transparent; border-top: 7px solid #fff; float: right;"></div>
-->


<div id="panel-filter" class="panel" style="">
	<ul>
<?php
  print_categories($db);
?>
	</ul>
</div>


<div id="panel-search" class="panel" style="">
	<input type="text" name="search-text" id="search-box" size="20" placeholder="Search" /><a id="clear-search" href="#"></a>
</div>


<ul id="tab-bar">
	<li id="tab-controls" class="tab"><a href="#">Remote</a></li>
	<li id="tab-channels" class="tab"><a href="#">All<br />Channels</a></li>
	<li id="tab-filter" class="tab"><a href="#">Filter</a></li>
	<li id="tab-search" class="tab"><a href="#">Search</a></li>
	<li id="tab-favs" class="tab"><a href="#">Favourites</a></li>
</ul>


</div>

</body>
</html>
<?php

$db->close();


?>
