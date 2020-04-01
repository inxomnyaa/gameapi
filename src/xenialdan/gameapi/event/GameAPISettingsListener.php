<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
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
    }

    public static function getSettings(): GameAPISettings
    {
        if (self::$settings === null) {
            self::$settings = new GameAPISettings(dirname(self::getRegistrant()->getDataFolder()) . DIRECTORY_SEPARATOR . "GameAPI" . DIRECTORY_SEPARATOR . "settings.json");
            self::$settings->save();
        }
        return self::$settings;
    }

}