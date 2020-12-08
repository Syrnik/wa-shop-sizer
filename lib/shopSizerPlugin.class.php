<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2020
 * @license Webasyst
 */

declare(strict_types=1);

/**
 * Class shopSizerPlugin
 */
class shopSizerPlugin extends shopPlugin
{
    /**
     * @param array $params
     * @return array|string
     * @throws Exception
     */
    public function getControls($params = array())
    {
        waHtmlControl::registerControl('DimensionInput', [$this, 'dimensionsInputControl']);
        return parent::getControls($params);
    }

    /**
     * @param string $name
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function dimensionsInputControl(string $name, array $params = []): string
    {
        $default_params = ['title' => '', 'title_wrapper' => false, 'description' => ''];
        $params = array_filter($params, function ($field) {
            return strpos($field, 'wrapper') === false;
        }, ARRAY_FILTER_USE_KEY);

        waHtmlControl::makeId($params, $name);

        if (!isset($params['value']) || !is_array($params['value']))
            $params['value'] = ['length' => 0, 'width' => 0, 'height' => 0, 'unit' => 'm'];

        foreach (['length', 'width', 'height'] as $item)
            if (!isset($params['value'][$item])) $params['value'][$item] = 0;

        if (!isset($params['value']['unit'])) {
            $base_unit = shopDimension::getBaseUnit('length');
            $params['value']['unit'] = ifset($base_unit, 'value', 'm');
        }

        $params = array_merge($params, $default_params);
        waHtmlControl::addNamespace($params, $name);

        $controls_arr = [];

        foreach (['length', 'width', 'height'] as $item) {
            $item_params = $params;
            $item_params['value'] = $params['value'][$item];
            $item_params['class'] = array_merge((array)ifset($item_params, 'class', []), ['short', 'numerical']);
            $item_params['placeholder'] = 0;
            $item_params['field_type'] = 'number';
            $item_params['min'] = '0';
            $control = trim(waHtmlControl::getControl(waHtmlControl::INPUT, $item, $item_params));

            $controls_arr[] = $control;
        }

        return implode('×', $controls_arr) .
            ' ' .
            trim(waHtmlControl::getControl(
                waHtmlControl::SELECT,
                'unit',
                array_merge($params, ['value' => $params['value']['unit'], 'options' => shopDimension::getUnits('length')])
            ));
    }


    public function weightInputControl(array $params = []): string
    {
        $value = (array)ifset($params, 'value', []);
        $weight_field = ifset($params, 'field_names', 'value', 'value') ?: 'value';
        $unit_field = ifset($params, 'field_names', 'unit', 'unit') ?: 'unit';
        $weight_value = ifset($value, $weight_field, 0);
        if (is_string($weight_value))
            $weight_value = (float)str_replace(',', '.', trim($weight_value));
        $unit_field = ifset($value, $unit_field, 'kg') ?: 'kg';
        unset($params['field_names']);

        $default_params = ['title' => '', 'title_wrapper' => false, 'description' => '',];
        $params = array_filter($params, function ($field) {
            return strpos($field, 'wrapper') === false;
        }, ARRAY_FILTER_USE_KEY);
    }
}
