<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Dibi;


class ColumnInfo
{
	private string $columnName;

	private string $propertyName;

	private string $tableIdentifier;

	private bool $isPrimaryKey;

	private bool $enabledForSelect = true;

	private ?string $customSelectSql;

	/**
	 * @var bool
	 */
	private $isDeprecated;

	public function __construct(string $columnName, string $propertyName, string $tableIdentifier, bool $isPrimaryKey, bool $isDeprecated, bool $enabledForSelect, string $customSelectSql = null)
	{
		$this->columnName = $columnName;
		$this->propertyName = $propertyName;
		$this->isPrimaryKey = $isPrimaryKey;
		$this->enabledForSelect = $enabledForSelect;
		$this->customSelectSql = $customSelectSql;
		$this->isDeprecated = $isDeprecated;
		$this->tableIdentifier = $tableIdentifier;
	}

	public function getColumnName(): string
	{
		return $this->columnName;
	}

	public function getPropertyName(): string
	{
		return $this->propertyName;
	}

	public function getTableIdentifier(): string
	{
		return $this->tableIdentifier;
	}

	public function isPrimaryKey(): bool
	{
		return $this->isPrimaryKey;
	}

	public function isDeprecated(): bool
	{
		return $this->isDeprecated;
	}

	public function isEnabledForSelect(): bool
	{
		return $this->enabledForSelect;
	}

	public function getCustomSelectSql(): ?string
	{
		return $this->customSelectSql;
	}
}
