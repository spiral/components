<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Security;

use Interop\Container\ContainerInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Security\Exceptions\RuleException;
use Spiral\Security\Rules\CallableRule;
use Spiral\Support\Patternizer;

/**
 * Provides ability to request permissions rules based on it's name.
 */
class RuleManager implements RulesInterface, SingletonInterface
{
    /**
     * @var array
     */
    private $rules = [];

    /**
     * @var Patternizer
     */
    private $patternizer = null;

    /**
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * RuleManager constructor.
     *
     * @param ContainerInterface $container
     * @param Patternizer|null   $patternizer
     */
    public function __construct(ContainerInterface $container, Patternizer $patternizer)
    {
        $this->container = $container;
        $this->patternizer = $patternizer;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function set(string $name, $rule = null): RuleManager
    {
        if (empty($rule)) {
            $rule = $name;
        }

        if (!$this->validateRule($rule)) {
            throw new RuleException("Unable to set rule '{$name}', invalid rule body");
        }

        $this->rules[$name] = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function remove(string $name): RuleManager
    {
        if (!$this->has($name)) {
            throw new RuleException("Undefined rule '{$name}'");
        }

        unset($this->rules[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        if (isset($this->rules[$name])) {
            return true;
        }

        if (class_exists($name)) {
            //We are allowing to use class names without direct registration
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): RuleInterface
    {
        if (!$this->has($name)) {
            throw new RuleException("Undefined rule '{$name}'");
        }

        if (!isset($this->rules[$name])) {
            //Rule represented as class name
            $rule = $name;
        } else {
            $rule = $this->rules[$name];
        }

        if ($rule instanceof RuleInterface) {
            return $rule;
        }

        if (is_string($rule)) {
            //We are expecting that rule points to
            $rule = $this->container->get($rule);

            if (!$rule instanceof RuleInterface) {
                throw new RuleException(
                    "Rule '{$name}' must point to RuleInterface, '" . get_class($rule) . "' given"
                );
            }

            return $rule;
        }

        //We have to respond using RuleInterface (expecting that rule is callable)
        return new CallableRule($rule);
    }

    /**
     * @param mixed $rule
     *
     * @return bool
     */
    private function validateRule($rule): bool
    {
        if ($rule instanceof \Closure || $rule instanceof RuleInterface) {
            return true;
        }

        if (is_array($rule)) {
            return is_callable($rule, true);
        }

        if (is_string($rule) && class_exists($rule)) {
            $reflection = new \ReflectionClass($rule);

            return $reflection->isSubclassOf(RulesInterface::class);
        }

        return false;
    }
}
