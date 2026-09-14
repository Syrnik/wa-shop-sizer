<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Кастомные контролы настроек: getControls(), dimensionsInputControl(), weightInputControl(),
 * packagesDimensionsControl().
 */
class shopSizerPluginControlsTest extends TestCase
{
    /**
     * getControls() регистрирует DimensionInput/WeightInput/PackageDimensions в waHtmlControl.
     * PackageDimensions рендерит вложенный DimensionInput — без предварительной регистрации
     * вместо контрола вывелось бы «Control type DimensionInput undefined».
     */
    public function testGetControlsRegistersCustomControlTypesUsedByPackageDimensions(): void
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->getControls();

        $html = $plugin->packagesDimensionsControl('sizes', [
            'id'        => 'sizer-sizes',
            'namespace' => 'sizer',
            'value'     => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 10, 'height' => 10, 'length' => 10, 'unit' => 'cm', 'add_weight' => 30, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $this->assertStringNotContainsString('undefined', $html);
    }

    public function testGetControlsReturnsAllThreeSettingsKeys(): void
    {
        $fake = new shopSizerPluginTestAppSettingsModelFake();
        shopSizerPluginTestDouble::installSettingsModel($fake);

        $plugin = new shopSizerPluginTestDouble();
        $controls = $plugin->getControls();

        $this->assertArrayHasKey('default_size', $controls);
        $this->assertArrayHasKey('default_add_weight', $controls);
        $this->assertArrayHasKey('sizes', $controls);

        shopSizerPluginTestDouble::resetSettingsModel();
    }

    public function testDimensionsInputControlRendersThreeNumberInputsAndUnitSelect(): void
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->getControls();

        $html = $plugin->dimensionsInputControl('default_size', [
            'namespace' => 'sizer',
            'value'     => ['length' => 10, 'width' => 20, 'height' => 30, 'unit' => 'cm'],
        ]);

        $this->assertStringContainsString('name="sizer[default_size][length]"', $html);
        $this->assertStringContainsString('name="sizer[default_size][width]"', $html);
        $this->assertStringContainsString('name="sizer[default_size][height]"', $html);
        $this->assertStringContainsString('name="sizer[default_size][unit]"', $html);
        $this->assertStringContainsString('value="10"', $html);
        $this->assertStringContainsString('value="20"', $html);
        $this->assertStringContainsString('value="30"', $html);
        $this->assertStringContainsString('<option value="cm" selected="selected">', $html);
        $this->assertSame(3, substr_count($html, 'type="number"'));
    }

    public function testDimensionsInputControlWithoutValueDefaultsToZerosAndBaseUnit(): void
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->getControls();

        $html = $plugin->dimensionsInputControl('default_size', ['namespace' => 'sizer']);

        $this->assertSame(3, substr_count($html, 'value="0"'));
        $this->assertStringContainsString('<option value="m" selected="selected">', $html);
    }

    public function testWeightInputControlWithFieldNamesRendersAddWeightFields(): void
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->getControls();

        $html = $plugin->weightInputControl('pack', [
            'namespace'   => 'sizer',
            'value'       => ['add_weight' => 30, 'add_weight_unit' => 'g'],
            'field_names' => ['value' => 'add_weight', 'unit' => 'add_weight_unit'],
        ]);

        $this->assertStringContainsString('name="sizer[pack][add_weight]"', $html);
        $this->assertStringContainsString('name="sizer[pack][add_weight_unit]"', $html);
        $this->assertStringContainsString('value="30"', $html);
        $this->assertStringContainsString('<option value="g" selected="selected">', $html);
    }

    public function testWeightInputControlParsesCommaDecimalValue(): void
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->getControls();

        $html = $plugin->weightInputControl('default_add_weight', [
            'namespace' => 'sizer',
            'value'     => ['value' => '0,5', 'unit' => 'kg'],
        ]);

        $this->assertStringContainsString('value="0.5"', $html);
    }

    public function testPackagesDimensionsControlRendersOneRowPerConfiguredPack(): void
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->getControls();

        $html = $plugin->packagesDimensionsControl('sizes', [
            'id'        => 'sizer-sizes',
            'namespace' => 'sizer',
            'value'     => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 10, 'height' => 10, 'length' => 10, 'unit' => 'cm', 'add_weight' => 30, 'add_weight_unit' => 'g'],
                ['weight' => 2, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 50, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $this->assertStringContainsString('id="sizer-sizes-table"', $html);
        $this->assertSame(2, substr_count($html, 'class="js-size-row"'));
        $this->assertStringContainsString('name="sizer[sizes][packs][0][weight]"', $html);
        $this->assertStringContainsString('name="sizer[sizes][packs][1][weight]"', $html);
    }
}
