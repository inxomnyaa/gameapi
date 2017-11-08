<?php

namespace xenialdan\gameapi\task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use xenialdan\gameapi\Arena;

class StartTickerTask extends PluginTask{

	private $arena;

	public function __construct(Plugin $plugin, Arena $arena){
		parent::__construct($plugin);
		$this->arena = $arena;
	}

	public function onRun(int $currentTick){
		if ($this->arena instanceof Arena){
			$this->arena->sendTimer($this->getOwner());
			return;
		} else
			$this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
		return;
	}
}