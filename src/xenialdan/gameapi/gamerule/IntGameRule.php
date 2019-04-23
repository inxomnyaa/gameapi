<?php


namespace xenialdan\gameapi\gamerule;


class IntGameRule extends GameRule
{
    /** @var int */
    public $value;

    public function __construct(string $name, int $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return GameRule::TYPE_INT;
    }
}