<?php

namespace xenialdan\gameapi;

use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

abstract class Game extends PluginBase
{
    /** @var Arena[] */
    private static $arenas = [];

    /**
     * The prefix of the game
     * Used for messages and signs
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->getDescription()->getPrefix();
    }

    /**
     * Create and return a new arena, used for addArena in onLoad, setupArena and resetArena (in @see ArenaAsyncCopyTask)
     * @param string $settingsPath The path to the .json file used for the settings. Basename should be levelname
     * @return Arena
     */
    public abstract function getNewArena(string $settingsPath): Arena;

    /**
     * Returns all arenas
     * @return Arena[]
     */
    public function getArenas(): array
    {
        return self::$arenas;
    }

    /**
     * Adds an arena
     * @param Arena $arena
     */
    public function addArena(Arena $arena): void
    {
        self::$arenas[$arena->getLevelName()] = $arena;
    }

    /**
     * Removes an arena
     * @param Arena $arena
     */
    public function removeArena(Arena $arena): void
    {
        unset(self::$arenas[$arena->getLevelName()]);
    }

    /**
     * Stops, removes and deletes the arena config
     * @param Arena $arena
     * @return bool
     */
    public function deleteArena(Arena $arena): bool
    {
        $arena->stopArena();
        $this->removeArena($arena);
        return unlink($this->getDataFolder() . $arena->getLevelName() . ".json");
    }

    /**
     * A method for setting up an arena.
     * @param Player $player The player who will run the setup
     */
    public abstract function setupArena(Player $player): void;

    /**
     * Stops the setup and teleports the player back to the default level
     * @param Player $player
     */
    public function endSetupArena(Player $player): void
    {
        $arena = API::getArenaByLevel($this, $player->getLevel());
        $arena->getSettings()->save();
        $arena->setState(Arena::IDLE);
        $player->getInventory()->clearAll();
        $player->setAllowFlight(false);
        $player->setFlying(false);
        $player->setGamemode($player->getServer()->getDefaultGamemode());
        $player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
    }

    /**
     * The names of the authors
     * @return string;
     */
    public function getAuthors(): string
    {
        return implode(", ", $this->getDescription()->getAuthors());
    }

    /**
     * @param Arena $arena
     */
    public abstract function startArena(Arena $arena): void;

    /**
     * Called AFTER API::stopArena, do NOT use $arena->stopArena() in this function - will result in an recursive call
     * @param Arena $arena
     */
    public abstract function stopArena(Arena $arena): void;

    /**
     * Called right when a player joins a team in an arena of this game. Used to set up players
     * @param Player $player
     */
    public abstract function onPlayerJoinTeam(Player $player): void;

    /**
     * Callback function for array_filter
     * If return value is true, this entity will be deleted.
     * @param Entity $entity
     * @return bool
     */
    public abstract function removeEntityOnArenaReset(Entity $entity): bool;

    /**
     * @return Player[]
     */
    public function getPlayers()
    {
        $players = [];
        foreach ($this->getArenas() as $arena) {
            $players = array_merge($players, $arena->getPlayers());
        }
        return $players;
    }

    public function onDisable()
    {
        try {
            API::stop($this);
        } catch (\ReflectionException $e) {
        }
    }

    public function getArenaByLevelName(string $worldname): ?Arena
    {
        foreach ($this->getArenas() as $arena) {
            if ($arena->getLevelName() === $worldname) return $arena;
        }
        return null;
    }
}