<?php

namespace xenialdan\gameapi;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\event\RegisterGameEvent;
use xenialdan\gameapi\event\StopGameEvent;

class API{
	/** @var Game[] */
	private static $games;

	/**
	 * Stops ALL games using the API
	 * Whatever the plugin is, only plugins that are games and "react" to the event are using the StopGameEvent
	 */
	public static function stopAll(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$server = Server::getInstance();
		foreach ($server->getPluginManager()->getPlugins() as $game){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			$server->getPluginManager()->callEvent($ev = new StopGameEvent($game));
		}
		$server->broadcastMessage(TextFormat::GREEN . "Stopped all games.");
	}

	/**
	 * Stops 1 game using the API
	 * Whatever the plugin is, only plugins that are games and "react" to the event are using the StopGameEvent
	 * @param Game|string $plugin a plugin or plugin name
	 * @return bool
	 */
	public static function stop($plugin){//TODO stop each arena
		$server = Server::getInstance();
		if (!$plugin instanceof Game && !is_string($plugin)) return false;
		if (is_string($plugin)){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			$plugin = $server->getPluginManager()->getPlugin($plugin);
		}
		if (!$plugin instanceof Game){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			$server->broadcastMessage(TextFormat::RED . "There is no such plugin/minigame");
			return false;
		}
		$server->getPluginManager()->callEvent($ev = new StopGameEvent($plugin));
		$server->broadcastMessage(TextFormat::GREEN . "Stopped " . $ev->getName());
		return true;
	}

	public static function resetArena(Arena $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$level = $arena->getLevel();
		$levelname = $arena->getLevelName();
		#$server = $level->getServer();
		$server = Server::getInstance();

		$arena->stopArena(); //TODO use this

		if ($server->isLevelLoaded($levelname)){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			#foreach ($level->getEntities() as $entity){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			#	$level->removeEntity($entity);
			#}
			#$server->unloadLevel($level);
			Server::getInstance()->getLogger()->notice('Level ' . $levelname . ($server->unloadLevel($server->getLevelByName($levelname)) ? ' successfully' : ' NOT'). ' unloaded!');
			$path1 = $arena->getOwningGame()->getDataFolder();
			var_dump($path1);
			if (self::copyr($path1 . "worlds/" . $levelname, $server->getDataPath() . "worlds/" . $levelname)){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
				// Delayed loading
				$server->getScheduler()->scheduleDelayedRepeatingTask(new class($arena->getOwningGame(), $arena) extends PluginTask{

					private $arena;
					private $levelname;
					private $tries = 0;

					/**
					 * DelayLoadTask constructor.
					 * @param Plugin $plugin
					 * @param Arena $arena
					 */
					public function __construct(Plugin $plugin, Arena $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
						parent::__construct($plugin);
						$this->arena = $arena;
						$this->levelname = $arena->getLevelName();
					}

					public function onRun(int $currentTick){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
						if ($this->arena instanceof Arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
							if (!Server::getInstance()->isLevelLoaded($this->levelname))
								Server::getInstance()->getLogger()->notice('Level ' . $this->levelname . (Server::getInstance()->loadLevel($this->levelname) ? ' successfully' : ' NOT') . ' reloaded!');
							$this->tries++;
							if ($this->tries >= 10){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
								Server::getInstance()->broadcastMessage('Level ' . $this->levelname . ' could not be reloaded, disabling arena ' . $this->levelname . ' for the game ' . $this->arena->getOwningGame()->getPrefix());
								$this->arena->getOwningGame()->removeArena($this->arena);

								Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
								return;
							}
							$this->arena->setLevel(Server::getInstance()->getLevelByName($this->levelname));
							//Prevents changing the level
							#$this->arena->getLevel()->setAutoSave(false);
							return;
						} else
							Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
						return;
					}
				}, 20 * 10, 20);

				// Delayed status setting
				$server->getScheduler()->scheduleDelayedTask(new class($arena->getOwningGame(), $arena) extends PluginTask{
					public $arena;

					/**
					 * @param Plugin $plugin
					 * @param Arena $arena
					 */
					public function __construct(Plugin $plugin, Arena $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
						parent::__construct($plugin);
						$this->arena = $arena;
					}

					public function onRun(int $currentTick){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
						if ($this->arena instanceof Arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
							#$this->arena->setState(Arena::IDLE);
						}
					}
				}, 50);
			}
		}
	}

	public static function copyr($source, $dest){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		// Check for symlinks
		if (is_link($source)){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			return symlink(readlink($source), $dest);
		}

		// Simple copy for a file
		if (is_file($source)){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			return copy($source, $dest);
		}

		// Make destination directory
		if (!is_dir($dest)){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			mkdir($dest);
		}

		// Loop through the folder
		$dir = dir($source);
		while (false !== $entry = $dir->read()){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			// Skip pointers
			if ($entry == '.' || $entry == '..'){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
				continue;
			}

			// Deep copy directories
			self::copyr("$source/$entry", "$dest/$entry");
		}

		// Clean up
		$dir->close();
		return true;
	}

