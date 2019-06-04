<?php

namespace xenialdan\gameapi;

use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\event\UpdateSignsEvent;
use xenialdan\gameapi\task\ArenaAsyncCopyTask;

abstract class Game extends PluginBase
{
    /** @var Arena[] */
    private $arenas = [];

    public function __construct(PluginLoader $loader, Server $server, PluginDescription $description, string $dataFolder, string $file)
    {
        parent::__construct($loader, $server, $description, $dataFolder, $file);
    }

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
        return $this->arenas;
    }

    /**
     * Adds an arena
     * @param Arena $arena
     */
    public function addArena(Arena $arena): void
    {
        $this->arenas[$arena->getLevelName()] = $arena;
        $ev = new UpdateSignsEvent($this, [$this->getServer()->getDefaultLevel()], $arena);
        try {
            $ev->call();
        } catch (\ReflectionException $e) {
            Server::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * Removes an arena
     * @param Arena $arena
     */
    public function removeArena(Arena $arena): void
    {
        unset($this->arenas[$arena->getLevelName()]);
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
        $player->getLevel()->save();
        $player->getServer()->getAsyncPool()->submitTask(new ArenaAsyncCopyTask($player->getServer()->getDataPath(), $this->getDataFolder(), $player->getLevel()->getFolderName(), $this->getName()));
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

    /**
     * Returns an array with the games status. Should only be called if this game (plugin) is enabled
     * @return string[]
     */
    public function getStatusLines(): array
    {
        $lines = [];
        $arenas = [];
        $loads = [];
        foreach ($this->getArenas() as $arena) {
            switch ($arena->getState()) {
                case Arena::IDLE:
                    {
                        $status = TextFormat::GREEN . "Empty";
                        break;
                    }
                case Arena::WAITING:
                    {
                        $status = TextFormat::GREEN . "Needs players";
                        break;
                    }
                case Arena::STARTING:
                    {
                        $status = TextFormat::GOLD . "Starting";
                        break;
                    }
                case Arena::INGAME:
                    {
                        $status = TextFormat::RED . "Running";
                        break;
                    }
                case Arena::STOP:
                    {
                        $status = TextFormat::RED . "Reloading";
                        break;
                    }
                case Arena::SETUP:
                    {
                        $status = TextFormat::DARK_RED . "Inaccessible";
                        break;
                    }
                default:
                    {
                        $status = "Unknown";
                    }
            }
            $playercount = round(count($this->getServer()->getOnlinePlayers()) / max(1, count($arena->getPlayers())), 0) * 100;
            $arenas[] = "\"" . $arena->getLevelName() . "\" - State: " . $status . TextFormat::RESET . " Players: " . count($arena->getPlayers()) . "/" . $arena->getMaxPlayers() . " (" . $playercount . "%) Time: " . round($arena->getLevel()->getTickRateTime(), 2) . "ms";
            $loads[] = $arena->getLevel()->getTickRateTime();
        }
        $lines["Players Total"] = TextFormat::RESET . "N/A";
        $lines["Time"] = TextFormat::RESET . "N/A";
        $lines["Arenas"] = TextFormat::RESET . "N/A";
        if (!empty($loads))
            $lines["Time"] = TextFormat::RESET . "Average: " . (round((array_sum($loads) / count($loads)), 2)) . "ms Combined: " . round(array_sum($loads), 2) . "ms";

        if (!empty($arenas)) {
            $lines["Players Total"] = TextFormat::RESET . count($this->getPlayers()) . " " . count($this->getServer()->getOnlinePlayers()) / max(1, count($this->getPlayers())) . "%";
            $lines["Arenas"] = PHP_EOL . TextFormat::RESET . " - " . implode(PHP_EOL . TextFormat::RESET . " - ", $arenas);
        }
        return $lines;
    }
}