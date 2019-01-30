<?php

namespace xenialdan\gameapi\task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use xenialdan\gameapi\Arena;

class StartTickerTask extends Task {

	private $arena;
    private $plugin;

    public function __construct(Plugin $plugin, Arena $arena)
    {
        $this->plugin = $plugin;
        $this->arena = $arena;
    }

    public function onRun(int $currentTick)
    {
		if ($this->arena instanceof Arena){
            $this->arena->sendTimer($this->getPlugin());
			return;
		} else
            $this->getPlugin()->getScheduler()->cancelTask($this->getTaskId());
		return;
	}

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }
}