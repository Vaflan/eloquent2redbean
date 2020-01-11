<?php

namespace App;

use DateTime;
use RedBeanPHP\Facade;
use RedBeanPHP\OODBBean;
use RedBeanPHP\SimpleModel;

/**
 * Class Model
 *
 * For RedBeanPHP Version 5.3, RedBeanPHP use plain classes (with namespacing)
 *
 * <code>
 * define('REDBEAN_MODEL_PREFIX', '\\App\\Models\\');
 * </code>
 *
 * @property int $id
 * @property string $createdAt
 * @method static static where(string $column, $operator = null, $value = null, $implode = 'and')
 * @method static static orWhere(string $column, $operator = null, $value = null)
 * @method static static whereIn(string $column, array $values, string $implode = 'and', bool $not = false) Add a "where in" clause to the query.
 */
abstract class Model extends SimpleModel
{
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = 'created_at';

    protected $__where = [];

    private static function modelClassName()
    {
        $classNameRow = explode('\\', static::class);
        return lcfirst(end($classNameRow));
    }

    public function __call($method, $arguments)
    {
        if (in_array($method, ['where', 'orWhere', 'whereIn'])) {
            $method = '__' . $method;
            return $this->$method(...$arguments);
        }
        return $this->$method(...$arguments);
    }

    public static function __callStatic($method, $arguments)
    {
        return (new static())->$method(...$arguments);
    }

    /**
     * @return static|OODBBean
     */
    public static function create()
    {
        return Facade::dispense(static::modelClassName());
    }

    /**
     * @param int $id
     * @return static|OODBBean
     */
    public static function find(int $id)
    {
        return Facade::load(static::modelClassName(), $id);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $implode
     * @return static
     */
    public function __where($column, $operator = null, $value = null, $implode = 'and')
    {
        if (!isset($value)) {
            $value = $operator;
            $operator = '=';
        }
        $choose = '?';

        if (is_array($value)) {
            switch ($operator) {
                case '=':
                    $operator = 'IN';
                    break;
                case '!=':
                case '<>':
                    $operator = 'NOT IN';
                    break;
            }
            $choose = '(' . Facade::genSlots($value) . ')';
        } else {
            $value = [$value];
        }

        $this->__where[] = [
            'where' => " {$column} {$operator} {$choose} ",
            'value' => $value,
            'implode' => $implode
        ];
        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return static
     */
    public function __orWhere($column, $operator = null, $value = null)
    {
        return $this->__where($column, $operator, $value, 'or');
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param string $column
     * @param array $values
     * @param string $implode = 'and'
     * @param bool $not = false
     * @return static
     */
    public function __whereIn($column, $values, $implode = 'and', $not = false)
    {
        return $this->__where($column, $not ? '<>' : '=', $values, $implode);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return static[]
     */
    public function get()
    {
        $where = '';
        $chooseValues = [];
        foreach ($this->__where as $index => $row) {
            if ($index > 0) {
                $where .= $row['implode'];
            }
            $where .= $row['where'];
            $chooseValues = array_merge($chooseValues, $row['value']);
        }
        $this->__where = [];
        return Facade::find(
            static::modelClassName(),
            $where,
            $chooseValues
        );
    }

    public function save()
    {
        /** @var static|OODBBean $this */
        if (static::UPDATED_AT) {
            $this->{static::UPDATED_AT} = new DateTime();
        }
        if (static::CREATED_AT && !$this->{static::CREATED_AT}) {
            $this->{static::CREATED_AT} = new DateTime();
        }
        return Facade::store($this);
    }

    /**
     * Update the model in the database.
     *
     * @param array $attributes
     * @return bool
     */
    public function update(array $attributes = [])
    {
        if (empty($attributes)) {
            return false;
        }

        /** @var static|OODBBean $object */
        $objects = [$this];
        if (!empty($this->__where)) {
            $objects = $this->get();
        }

        $result = true;
        foreach ($objects as $object) {
            if (!$object->id) {
                $result = false;
                break;
            }
            foreach ($attributes as $column => $value) {
                $object->{$column} = $value;
            }
            $result = !empty($object->save());
        }
        return $result;
    }

    public function refresh()
    {
        /** @var static|OODBBean $this */
        return $this->fresh();
    }
}