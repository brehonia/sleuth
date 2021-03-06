﻿<?php
	mb_internal_encoding('UTF-8');
	mb_http_output('UTF-8');
	require_once('classdefs.php');
	
	// find paths from the specified source node to every other node on a pre-constructed graph
	function shortestPathsFrom($source, $nodes, $nodeLookup, $edges)
	{
		$estimates = array();
		$processed = array();
		
		for ($i = 0; $i < count($nodes); $i++)
		{
			$estimates[$i] = new Path($nodes[$i]);
		}
		
		$estimates[$nodeLookup[Node::TYPE_DIGIMON][$source->id]]->cost->setRelaxed(true);
		
		$nodeCount = count($estimates);
		while (count($processed) < $nodeCount)
		{
			// target the closest node that hasn't already been processed
			usort($estimates, "Path::cmp");
			$currNode = null;
			foreach ($estimates as $path)
			{
				if (!in_array($nodeLookup[$path->node->type][$path->node->id], $processed))
				{
					// if we run into an edge that hasn't been relaxed, the remaining nodes are unreachable
					if (!$path->cost->getRelaxed()) return $estimates;
					
					$currNode = $path->node;
					break;
				}
			}
			
			// examine all nodes adjacent to the target
			foreach ($edges as $edge)
			{
				if ($edge->srcNode == $currNode)
				{
					$srcIdx = Path::indexOf($edge->srcNode, $estimates);
					$destIdx = Path::indexOf($edge->destNode, $estimates);
					
					// if there's no path to this node yet, or if the path we've taken now is shorter, update the estimate
					// (path to adj node = path to target node + the edge that joins them)
					if ($estimates[$destIdx]->cost->total() > ($estimates[$srcIdx]->cost->total() + $edge->cost->total()))
					{
						$estimates[$destIdx]->edges = array();
						foreach ($estimates[$srcIdx]->edges as $e)
						{
							array_push($estimates[$destIdx]->edges, $e);
						}
						array_push($estimates[$destIdx]->edges, $edge);
						
						// the cost addition function propagates relaxation from the source
						$estimates[$destIdx]->cost = $estimates[$srcIdx]->cost->add($edge->cost);
					}
				}
			}
			
			array_push($processed, $nodeLookup[$currNode->type][$currNode->id]);
		}
		
		return $estimates;
	}
	
	// find and store paths from the specified digimon to every other digimon and skill
	function shortestFromId($db, $sourceId)
	{
		$nodes = array();
		$edges = array();
		
		$nodeLookup = array();
		$nodeLookup[Node::TYPE_DIGIMON] = array();
		$nodeLookup[Node::TYPE_SKILL] = array();
		
		// build an array of nodes (mons and skills)
		// plus a table to look them up by type and database id
		$query = $db->prepare('select id from digimon');
		$query->execute();
		$monRows = $query->fetchAll(\PDO::FETCH_OBJ);
		
		for ($i = 0; $i < count($monRows); $i++)
		{
			$id = $monRows[$i]->id;
			$type = Node::TYPE_DIGIMON;
			array_push($nodes, new Node($id, $type));
			$nodeLookup[$type][$id] = $i;
		}
		
		$query = $db->prepare('select id from skill');
		$query->execute();
		$skillRows = $query->fetchAll(\PDO::FETCH_OBJ);
		
		$monCount = count($nodeLookup[Node::TYPE_DIGIMON]);
		for ($i = 0; $i < count($skillRows); $i++)
		{
			$id = $skillRows[$i]->id;
			$type = Node::TYPE_SKILL;
			array_push($nodes, new Node($id, $type));
			$nodeLookup[$type][$id] = $i + $monCount;
		}
		
		// represent (de-)digivolutions as undirected edges between digimon nodes
		$query = $db->prepare('select dv.id dvid, dv.*, s.* from digivolution dv inner join stats s on s.id = dv.reqstatsid;');
		$query->execute();
		$lutionRows = $query->fetchAll(\PDO::FETCH_OBJ);
		
		foreach ($lutionRows as $lr)
		{
			$lowerIdx = $nodeLookup[Node::TYPE_DIGIMON][$lr->lowerid];
			$upperIdx = $nodeLookup[Node::TYPE_DIGIMON][$lr->upperid];
			
			$edgeCost = new Cost(true);
			$edgeCost->setLevels($lr->level);
			// todo: stats
			$edgeCost->setAbiTraining($lr->abi);
			$edgeCost->setCamTraining($lr->cam);
			
			array_push($edges, new Edge($lr->dvid, Edge::TYPE_LUTION, $nodes[$lowerIdx], $nodes[$upperIdx], $edgeCost));
			array_push($edges, new Edge($lr->dvid, Edge::TYPE_LUTION, $nodes[$upperIdx], $nodes[$lowerIdx], new Cost(true)));
			if ($lr->lowertwoid != null)
			{
				$lowerTwoIdx = $nodeLookup[Node::TYPE_DIGIMON][$lr->lowertwoid];
				array_push($edges, new Edge($lr->dvid, Edge::TYPE_LUTION, $nodes[$lowerTwoIdx], $nodes[$upperIdx], $edgeCost));
				array_push($edges, new Edge($lr->dvid, Edge::TYPE_LUTION, $nodes[$upperIdx], $nodes[$lowerTwoIdx], new Cost(true)));
			}
		}
		// represent level requirements for skills as directed edges from digimon nodes to skill nodes
		$query = $db->prepare('select * from learnskill');
		$query->execute();
		$learnRows = $query->fetchAll(\PDO::FETCH_OBJ);
		
		foreach ($learnRows as $lr)
		{
			$monIdx = $nodeLookup[Node::TYPE_DIGIMON][$lr->digimonid];
			$skillIdx = $nodeLookup[Node::TYPE_SKILL][$lr->skillid];
			$edgeCost = new Cost(true);
			$edgeCost->setLevels($lr->level);
			array_push($edges, new Edge($lr->id, Edge::TYPE_LEARN, $nodes[$monIdx], $nodes[$skillIdx], $edgeCost));
		}
		
		// find paths
		$sourceNode = $nodes[$nodeLookup[Node::TYPE_DIGIMON][$sourceId]];
		$result = shortestPathsFrom($sourceNode, $nodes, $nodeLookup, $edges);
		
		// cache the results in the database
		$cacheInsert = 'insert into pathcache (srcid, destid, desttype, listpos, edgedestid, lutionid, learnid) values';
		$values = array();
		foreach ($result as $p)
		{
			$edgeIdx = 0;
			foreach ($p->edges as $e)
			{
				$cacheInsert .= ' (?, ?, ?, ?, ?, ?, ?),';
				array_push($values, $sourceId);
				array_push($values, $p->node->id);
				array_push($values, $p->node->type);
				array_push($values, $edgeIdx);
				array_push($values, $e->destNode->id);
				$edgeIdx++;
				
				$lutionid = null;
				$learnid = null;
				if ($e->type == Edge::TYPE_LUTION) $lutionid = $e->id;
				if ($e->type == Edge::TYPE_LEARN) $learnid = $e->id;
				array_push($values, $lutionid);
				array_push($values, $learnid);
			}
		}
		$cacheInsert = rtrim($cacheInsert, ',') . ';';
		
		$query = $db->prepare($cacheInsert);
		for ($i=0; $i<count($values); $i++)
		{
			$query->bindValue($i+1, $values[$i]);
		}
		
		$query->execute();
		
		return $result;
	}
	
	// retrieve a single path from the database cache
	function pathsFromCache($db, $sourceId, $targetId, $targetType)
	{
		$query = $db->prepare('select
				pc.lutionid lutionid,
				pc.learnid learnid,
				pc.edgedestid edgedestid,
				dv.upperid upperid,
				dv.level dvlv,
				ls.level learnlv,
				s.*
			from pathcache pc
			left outer join digivolution dv on pc.lutionid = dv.id
			left outer join learnskill ls on pc.learnid = ls.id
			left outer join stats s on dv.reqstatsid = s.id
			where pc.srcid = ? and pc.destid = ? and pc.desttype = ?
			order by pc.listpos asc;');
		$query->bindValue(1, $sourceId, PDO::PARAM_INT);
		$query->bindValue(2, $targetId, PDO::PARAM_INT);
		$query->bindValue(3, $targetType, PDO::PARAM_INT);
		$query->execute();
		if ($query->rowCount() == 0) return null;
		
		$cachedPath = $query->fetchAll(PDO::FETCH_OBJ);
		$pathSrc = new Node($sourceId, Node::TYPE_DIGIMON);
		$pathDest = new Node($targetId, $targetType);
		$totalCost = new Cost(true);
		$path = new Path($pathDest);
		
		$lastNode = $pathSrc;
		foreach ($cachedPath as $cachedEdge)
		{
			$srcNode = $lastNode;
			$destId = $cachedEdge->edgedestid;
			$cost = new Cost(true);
			
			if ($cachedEdge->lutionid === null)
			{
				$destType = Node::TYPE_SKILL;
				$edgeType = Edge::TYPE_LEARN;
				$edgeId = $cachedEdge->lutionid;
				$cost->setLevels($cachedEdge->learnlv);
			}
			else
			{
				$destType = Node::TYPE_DIGIMON;
				$edgeType = Edge::TYPE_LUTION;
				$edgeId = $cachedEdge->lutionid;
				if ($cachedEdge->upperid == $cachedEdge->edgedestid)
				{
					$cost->setLevels($cachedEdge->dvlv);
				}
			}
			
			$destNode = new Node($destId, $destType);
			$lastNode = $destNode;
			
			$totalCost = $totalCost->add($cost);
			
			array_push($path->edges, new Edge($edgeId, $edgeType, $srcNode, $destNode, $cost));
		}
		
		$path->cost = $totalCost;
		return array($path);
	}
	
	function shortestPathToAnything($db, $sourceId, $targetId, $targetType)
	{
		$paths = pathsFromCache($db, $sourceId, $targetId, $targetType);
		if ($paths == null) $paths = shortestFromId($db, $sourceId);
		
		foreach ($paths as $path)
		{
			if ($path->node->id == $targetId && $path->node->type == $targetType) return $path;
		}
	}
	
	function shortestPathToDigimon($db, $sourceId, $targetId)
	{
		return shortestPathToAnything($db, $sourceId, $targetId, Node::TYPE_DIGIMON);
	}
	
	function shortestPathToSkill($db, $sourceId, $targetId)
	{
		return shortestPathToAnything($db, $sourceId, $targetId, Node::TYPE_SKILL);
	}
	
	function getAllDigimon($db, $namesOnly)
	{
		$queryStr = 'select * from digimon';
		if ($namesOnly) $queryStr = 'select id, name from digimon';
		
		$query = $db->prepare($queryStr);
		$query->execute();
		$rows = $query->fetchAll(\PDO::FETCH_OBJ);
		$result = array();
		foreach ($rows as $row) $result[$row->id] = $row;
		return $result;
	}
	
	function getSomeDigimon($db, $idArray)
	{
		$queryStr = 'select * from digimon where id in (';
		foreach ($idArray as $id)
		{
			$queryStr .= '?,';
		}
		$queryStr = rtrim($queryStr, ',') . ');';
		$query = $db->prepare($queryStr);
		for ($i=0; $i < count($idArray); $i++)
		{
			$query->bindValue($i+1, $idArray[$i], PDO::PARAM_INT);
		}
		$query->execute();
		$rows = $query->fetchAll(PDO::FETCH_OBJ);
		$result = array();
		foreach ($rows as $row) $result[$row->id] = $row;
		return $result;
	}
	
	function getAllSkills($db, $namesOnly)
	{
		$queryStr = 'select * from skill';
		if ($namesOnly) $queryStr = 'select id, name from skill';
		
		$query = $db->prepare($queryStr);
		$query->execute();
		$rows = $query->fetchAll(\PDO::FETCH_OBJ);
		$result = array();
		foreach ($rows as $row) $result[$row->id] = $row;
		return $result;
	}
?>
