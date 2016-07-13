<?php

/**
 * @link http://www.digitaldeals.cz/
 * @copyright Copyright (c) 2016 Digital Deals s.r.o.
 * @license http://www.digitaldeals.cz/license/
 */

namespace dlds\giixer\components\helpers;

use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

/**
 * This is AR model helper class provides with massive assignment adapter,
 * validation rules and scenarios manipulators
 * @see http://www.yiiframework.com/doc-2.0/guide-db-active-record.html
 */
class GxModelHelper
{

    /**
     * Adapts given params to model requirements and be able to pass
     * massive assignemnt. 
     * ---
     * Also adapts params in format
     * "Classname_attribute" to standart "Classname[attribute]"
     * ===
     * @param array $params given params to be adapted
     * @param string $classname model classname used in mass assignment
     * @see http://www.yiiframework.com/doc-2.0/guide-structure-models.html#massive-assignment
     * @return array adapted params
     */
    public static function adaptToMassiveAssignment($params, $classname)
    {
        if ($params) {
            $basename = StringHelper::basename($classname);

            foreach ($params as $key => $value) {

                $attr = $key;

                if (preg_match(sprintf('/^%s_.*$/', $className), $attr)) {
                    $attr = ltrim(strstr($key, '_'), '_');
                }

                unset($params[$key]);

                $params[$basename][$attr] = $value;
            }
        }

        return $params;
    }

    /**
     * Removes validation of given attributes from given rules
     * ---
     * The most common use of this method is when you want to have 
     * columns 'created_at' and 'updated_at' automatically filled by timestamps
     * but these columns are required by DB schema so cannot be NULL (empty) see below
     * ---
     * self::removeValidationRules($rules, 'required', ['created_at', 'updated_at']);
     * ===
     * @param array $rules model rules from which attrs will be removed
     * @param string $validator name of rule validator the attrs are defined in
     * @param array $attrs names of attributes which will be removed
     */
    public static function removeValidationRules(&$rules, $validator, array $attrs = [])
    {
        foreach ($rules as $i => &$rule) {
            if ($validator === $rule[1]) {
                if (empty($attrs)) {
                    ArrayHelper::remove($rules, $i);
                } else {
                    foreach ($rule[0] as $j => $attr) {
                        if (in_array($attr, $attrs)) {
                            ArrayHelper::remove($rule[0], $j);
                        }
                    }
                }
            }
        }
    }

    /**
     * Removes given attributes from given scenario name
     * @param array $scenarios model scenarios
     * @param string $name targeted scenario name
     * @param array $attrs attrs to be removed
     */
    public static function removeScenarioAttributes(&$scenarios, $name, array $attrs = [])
    {
        if (isset($scenarios[$name])) {
            foreach ($attrs as $attr) {
                $key = array_search($attr, $scenarios[$name]);

                if (false !== $key) {
                    ArrayHelper::remove($scenarios[$name], $key);
                }
            }
        }
    }

    /**
     * Sets given attributes unsafe in given scenario name
     * @param array $scenarios model scenarios
     * @param string $name targeted scenario name
     * @param array $attrs attrs to be set unsafe
     */
    public static function setAttributesUnsafe(&$scenarios, $name, array $attrs = [])
    {
        if (isset($scenarios[$name])) {
            foreach ($attrs as $attr) {
                $key = array_search($attr, $scenarios[$name]);

                if (false !== $key) {
                    $value = $scenarios[$name][$key];

                    if (!StringHelper::startsWith($value, '!')) {
                        $scenarios[$name][$key] = sprintf('!%s', $value);
                    }
                }
            }
        }
    }

}