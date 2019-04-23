<?php


namespace xenialdan\gameapi\gamerule;


class FloatGameRule extends GameRule//Not used yet
{
    /** @var float */
    public $value;

    public function __construct(string $name, float $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return GameRule::TYPE_FLOAT;
    }
}