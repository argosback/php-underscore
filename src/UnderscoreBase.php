<?php

namespace Ahc\Underscore;

class UnderscoreBase implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    const VERSION = '0.0.1';

    /** @var array The array manipulated by this Underscore instance */
    protected $data;

    /**
     * Constructor.
     *
     * @param array|mixed $data Array or array like or array convertible.
     */
    public function __construct($data = [])
    {
        $this->data = \is_array($data) ? $data : $this->asArray($data);
    }

    /**
     * Get the underlying array data.
     *
     * @param string|int|null $index
     *
     * @return mixed
     */
    public function get($index = null)
    {
        if (null === $index) {
            return $this->data;
        }

        return $this->data[$index];
    }

    /**
     * Get data as array.
     *
     * @param mixed $data Arbitrary data.
     * @param bool  $cast Force casting to array!
     *
     * @return array
     */
    public function asArray($data, $cast = true)
    {
        if (\is_array($data)) {
            return $data;
        }

        if ($data instanceof static) {
            return $data->get();
        }

        // @codeCoverageIgnoreStart
        if ($data instanceof \Traversable) {
            return \iterator_to_array($data);
        }
        // @codeCoverageIgnoreEnd

        if ($data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        }

        if (\method_exists($data, 'toArray')) {
            return $data->toArray();
        }

        return  $cast ? (array) $data : $data;
    }

    /**
     * Convert the data items to array.
     *
     * @return array
     */
    public function toArray()
    {
        return \array_map(function ($value) {
            if (\is_scalar($value)) {
                return $value;
            }

            return $this->asArray($value, false);
        }, $this->data);
    }

    /**
     * Flatten a multi dimension array to 1 dimension.
     *
     * @param array $array
     *
     * @return array
     */
    public function flat($array, &$flat = [])
    {
        foreach ($array as $value) {
            if ($value instanceof static) {
                $value = $value->get();
            }

            if (\is_array($value)) {
                $this->flat($value, $flat);
            } else {
                $flat[] = $value;
            }
        }

        return $flat;
    }

    /**
     * Negate a given truth test callable.
     *
     * @param callable $fn
     *
     * @return callable
     */
    protected function negate(callable $fn)
    {
        return function () use ($fn) {
            return !\call_user_func_array($fn, \func_get_args());
        };
    }

    /**
     * Get a value generator callable.
     *
     * @param callable|string|null $fn
     *
     * @return callable
     */
    protected function valueFn($fn)
    {
        if (\is_callable($fn)) {
            return $fn;
        }

        return function ($value) use ($fn) {
            if (null === $fn) {
                return $value;
            }

            $value = \array_column([$value], $fn);

            return  $value ? $value[0] : null;
        };
    }

    /**
     * Checks if offset/index exists.
     *
     * @param string|int $index
     *
     * @return bool
     */
    public function offsetExists($index)
    {
        return \array_key_exists($index, $this->data);
    }

    /**
     * Gets the value at given offset/index.
     *
     * @return mixed
     */
    public function offsetGet($index)
    {
        return $this->data[$index];
    }

    /**
     * Sets a new value at the given offset/index.
     *
     * @param string|int $index
     * @param mixed      $value
     *
     * @return void
     */
    public function offsetSet($index, $value)
    {
        $this->data[$index] = $value;
    }

    /**
     * Unsets/removes the value at given index.
     *
     * @param string|int $index
     */
    public function offsetUnset($index)
    {
        unset($this->data[$index]);
    }

    /**
     * Gets the count of items.
     *
     * @return int
     */
    public function count()
    {
        return \count($this->data);
    }

    /**
     * Alias of count().
     */
    public function size()
    {
        return $this->count();
    }

    /**
     * Gets the iterator for looping.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Gets the data for json serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Stringify the underscore instance.
     *
     * @return string Json encoded data.
     */
    public function __toString()
    {
        return \json_encode($this->toArray());
    }

    /**
     * The current time in millisec.
     *
     * @return float
     */
    public function now()
    {
        return \microtime(1) * 1000;
    }

    /**
     * Get all the keys.
     *
     * @return self
     */
    public function keys()
    {
        return new static(\array_keys($this->data));
    }

    /**
     * Get all the keys.
     *
     * @return self
     */
    public function values()
    {
        return new static(\array_values($this->data));
    }

    /**
     * Pair all items to use an array of index and value.
     *
     * @return self
     */
    public function pairs()
    {
        $pairs = [];

        foreach ($this->data as $index => $value) {
            $pairs[$index] = [$index, $value];
        }

        return new static($pairs);
    }

    /**
     * Swap index and value of all the items. The values should be stringifiable.
     *
     * @return self
     */
    public function invert()
    {
        return new static(\array_flip($this->data));
    }

    /**
     * Pick only the items having one of the whitelisted indexes.
     *
     * @param array|...string|...int $index Either whitelisted indexes as array or as variads.
     *
     * @return self
     */
    public function pick($index)
    {
        $indices = \array_flip(\is_array($index) ? $index : \func_get_args());

        return new static(\array_intersect_key($this->data, $indices));
    }

    /**
     * Omit the items having one of the blacklisted indexes.
     *
     * @param array|...string|...int $index Either blacklisted indexes as array or as variads.
     *
     * @return self
     */
    public function omit($index)
    {
        $indices = \array_diff(
            \array_keys($this->data),
            \is_array($index) ? $index : \func_get_args()
        );

        return $this->pick($indices);
    }

    /**
     * A static shortcut to constructor.
     *
     * @param mixed $data
     *
     * @return self
     */
    public static function _($data = null)
    {
        return new static($data);
    }
}
