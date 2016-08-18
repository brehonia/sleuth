<?php
	set_time_limit(60);
	mb_internal_encoding('UTF-8');
	mb_http_output('UTF-8');
	require_once('config.php');
	require_once('lib.php');
	$db = new \PDO('mysql:host='.$config_mysqlHost.';dbname='.$config_mysqlDatabase.';charset=utf8', $config_mysqlUser, $config_mysqlPassword);
	
	$src = (int) $_REQUEST['src_id'];
	$dest = (int) $_REQUEST['dest_id'];
	$sk1 = (int) $_REQUEST['sk1_id'];
	$digimon = getAllDigimon($db, false);
	$skills = getAllSkills($db, false);
	
	$src_ok = !($src == null || $src == -1);
	$dest_ok = !($dest == null || $dest == -1);
	$sk1_ok = !($sk1 == null || $sk1 == -1);
	
	// if there's only one mon, call it the source
	if ($dest_ok && !$src_ok)
	{
		$src = $dest;
		$dest = null;
		$src_ok = true;
		$dest_ok = false;
	}
	
	// quit out if there's no valid source/destination pair (mon-mon or mon-skill),
	// OR if the source and destination are the same with no skill in between
	if ((!($src_ok && $dest_ok) && !($src_ok && $sk1_ok)) || (($src == $dest) && !$sk1_ok))
	{
		echo('Pick two Digimon, or a Digimon and a skill.');
		return;
	}
	
	// mon to skill
	if ($src_ok && $sk1_ok)
	{
		$edges = shortestPathToSkill($db, $src, $sk1)->edges;
		$edgeCount = count($edges);
		$lastMon = $src;
		
		echo('<table>');
		
		// interpret each step in the path as a text instruction: "source => cost => destination"
		for ($i=0; $i < $edgeCount; $i++)
		{
			$srcName = $digimon[$edges[$i]->srcNode->id]->name;
			$destName = '';
			if ($i + 1 == $edgeCount)
			{
				$destName = '<i>'.$skills[$edges[$i]->destNode->id]->name.'</i>';
				$lastMon = $edges[$i]->srcNode->id;
			}
			else $destName = $digimon[$edges[$i]->destNode->id]->name;
			
			$levels = $edges[$i]->cost->totalLevels();
			$levelText = 'Lv'.$levels;
			if ($levels == 0) $levelText = 'Free';
			
			echo('<tr><td class="stepfrom">'.$srcName.'</td><td class="steparrow"><img src="arrow_right.png" /></td><td class="stepcost">'.$levelText.'</td><td class="steparrow"><img src="arrow_right.png" /></td><td class="stepto">'.$destName.'</td></tr>');
		}
		
		// skill to mon
		if ($dest_ok && ($lastMon != $dest))
		{
			$edges = shortestPathToDigimon($db, $lastMon, $dest)->edges;
			$edgeCount = count($edges);
			
			for ($i=0; $i < $edgeCount; $i++)
			{
				$srcName = $digimon[$edges[$i]->srcNode->id]->name;
				$destName = $digimon[$edges[$i]->destNode->id]->name;
				
				$levels = $edges[$i]->cost->totalLevels();
				$levelText = 'Lv'.$levels;
				if ($levels == 0) $levelText = 'Free';
				
				echo('<tr><td class="stepfrom">'.$srcName.'</td><td class="steparrow"><img src="arrow_right.png" /></td><td class="stepcost">'.$levelText.'</td><td class="steparrow"><img src="arrow_right.png" /></td><td class="stepto">'.$destName.'</td></tr>');
			}
		}
		
		echo('</table><br />');
	}
	
	// mon to mon
	if ($src_ok && $dest_ok && !$sk1_ok)
	{
		$edges = shortestPathToDigimon($db, $src, $dest)->edges;
		$edgeCount = count($edges);
		
		echo('<table>');
		
		for ($i=0; $i < $edgeCount; $i++)
		{
			$srcName = $digimon[$edges[$i]->srcNode->id]->name;
			$destName = $digimon[$edges[$i]->destNode->id]->name;
			
			$levels = $edges[$i]->cost->totalLevels();
			$levelText = 'Lv'.$levels;
			if ($levels == 0) $levelText = 'Free';
			
			echo('<tr><td class="stepfrom">'.$srcName.'</td><td class="steparrow"><img src="arrow_right.png" /></td><td class="stepcost">'.$levelText.'</td><td class="steparrow"><img src="arrow_right.png" /></td><td class="stepto">'.$destName.'</td></tr>');
		}
		
		echo('</table><br />');
	}
?>
