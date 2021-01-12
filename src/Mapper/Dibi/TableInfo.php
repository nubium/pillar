<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Dibi;

class TableInfo
{
	private string $name;

	private string $identifier;

	private ?string $sqlJoinCode;

	public function __construct($name, $identifier, $sqlJoinCode = null)
	{
		$this->name = $name;
		$this->identifier = $identifier;
		$this->sqlJoinCode = $sqlJoinCode;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getSqlJoinCode(): ?string
	{
		return $this->sqlJoinCode;
	}
}
