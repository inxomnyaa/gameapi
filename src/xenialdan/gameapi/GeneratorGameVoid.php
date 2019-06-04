<?php

namespace xenialdan\gameapi;

use pocketmine\level\biome\Biome;
use pocketmine\Server;

class GeneratorGameVoid
{

    public function generateLevel(string $levelName): bool
    {
        return Server::getInstance()->generateLevel($levelName, null, 'pocketmine\level\generator\Flat', ["preset" => "3;minecraft:air;" . Biome::PLAINS . ";"]);
    }
}