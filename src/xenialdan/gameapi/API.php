<?php

namespace xenialdan\gameapi;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\event\StopGameEvent;
use xenialdan\gameapi\task\DelayLoadTask;

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
	public static function stop($plugin){
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
		$server->broadcastMessage(TextFormat::GREEN . "Stopped " . $plugin->getName());
		return true;
	}

	public static function resetArena(Arena $arena){
		$level = $arena->getLevel();
		$levelname = $level->getName();
		$server = $level->getServer();
		$arena->setState(Arena::STOP);
		if ($server->isLevelLoaded($levelname)){
			foreach ($level->getEntities() as $entity){
				$level->removeEntity($entity);
			}
			Server::getInstance()->broadcastMessage('Level ' . $levelname . ($server->unloadLevel($level) ? ' successfully' : ' NOT') . ' unloaded!');
			$path1 = $arena->getOwningGame()->getDataFolder();
			if (self::copyr($path1 . "/worlds/" . $levelname, $server->getDataPath() . "/worlds/" . $levelname)){
				$server->getScheduler()->scheduleDelayedTask(new DelayLoadTask($arena->getOwningGame(), $arena, $levelname), 20);
			}
		}
		$arena->setState(Arena::IDLE);
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
	public static function getTeamOfPlayer(Player $player){
		$arena = self::getArenaOfPlayer($player);
		if(is_null($arena)) return null;
		return $arena->getTeamByPlayer($player);
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
	 * @param Game $game
	 */
	public static function registerGame(Game $game){
		self::$games[$game->getName()] = $game;
		Server::getInstance()->getLogger()->notice('Registered game ' . $game->getName() . ' by ' . $game->getAuthors());
	}

	/**
	 * @return Game[]
	 */
	public static function getGames(){
		return self::$games;
	}

	/**
	 * Returns all arenas of a Game
	 * @param Game $game
	 * @return Arena[]
	 */
	public static function getArenas(Plugin $game){
		return $game->getArenas();
	}

	/**
	 * @param Game $game
	 * @param Level $level
	 * @return bool
	 */
	public static function isArena(Plugin $game, Level $level){
		return (in_array($level, array_map(function (Arena $arena){
			return $arena->getLevel();
		}, $game->getArenas())));
	}

	/**
	 * @param Game $game
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