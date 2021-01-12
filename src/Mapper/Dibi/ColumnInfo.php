<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Dibi;


class ColumnInfo
{
	private string $columnName;

	private string $propertyName;

	/**
	 * @var TableInfo
	 */
	private $tableInfo;

	private bool $isPrimaryKey;

	private bool $enabledForSelect = true;

	private ?string $customSelectSql;

	/**
	 * @var bool
	 */
	private $isDeprecated;

	public function __construct($columnName, $propertyName, TableInfo $tableInfo, $isPrimaryKey, $isDeprecated, $enabledForSelect, $customSelectSql = null)
	{
		$this->columnName = $columnName;
		$this->propertyName = $propertyName;
		$this->tableInfo = $tableInfo;
		$this->isPrimaryKey = $isPrimaryKey;
		$this->enabledForSelect = $enabledForSelect;
		$this->customSelectSql = $customSelectSql;
		$this->isDeprecated = $isDeprecated;
	}

	public function getColumnName(): string
	{
		return $this->columnName;
	}

	public function getPropertyName(): string
	{
		return $this->propertyName;
	}

	public function getTableInfo()
	{
		return $this->tableInfo;
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
