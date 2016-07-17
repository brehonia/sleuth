<?php
	mb_internal_encoding('UTF-8');
	mb_http_output('UTF-8');
	
	class Node
	{
		const TYPE_DIGIMON = 1;
		const TYPE_SKILL = 2;
		
		public $id;
		public $type;
		
		function __construct($id, $type)
		{
			$this->id = (int) $id;
			$this->type = (int) $type;
		}
	}
	
	class Edge
	{
		const TYPE_LUTION = 3;
		const TYPE_LEARN = 4;
		
		public $id;
		public $type;
		public $srcNode;
		public $destNode;
		public $cost;
		
		function __construct($id, $type, $srcNode, $destNode, $cost)
		{
			$this->id = (int) $id;
			$this->type = (int) $type;
			$this->srcNode = $srcNode;
			$this->destNode = $destNode;
			$this->cost = $cost;
		}
	}
	
	class Path
	{
		public $node;
		public $cost;
		public $edges;
		
		function __construct($node)
		{
			$this->node = $node;
			$this->cost = new Cost(false);
			$this->edges = array();
		}
		
		static function cmp($a, $b)
		{
			$aTotal = $a->cost->total();
			$bTotal = $b->cost->total();
			if ($aTotal == $bTotal) return 0;
			return ($aTotal < $bTotal) ? -1 : 1;
		}
		
		static function indexOf($node, $pathArray)
		{
			for ($i = 0; $i < count($pathArray); $i++)
			{
				if ($pathArray[$i]->node == $node) return $i;
			}
		}
	}
	
	class Cost
	{
		const ABI_FUDGE = 5;
		const CAM_FUDGE = 20;
		const TRAINING_FUDGE = 10;
		
		private $levels;
		private $statLevels;
		private $hpTraining;
		private $spTraining;
		private $atkTraining;
		private $defTraining;
		private $intTraining;
		private $spdTraining;
		private $abiTraining;
		private $camTraining;
		private $relaxed;
		
		private $totalCache;
		private $totalUpdated;
		
		function __construct($relaxed)
		{
			$this->relaxed = (bool) $relaxed;
			
			$this->levels = 0;
			$this->statLevels = 0;
			$this->hpTraining = 0;
			$this->spTraining = 0;
			$this->atkTraining = 0;
			$this->defTraining = 0;
			$this->intTraining = 0;
			$this->spdTraining = 0;
			$this->abiTraining = 0;
			$this->camTraining = 0;
			
			$this->totalCache = 0;
			$this->totalUpdated = true;
		}
		
		function getLevels()
		{
			return $this->levels;
		}
		
		function getStatLevels()
		{
			return $this->statLevels;
		}
		
		function getHpTraining()
		{
			return $this->hpTraining;
		}
		
		function getSpTraining()
		{
			return $this->spTraining;
		}
		
		function getAtkTraining()
		{
			return $this->atkTraining;
		}
		
		function getDefTraining()
		{
			return $this->defTraining;
		}
		
		function getIntTraining()
		{
			return $this->intTraining;
		}
		
		function getSpdTraining()
		{
			return $this->spdTraining;
		}
		
		function getAbiTraining()
		{
			return $this->abiTraining;
		}
		
		function getCamTraining()
		{
			return $this->camTraining;
		}
		
		function getRelaxed()
		{
			return $this->relaxed;
		}
		
		function setLevels($lv)
		{
			if ($lv != $this->levels)
			{
				$this->totalUpdated = true;
				$this->levels = $lv;
			}
		}
		
		function setStatLevels($lv)
		{
			if ($lv != $this->statLevels)
			{
				$this->totalUpdated = true;
				$this->statLevels = $lv;
			}
		}
		
		function setHpTraining($a)
		{
			if ($a != $this->hpTraining)
			{
				$this->totalUpdated = true;
				$this->hpTraining = $a;
			}
		}
		
		function setSpTraining($a)
		{
			if ($a != $this->spTraining)
			{
				$this->totalUpdated = true;
				$this->spTraining = $a;
			}
		}
		
		function setAtkTraining($a)
		{
			if ($a != $this->atkTraining)
			{
				$this->totalUpdated = true;
				$this->atkTraining = $a;
			}
		}
		
		function setDefTraining($a)
		{
			if ($a != $this->defTraining)
			{
				$this->totalUpdated = true;
				$this->defTraining = $a;
			}
		}
		
		function setIntTraining($a)
		{
			if ($a != $this->intTraining)
			{
				$this->totalUpdated = true;
				$this->intTraining = $a;
			}
		}
		
		function setSpdTraining($a)
		{
			if ($a != $this->spdTraining)
			{
				$this->totalUpdated = true;
				$this->spdTraining = $a;
			}
		}
		
		function setAbiTraining($a)
		{
			if ($a != $this->abiTraining)
			{
				$this->totalUpdated = true;
				$this->abiTraining = $a;
			}
		}
		
		function setCamTraining($c)
		{
			if ($c != $this->camTraining)
			{
				$this->totalUpdated = true;
				$this->camTraining = $c;
			}
		}
		
		function setRelaxed($r)
		{
			if ($r != $this->relaxed)
			{
				$this->totalUpdated = true;
				$this->relaxed = $r;
			}
		}
		
		function total()
		{
			if (!$this->totalUpdated)
			{
				return $this->totalCache;
			}
			
			$result = (int) $this->levels
				+ (int) $this->statLevels
				+ ((int) $this->hpTraining
					+ (int) $this->spTraining
					+ (int) $this->atkTraining
					+ (int) $this->defTraining
					+ (int) $this->intTraining
					+ (int) $this->spdTraining) * Cost::TRAINING_FUDGE
				+ (int) $this->abiTraining * Cost::ABI_FUDGE
				+ (int) $this->camTraining * Cost::CAM_FUDGE;
				
			if (!$this->relaxed) $result += 99999;
			
			$this->totalCache = $result;
			$this->totalUpdated = false;
			return $result;
		}
		
		function totalLevels()
		{
			return $this->levels + $this->statLevels;
		}
		
		function add($otherCost)
		{
			$result = new Cost(true);
			$result->setLevels($this->levels + $otherCost->levels);
			$result->setStatLevels($this->statLevels + $otherCost->statLevels);
			$result->setHpTraining($this->hpTraining + $otherCost->hpTraining);
			$result->setHpTraining($this->spTraining + $otherCost->hpTraining);
			$result->setHpTraining($this->atkTraining + $otherCost->atkTraining);
			$result->setHpTraining($this->defTraining + $otherCost->defTraining);
			$result->setHpTraining($this->intTraining + $otherCost->intTraining);
			$result->setHpTraining($this->spdTraining + $otherCost->spdTraining);
			$result->setAbiTraining(max($this->abiTraining, $otherCost->abiTraining));
			$result->setCamTraining(max($this->camTraining, $otherCost->camTraining));
			return $result;
		}
	}
?>