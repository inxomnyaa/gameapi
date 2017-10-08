<?php

namespace xenialdan\gameapi;

interface Game{

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
	public function getArenas();

	/**
	 * TODO: check if this should be in a minigame, instead of loader
	 * Adds an arena
	 * @param Arena $arena
	 */
	public function addArena(Arena $arena);

	/**
	 * TODO: check if this should be in a minigame, instead of loader
	 * Removes an arena
	 * @param Arena $arena
	 */
	public function removeArena(Arena $arena);

	/**
	 * The names of the authors
	 * @return string;
	 */
	public function getAuthors();

	/**
	 * @param Arena $arena
	 */
	public function startArena(Arena $arena);
}