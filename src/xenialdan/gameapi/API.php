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
use xenialdan\gameapi\task\ArenaAsyncCopyTask;

class API{
	/** @var Game[] */
	private static $games;

	/**
	 * Stops ALL games using the API
	 * Whatever the plugin is, only plugins that are games and "react" to the event are using the StopGameEvent
	 */
	public static function stopAll(){
		$server = Server::getInstance();
		foreach ($server->getPluginManager()->getPlugins() as $game){
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
			$plugin = $server->getPluginManager()->getPlugin($plugin);
		}
		if (!$plugin instanceof Game){
			$server->broadcastMessage(TextFormat::RED . "There is no such plugin/minigame");
			return false;
		}
		$server->getPluginManager()->callEvent($ev = new StopGameEvent($plugin));
		$server->broadcastMessage(TextFormat::GREEN . "Stopped " . ($ev->getName() ?? "nameless game"));
		return true;
	}

	public static function resetArena(Arena $arena){
		$level = $arena->getLevel();
		$levelname = $arena->getLevelName();
		#$server = $level->getServer();
		$server = Server::getInstance();

		$arena->stopArena(); //TODO use this

		if ($server->isLevelLoaded($levelname)){
			foreach ($level->getEntities() as $entity){
				$level->removeEntity($entity);
			}
			#$server->unloadLevel($level);
			Server::getInstance()->getLogger()->notice('Level ' . $levelname . ($server->unloadLevel($server->getLevelByName($levelname)) ? ' successfully' : ' NOT') . ' unloaded!');
			$path1 = $arena->getOwningGame()->getDataFolder();
			var_dump($path1);

			$server->getScheduler()->scheduleAsyncTask(new ArenaAsyncCopyTask($path1, $server->getDataPath(), $levelname));

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
					parent::__construct($plugin);
					$this->arena = $arena;
					$this->levelname = $arena->getLevelName();
				}

				public function onRun(int $currentTick){
					if ($this->arena instanceof Arena){
						if (Server::getInstance()->loadLevel($this->levelname)){
							Server::getInstance()->getLogger()->notice('Level ' . $this->levelname . ' successfully reloaded!');
							Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
						}
						$this->tries++;
						if ($this->tries >= 10){
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
					parent::__construct($plugin);
					$this->arena = $arena;
				}

				public function onRun(int $currentTick){
					if ($this->arena instanceof Arena){
						$this->arena->setState(Arena::IDLE);
					}
				}
			}, 50 * 10);
		}
	}

	public static function copyr($source, $dest){
		// Check for symlinks
		if (is_link($source)){
			return symlink(readlink($source), $dest);
		}

		// Simple copy for a file
		if (is_file($source)){
			return copy($source, $dest);
		}

		// Make destination directory
		if (!is_dir($dest)){
			mkdir($dest);
		}

		// Loop through the folder
		$dir = dir($source);
		while (false !== $entry = $dir->read()){
			// Skip pointers
			if ($entry == '.' || $entry == '..'){
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

	/** Gets the team a player is in
	 * @param Player $player
	 * @param string $color
	 * @return null|Team
	 */
	public static function getTeamByColor(Player $player, string $color){//TODO check if mistake
		$arena = self::getArenaOfPlayer($player);
		if (is_null($arena)) return null;
		return $arena->getTeamByColor($color);
	}

	/** Gets the arena a player is in
	 * @param Player $player
	 * @return Arena | null
	 */
	public static function getArenaOfPlayer(Player $player){
		foreach (self::getGames() as $game){
			#if (!self::isPlaying($game, $player)) continue;
			foreach (self::getArenas($game) as $arena){
				if ($arena->inArena($player)) return $arena;
			}
		}
		return null;
	}

	public static function isPlaying(Plugin $game, Player $gamer){
		return !is_null(self::isArena($game, $gamer->getLevel()));
	}

	/**
	 * Register a plugin as a game
	 * @param Plugin|Game $game
	 */
	public static function registerGame(Game $game){
		self::$games[$game->getName()] = $game;
		Server::getInstance()->getPluginManager()->callEvent($ev = new RegisterGameEvent($game, $game));
		Server::getInstance()->getLogger()->notice('Registered game ' . $ev->getName() . ' by ' . $ev->getGame()->getAuthors());
	}

	/**
	 * @return Game[]
	 */
	public static function getGames(){
		return self::$games;
	}

	/**
	 * @param string $name
	 * @return null|Game
	 */
	public static function getGame(string $name){
		return self::$games[$name] ?? null;
	}

	/**
	 * Returns all arenas of a Game
	 * @param Plugin|Game $game
	 * @return Arena[]
	 */
	public static function getArenas(Plugin $game){
		return $game->getArenas();
	}

	/**
	 * @param Plugin|Game $game
	 * @param Level $level
	 * @return bool
	 */
	public static function isArena(Plugin $game, Level $level){
		return (in_array($level->getName(), array_map(function (Arena $arena){ //TODO check if this is the cause for crashes
			return $arena->getLevelName();
		}, self::getArenas($game))));
	}

	/**
	 * @param Plugin|Game $game
	 * @param Level $level
	 * @return Arena
	 */
	public static function getArenaByLevel(Plugin $game, Level $level){
		$id = array_search($level, array_map(function (Arena $arena){
			return $arena->getLevel();
		}, $game->getArenas()));
		return $game->getArenas()[$id];
	}

	/**
	 * @param string $color a TextFormat color constant
	 * @return Color
	 */
	public static function colorFromTextFormat($color){
		list($r, $g, $b) = str_split(ltrim(str_replace('>', '', str_replace('<span style=color:#', '', TextFormat::toHTML($color))), '#'));
		return new Color(...array_map('hexdec', [$r . $r, $g . $g, $b . $b]));
	}

	/**
	 * Returns a fitting meta for a team color
	 * @param string $color a TextFormat constant
	 * @return int $meta
	 */
	public static function getMetaByColor(string $color){
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

	/**
	 * Returns a fitting meta for a team color
	 * @param int $meta
	 * @return string $color a TextFormat constant
	 */
	public static function getColorByMeta(int $meta){
		switch ($meta){
			case 0:
			default:
				return TextFormat::WHITE;
			case 1:
				return TextFormat::GOLD;
			case 2:
				return TextFormat::LIGHT_PURPLE;
			case 3:
				return TextFormat::BLUE;
			case 4:
				return TextFormat::YELLOW;
			case 5:
				return TextFormat::GREEN;
			case 7:
				return TextFormat::DARK_GRAY;
			case 8:
				return TextFormat::GRAY;
			case 9:
				return TextFormat::AQUA;
			case 10:
				return TextFormat::DARK_PURPLE;
			case 11:
				return TextFormat::DARK_BLUE;
			case 13:
				return TextFormat::DARK_GREEN;
			case 14:
				return TextFormat::RED;
			case 15:
				return TextFormat::BLACK;
		}
	}

	public static function setCustomColor(Item $item, Color $color){
		if (($hasTag = $item->hasCompoundTag())){
			$tag = $item->getNamedTag();
		} else{
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
		return ($color->getR() << 16) | ($color->getG() << 8) | $color->getB() & 0xffffff;
	}
}