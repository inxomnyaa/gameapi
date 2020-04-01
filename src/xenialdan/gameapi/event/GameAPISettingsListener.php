<?php

namespace xenialdan\gameapi\event;

use BadMethodCallException;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Game;
use xenialdan\gameapi\GameAPISettings;

class GameAPISettingsListener implements Listener
{

    /** @var Game|null */
    private static $registrant;
    /** @var GameAPISettings|null */
    private static $settings;

    public static function isRegistered(): bool
    {
        return self::$registrant instanceof Game;
    }

    public static function getRegistrant(): Game
    {
        return self::$registrant;
    }

    public static function unregister(): void
    {
        self::$registrant = null;
    }

    /**
     * @param Game|Plugin $plugin
     */
    public static function register(Game $plugin): void
    {
        if (self::isRegistered()) {
            throw new \Error($plugin->getName() . "attempted to register " . self::class . " twice.");
        }

        self::$registrant = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents(new self(), $plugin);
        if (count(self::getSettings()->lobbies) === 0) {
            API::$generator->generateLevel("game_lobby");
            self::getSettings()->lobbies[] = "game_lobby";
        }
        foreach (self::getSettings()->lobbies as $worldName) {
            $plugin->getServer()->loadLevel($worldName);
        }
    }

    public static function getSettings(): GameAPISettings
    {
        if (self::$settings === null) {
            self::$settings = new GameAPISettings(dirname(self::getRegistrant()->getDataFolder()) . DIRECTORY_SEPARATOR . "GameAPI" . DIRECTORY_SEPARATOR . "settings.json");
            self::$settings->save();
        }
        return self::$settings;
    }

    /**
     * TODO move to API
     * @return Level[]
     */
    public static function getLobbies(): array
    {
        $lobbies = self::getSettings()->lobbies;
        if (empty($lobbies)) $lobbies[] = "game_lobby";
        $levels = [];
        foreach ($lobbies as $lobby) {
            $clean = TextFormat::clean($lobby);
            if (($level = self::getRegistrant()->getServer()->getLevelByName($clean)) instanceof Level) $levels[$clean] = $level;
        }
        return $levels;
    }

    /**
     * @param Level $level
     * @return bool
     */
    public static function isLobby(Level $level): bool
    {
        return in_array(TextFormat::clean($level->getFolderName()), self::getSettings()->lobbies);
    }

    /**
     * @priority HIGH
     * @param BlockPlaceEvent $e
     */
    public function lobbyProtectWorldBuild(BlockPlaceEvent $e): void
    {
        if (self::getSettings()->lobbyProtectWorld === true) {
            if (!$e->getBlock()->isValid() || ($level = $e->getBlock()->getLevel()) === null) return;
            if (!self::isLobby($level)) return;
            $e->setCancelled();
        }
    }

    /**
     * @priority HIGH
     * @param BlockBreakEvent $e
     */
    public function lobbyProtectWorldBreak(BlockBreakEvent $e): void
    {
        if (self::getSettings()->lobbyProtectWorld === true) {
            if (!$e->getBlock()->isValid() || ($level = $e->getBlock()->getLevel()) === null) return;
            if (!self::isLobby($level)) return;
            $e->setCancelled();
        }
    }

    /**
     * @priority HIGH
     * @param InventoryTransactionEvent $e
     * @throws BadMethodCallException
     */
    public function lobbyStaticInventory(InventoryTransactionEvent $e): void
    {
        if (self::getSettings()->lobbyStaticInventory === false) return;
        if (!$e->getTransaction()->getSource()->isValid() || is_null($level = $e->getTransaction()->getSource()->getLevel())) return;
        if (!$e->getTransaction()->getSource() instanceof Player) return;
        if (!self::isLobby($level)) return;
        $e->setCancelled();
    }

}