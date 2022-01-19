<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Column implements IPillarAnnotation
{
	/**
	 * @Required()
	 */
	protected ?string $name = null;

	/**
	 * @Required()
	 */
	protected string $table;

	protected ?string $customSelect = null;

	protected bool $deprecated = false;

	protected bool $primary = false;

	public function __construct(array $values)
	{
		if (isset($values['value'])) {
			$this->name = $values['value'];
		}
		if (isset($values['name'])) {
			$this->name = $values['name'];
		}
		if (isset($values['table'])) {
			$this->table = $values['table'];
		}
		if (isset($values['primary'])) {
			$this->primary = $values['primary'];
		}
		if (isset($values['deprecated'])) {
			$this->deprecated = $values['deprecated'];
		}
		if (isset($values['customSelect'])) {
			$this->customSelect = $values['customSelect'];
		}
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function getTable(): string
	{
		return $this->table;
	}

	public function isPrimary(): bool
	{
		return $this->primary;
	}

	public function isDeprecated(): bool
	{
		return $this->deprecated;
	}

	public function getCustomSelect(): ?string
	{
		return $this->customSelect;
	}
}
