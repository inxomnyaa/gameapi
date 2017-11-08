<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\level\Level;
use pocketmine\plugin\Plugin;
use pocketmine\tile\Sign as SignTile;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\Arena;

class UpdateSignsEvent extends PluginEvent{
	public static $handlerList = null;
	private $levels;
	/** @var Arena */
	private $arena;

	/**
	 * UpdateSignsEvent constructor.
	 * @param Plugin $plugin
	 * @param Level[] $levels
	 * @param Arena $arena
	 */
	public function __construct(Plugin $plugin, $levels, Arena $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		parent::__construct($plugin);
		$this->levels = $levels;
		$this->arena = $arena;
	}

	public function updateSigns(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		foreach ($this->levels as $level){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			if (!$level instanceof Level) continue;
			foreach (array_filter($level->getTiles(), function (Tile $tile){ return $tile instanceof SignTile; }) as $tile){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
				/** @var SignTile $tile */
				$lines = $tile->getText();
				if (strtolower(TextFormat::clean($lines[0])) === strtolower(TextFormat::clean($this->arena->getOwningGame()->getPrefix()))){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
					if (TextFormat::clean($lines[1]) === $this->arena->getLevelName()){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
						$state = $this->arena->getState();
						switch ($state){
							case Arena::IDLE: {
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
								$status = "Empty/Idle";
								break;
							}
							case Arena::WAITING: {
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
								$status = "Need players";
								break;
							}
							case Arena::STARTING: {
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
								$status = "Starting";
								break;
							}
							case Arena::INGAME: {
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
								$status = "Running";
								break;
							}
							case Arena::STOP: {
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
								$status = "Reloading";
								break;
							}
							default: {
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
								$status = "Unknown";
							}
						}
						$playerline = TextFormat::AQUA . "[" . (count($this->arena->getPlayers()) === $this->arena->getMaxPlayers() ? TextFormat::RED : TextFormat::GREEN) . count($this->arena->getPlayers()) . TextFormat::AQUA . "|" . (count($this->arena->getPlayers()) === $this->arena->getMaxPlayers() ? TextFormat::RED : TextFormat::GREEN) . $this->arena->getMaxPlayers() . '-' . $this->arena->getMaxPlayers() . TextFormat::AQUA . "]";
						$tile->setText($lines[0], $lines[1], $status, $playerline);
					}
				}
			}
		}
	}
}