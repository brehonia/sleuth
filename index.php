<?php
	mb_internal_encoding('UTF-8');
	mb_http_output('UTF-8');
	
	require_once('config.php');
	require_once('lib.php');
	$db = new \PDO('mysql:host='.$config_mysqlHost.';dbname='.$config_mysqlDatabase.';charset=utf8', $config_mysqlUser, $config_mysqlPassword);
	
	$digimonList = '';
	foreach (getAllDigimon($db, true) as $mon)
	{
		$digimonList .= '{label:"'.$mon->name.'", value:'.$mon->id.'},';
	}
	$digimonList = rtrim($digimonList, ',');
	
	$skillList = '';
	foreach (getAllSkills($db, true) as $skill)
	{
		$skillList .= '{label:"'.$skill->name.'", value:'.$skill->id.'},';
	}
	$skillList = rtrim($skillList, ',');
?>
﻿<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
	<meta charset="utf-8" />
	<meta name="description" content="Character builder for Digimon Story: Cyber Sleuth." />
	<link rel="icon" type="image/x-icon" href="favicon.ico" />
	<title>SLEUTH</title>

	<link rel="stylesheet" type="text/css" href="jquery-ui/jquery-ui.css" />
	<script type="text/javascript" src="jquery-1.12.3.min.js"></script>
	<script type="text/javascript" src="jquery-ui/jquery-ui.min.js"></script>

	<link rel="stylesheet" type="text/css" href="sleuth.css" />
	<script type="text/javascript">
		function getDigimonList()
		{
			return [<?=$digimonList?>];
		}
		
		function getSkillList()
		{
			return [<?=$skillList?>];
		}
	</script>
	<script type="text/javascript" src="sleuth.js" charset="utf-8"></script>
</head>
<body>
<div id="swoosh"></div>
<div id="logo">
	<img src="logo.png" alt="SLEUTH" />
</div>
<div class="halfpanel">
	<form id="mainform">
		<div id="src_box" class="expandBox">
			<h3>The Digimon You Have</h3>
			<div>
				<input type="text" id="src_digimon" />
			</div>
		</div>
		<div id="dest_box" class="expandBox">
			<h3>The Digimon You Deserve</h3>
			<div>
				<input type="text" id="dest_digimon" />
			</div>
		</div>
		<div id="sk1_box" class="expandBox">
			<h3 id="sk1_header">Skill (Optional)</h3>
			<div>
				<input type="text" id="sk1" />
			</div>
		</div>
		<input type="hidden" id="src_id" name="src_id" />
		<input type="hidden" id="dest_id" name="dest_id" />
		<input type="hidden" id="sk1_id" name="sk1_id" />
		<input type="submit" id="submit" value="Solve" />
	</form>
</div>
<div class="halfpanel" id="rightpanel">
	<div id="results" class="ui-widget ui-widget-content ui-corner-all"></div>
	<div id="startpanel">
		<div id="info" class="ui-widget ui-widget-content ui-corner-all">
			<p>This is a character builder for Digimon Story: Cyber Sleuth.</p>
			<p>Put any two Digimon in these boxes, and I'll show you the most efficient sequence of Digivolutions to turn one into the other.</p>
			<p>You can also choose an inherited skill to pick up along the way. In the future you will be able to add more than one skill, I promise!</p>
		</div>
		<div>
			<img src="wanya.gif" id="wanya" alt="Wanyamon" />
		</div>
	</div>
</div>
<div id="about" title="About">
	<p><b>Work in progress</b></p>
	<p>Made by <a href="http://twitter.com/brehonia" target="_blank">@brehonia</a></p>
	<p>Many thanks and kudos to <a href="http://www.gamefaqs.com/vita/757436-digimon-story-cyber-sleuth/faqs/71778?single=1" target="_blank">Draken70 on GameFAQs</a>.
</div>
</body>
</html>
