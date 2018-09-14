<?php

namespace xenialdan\gameapi\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\Task;
use xenialdan\gameapi\Arena;

class StartTickerTask extends Task {

	private $arena;

	public function onRun(int $currentTick){
		if ($this->arena instanceof Arena){
			$this->arena->sendTimer($this->getOwner());
			return;
		} else
			$this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
		return;
	}
}