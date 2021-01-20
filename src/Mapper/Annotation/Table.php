<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Table implements IPillarAnnotation
{
    const DEFAULT_TAG = 'default';

	/**
	 * @Required()
	 */
	protected string $name;

	protected string $identifier = '';

	protected ?string $code = null;

    /** @var null|string[] */
	protected ?array $tags = null;

	public function __construct($values)
	{
		if (isset($values['value'])) {
			$this->name = $values['value'];
		}
		if (isset($values['name'])) {
			$this->name = $values['name'];
		}
		$this->identifier = $this->name;
		if (isset($values['identifier'])) {
			$this->identifier = $values['identifier'];
		}
		if (isset($values['code'])) {
			$this->code = $values['code'];
		}
		if (isset($values['tags'])) {
			$this->tags = $values['tags'];
		}
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getCode(): ?string
	{
		return $this->code;
	}

    /**
     * @return string[]|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }
}
