<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Dibi;

class TableInfo
{
	private string $name;

	private string $identifier;

	private ?string $sqlJoinCode;

	/** @var null|string[] */
	private ?array $tags;

	/**
	 * @param string[]|null $tags
	 */
	public function __construct(string $name, string $identifier, string $sqlJoinCode = null, array $tags = null)
	{
		$this->name = $name;
		$this->identifier = $identifier;
		$this->sqlJoinCode = $sqlJoinCode;
		$this->tags = $tags;
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

	/**
	 * @return string[]|null
	 */
	public function getTags(): ?array
	{
		return $this->tags;
	}
}
