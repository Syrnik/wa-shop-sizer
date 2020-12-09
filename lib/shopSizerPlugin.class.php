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
        waHtmlControl::registerControl('WeightInput', [$this, 'weightInputControl']);
        waHtmlControl::registerControl('PackageDimensions', [$this, 'packagesDimensionsControl']);
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

    /**
     * @param string $name
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function weightInputControl(string $name, array $params = []): string
    {
        $value = (array)ifset($params, 'value', []);
        $weight_field_name = ifset($params, 'field_names', 'value', 'value') ?: 'value';
        $unit_field_name = ifset($params, 'field_names', 'unit', 'unit') ?: 'unit';
        $weight_value = ifset($value, $weight_field_name, 0);
        if (is_string($weight_value))
            $weight_value = (float)str_replace(',', '.', trim($weight_value));
        $unit_value = ifset($value, $unit_field_name, 'kg') ?: 'kg';
        if (!$unit_value) {
            $base_unit = shopDimension::getBaseUnit('weight');
            $unit_value = ifset($base_unit, 'value', 'kg');
        }

        unset($params['field_names']);
        $controls = [];

        waHtmlControl::makeId($params);

        $default_params = ['title' => '', 'title_wrapper' => false, 'description' => '',];
        $params = array_filter($params, function ($field) {
            return strpos($field, 'wrapper') === false;
        }, ARRAY_FILTER_USE_KEY);

        $params = array_merge($params, $default_params);
        waHtmlControl::addNamespace($params, $name);
        $weight_field_params = array_merge($params, [
            'value'       => $weight_value,
            'class'       => ifset($params, 'value_field_class', ['short', 'numerical']),
            'placeholder' => '0',
            'field_type'  => 'number',
            'min'         => '0'
        ]);
        $unit_field_params = array_merge($params, [
            'value' => $unit_value, 'options' => shopDimension::getUnits('weight')
        ]);
        $controls[] = trim(waHtmlControl::getControl(waHtmlControl::INPUT, $weight_field_name, $weight_field_params));
        $controls[] = trim(waHtmlControl::getControl(waHtmlControl::SELECT, $unit_field_name, $unit_field_params));

        return implode(' ', $controls);
    }

    /**
     * @param string $name
     * @param array $params
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    public function packagesDimensionsControl(string $name, array $params = []): string
    {
        $default_params = ['title' => '', 'title_wrapper' => false, 'description' => ''];
        $params = array_filter($params, function ($field) {
            return strpos($field, 'wrapper') === false;
        }, ARRAY_FILTER_USE_KEY);

        $params = array_merge($params, $default_params);
        waHtmlControl::addNamespace($params, $name);

        $controls = [
            'weight_unit' => waHtmlControl::getControl(
                waHtmlControl::SELECT,
                'weight_unit',
                array_merge($params, ['value' => $params['value']['weight_unit'], 'options' => shopDimension::getUnits('weight')])
            )
        ];

        $row_params = $params;
        waHtmlControl::addNamespace($row_params, 'packs');

        $grid_row = function ($params, $id, $pack) {
            $row_params = $params;
            $controls = [];
            waHtmlControl::addNamespace($row_params, $id);
            $weight_control = waHtmlControl::getControl(
                waHtmlControl::INPUT,
                'weight',
                array_merge($row_params, [
                    'value'      => $pack['weight'],
                    'field_type' => 'number',
                    'min'        => '0',
                    'step'       => '0.001',
                    'class'      => 'short numerical'
                ])
            );
            $controls['weight'] = $weight_control;
            $controls['size'] = waHtmlControl::getControl(
                'DimensionInput',
                $id,
                array_merge($params, [
                    'value' => ['length' => $pack['length'], 'width' => $pack['width'], 'height' => $pack['height'], 'unit' => $pack['unit']]
                ])
            );
            $controls['add_weight'] = waHtmlControl::getControl(
                'WeightInput',
                $id,
                array_merge($params, [
                    'value'       => ['add_weight' => $pack['add_weight'], 'add_weight_unit' => $pack['add_weight_unit']],
                    'field_names' => ['value' => 'add_weight', 'unit' => 'add_weight_unit']
                ])
            );

            return $controls;
        };

        foreach ($params['value']['packs'] as $id => $pack)
            $controls['packs'][$id] = $grid_row($row_params, $id, $pack);

        $empty_row_controls = $grid_row($row_params, 0, [
            'weight'          => 1,
            'width'           => 10,
            'height'          => 10,
            'length'          => 10,
            'unit'            => 'cm',
            'add_weight'      => 30,
            'add_weight_unit' => 'g'
        ]);

        $empty_row = "<tr class=\"js-size-row\"><td>{$empty_row_controls['weight']}</td>" .
            "<td>{$empty_row_controls['size']}</td><td>{$empty_row_controls['add_weight']}</td>" .
            "<td class=\"actions\"><a href=\"javascript:void(0)\" alt=\"Удалить\" title=\"Удалить\" class=\"js-action-delete\"><i class=\"icon16 no\"></i></a></td></tr>";

        $namespace = (string)waHtmlControl::makeNamespace($row_params);
        $table_id = $params['id'] . '-table';

        $view = new waSmarty3View(wa());
        $view->assign(compact('controls', 'namespace', 'table_id', 'empty_row'));

        return $view->fetch($this->path . '/templates/controls/package-dimensions-control.html');
    }

    /**
     * @param array $settings
     * @return array|void
     * @throws waException
     */
    public function saveSettings($settings = array())
    {
        if (is_array($settings)) {
            if (isset($settings['sizes'])) {
                $sizes = $settings['sizes'];
                if (is_array($sizes) && isset($sizes['packs']) && is_array($sizes['packs'])) {
                    array_walk($sizes['packs'], function (&$s) {
                        if (is_array($s)) {
                            $s['weight'] = (float)str_replace(',', '.', $s['weight']);
                            $s['width'] = (float)str_replace(',', '.', $s['width']);
                            $s['height'] = (float)str_replace(',', '.', $s['height']);
                            $s['length'] = (float)str_replace(',', '.', $s['length']);
                            $s['add_weight'] = (float)str_replace(',', '.', $s['add_weight']);
                            if ((0 >= $s['width']) || (0 >= $s['height']) || (0 >= $s['length']))
                                throw new waException('Измерение у размера упаковки должно быть больше нуля!');
                        }
                    });
                    usort($sizes['packs'], function ($a, $b) {
                        return $a['weight'] <=> $b['weight'];
                    });
                }
                $settings['sizes'] = $sizes;
            }
            if (isset($settings['default_size'])) {
                foreach (['length', 'width', 'height'] as $value) {
                    $settings['default_size'][$value] = (float)str_replace(',', '.', $settings['default_size'][$value]);
                    if (0 >= $settings['default_size'][$value])
                        throw new waException('Измерение у размера упаковки по умолчанию должно быть больше нуля!');
                }
            }
            if (isset($settings['default_add_weight']))
                $settings['default_add_weight']['value'] = (float)str_replace(',', '.', $settings['default_add_weight']['value']);
        }

        parent::saveSettings($settings);
    }
}