	/** Gets the team a player is in
	 * @param Player $player
	 * @return null|Team
	 */
	public static function getTeamOfPlayer(Player $player){//TODO check if mistake
		$arena = self::getArenaOfPlayer($player);
		if (is_null($arena)) return null;
		return $arena->getTeamByPlayer($player);
	}

	/** Gets the arena a player is in
	 * @param Player $player
	 * @return Arena | null
	 */
	public static function getArenaOfPlayer(Player $player){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		foreach (self::getGames() as $game){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			#if (!self::isPlaying($game, $player)) continue;
			foreach (self::getArenas($game) as $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
				if ($arena->inArena($player)) return $arena;
			}
		}
		return null;
	}

	public static function isPlaying(Plugin $game, Player $gamer){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return !is_null(self::isArena($game, $gamer->getLevel()));
	}

	/**
	 * Register a plugin as a game
	 * @param Plugin|Game $game
	 */
	public static function registerGame(Game $game){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		self::$games[$game->getName()] = $game;
		Server::getInstance()->getPluginManager()->callEvent($ev = new RegisterGameEvent($game, $game));
		Server::getInstance()->getLogger()->notice('Registered game ' . $ev->getName() . ' by ' . $ev->getGame()->getAuthors());
	}

	/**
	 * @return Game[]
	 */
	public static function getGames(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return self::$games;
	}

	/**
	 * @param string $name
	 * @return null|Game
	 */
	public static function getGame(string $name){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return self::$games[$name] ?? null;
	}

	/**
	 * Returns all arenas of a Game
	 * @param Plugin|Game $game
	 * @return Arena[]
	 */
	public static function getArenas(Plugin $game){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $game->getArenas();
	}

	/**
	 * @param Plugin|Game $game
	 * @param Level $level
	 * @return bool
	 */
	public static function isArena(Plugin $game, Level $level){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return (in_array($level->getName(), array_map(function (Arena $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			return $arena->getLevelName();
		}, self::getArenas($game))));
	}

	/**
	 * @param Plugin|Game $game
	 * @param Level $level
	 * @return Arena
	 */
	public static function getArenaByLevel(Plugin $game, Level $level){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$id = array_search($level, array_map(function (Arena $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			return $arena->getLevel();
		}, $game->getArenas()));
		return $game->getArenas()[$id];
	}

	/**
	 * @param string $color a TextFormat color constant
	 * @return Color
	 */
	public static function colorFromTextFormat($color){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		list($r, $g, $b) = str_split(ltrim(str_replace('>', '', str_replace('<span style=color:#', '', TextFormat::toHTML($color))), '#'));
		return new Color(...array_map('hexdec', [$r . $r, $g . $g, $b . $b]));
	}

	/**
	 * Returns a fitting meta for a team color
	 * @param string $color a TextFormat constant
	 * @return int $meta
	 */
	public static function getMetaByColor(string $color){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		switch ($color){
			case TextFormat::BLACK:
				return 15;
			case TextFormat::DARK_BLUE:
				return 11;
			case TextFormat::DARK_GREEN :
				return 13;
			case TextFormat::DARK_AQUA :
			case TextFormat::AQUA :
				return 9;
			case TextFormat::DARK_RED :
			case TextFormat::RED :
				return 14;
			case TextFormat::DARK_PURPLE :
				return 10;
			case TextFormat::GOLD :
				return 1;
			case TextFormat::GRAY :
				return 8;
			case TextFormat::DARK_GRAY :
				return 7;
			case TextFormat::BLUE :
				return 3;
			case TextFormat::GREEN :
				return 5;
			case TextFormat::LIGHT_PURPLE :
				return 2;
			case TextFormat::YELLOW :
				return 4;
			case TextFormat::WHITE :
				return 0;
			default:
				return -1;
		}
	}


	public static function setCustomColor(Item $item, Color $color){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		if (($hasTag = $item->hasCompoundTag())){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			$tag = $item->getNamedTag();
		} else{
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			$tag = new CompoundTag("", []);
		}
		$tag->customColor = new IntTag("customColor", self::toRGB($color));
		$item->setCompoundTag($tag);
		return $item;
	}

	/**
	 * Returns an RGB 32-bit colour value.
	 * @param Color $color
	 * @return int
	 */
	public static function toRGB(Color $color): int{
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return ($color->getR() << 16) | ($color->getG() << 8) | $color->getB() & 0xffffff;
	}
}