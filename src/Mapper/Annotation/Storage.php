<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 */
class Storage implements IPillarAnnotation
{
	/**
	 * @Required()
	 */
	protected string $type;

	/**
	 * @param string[] $values
	 */
	public function __construct(array $values)
	{
		if (isset($values['value'])) {
			$this->type = $values['value'];
		}
		if (isset($values['type'])) {
			$this->type = $values['type'];
		}
	}

	public function getType(): string
	{
		return $this->type;
	}
}
