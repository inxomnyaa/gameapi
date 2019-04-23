<?php

namespace xenialdan\gameapi;

use pocketmine\Player;

interface Game
{

    /**
     * The name of the game, lowercase
     * @return string;
     */
    public function getName(): string;

    /**
     * The prefix of the game
     * Used for messages and signs
     * @return string;
     */
    public function getPrefix(): string;

    /**
     * TODO: check if this should be in a minigame, instead of loader
     * Returns all arenas
     * @return Arena[]
     */
    public function getArenas(): array;

    /**
     * TODO: check if this should be in a minigame, instead of loader
     * Adds an arena
     * @param Arena $arena
     */
    public function addArena(Arena $arena): void;

    /**
     * TODO: check if this should be in a minigame, instead of loader
     * Removes an arena
     * @param Arena $arena
     */
    public function removeArena(Arena $arena): void;

    /**
     * A method for setting up an arena.
     * @param Player $player The player who will run the setup
     */
    public function setupArena(Player $player): void;

    /**
     * Stops the setup and teleports the player back to the default level
     * @param Player $player
     */
    public function endSetupArena(Player $player): void;

    /**
     * The names of the authors
     * @return string;
     */
    public function getAuthors(): string;

    /**
     * @param Arena $arena
     */
    public function startArena(Arena $arena): void;

    /**
     * TODO use this
     * @param Arena $arena
     */
    public function stopArena(Arena $arena): void;

    /**
     * Called right when a player joins a game in an arena. Used to set up players
     * @param Player $player
     */
    public function onPlayerJoinGame(Player $player): void;
}