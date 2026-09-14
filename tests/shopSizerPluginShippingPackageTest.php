<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * handlerShippingPackage() — подбор упаковки для хука shipping_package.
 *
 * Базовые единицы установки — m и kg (wa-apps/shop/lib/config/data/dimension.php,
 * переопределений в wa-config/apps/shop/ нет), поэтому все ожидаемые значения ниже — в метрах
 * и килограммах.
 */
class shopSizerPluginShippingPackageTest extends TestCase
{
    private const DELTA = 1e-9;

    private function makePlugin(array $settings): shopSizerPluginTestDouble
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->setTestSettings($settings);

        return $plugin;
    }

    public function testTotalWeightSumsQuantityTimesWeightWithCommaAsDecimalSeparator(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 2, 'weight' => '1,0'],
        ]);

        $this->assertSame(2.0, $result['weight']);
    }

    public function testItemWithoutWeightKeyIsTreatedAsZero(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1],
        ]);

        $this->assertSame(0.0, $result['weight']);
    }

    public function testEmptyItemsListUsesOnlyDefaultAddWeightAndDefaultSize(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([]);

        $this->assertSame(0.0, $result['weight']);
        $this->assertEqualsWithDelta(0.1, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['height'], self::DELTA);
    }

    public function testNoPacksConfiguredFallsBackToDefaultSizeAndAddWeight(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 30, 'unit' => 'g'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(1.03, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['height'], self::DELTA);
    }

    public function testHeaviestPackWithThresholdNotExceedingTotalWeightIsSelected(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 5, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'cm', 'add_weight' => 300, 'add_weight_unit' => 'g'],
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(2.1, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['height'], self::DELTA);
    }

    public function testWeightBelowLightestThresholdFallsBackToDefaultSize(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 5, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'cm', 'add_weight' => 300, 'add_weight_unit' => 'g'],
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 0.5],
        ]);

        $this->assertEqualsWithDelta(0.5, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['height'], self::DELTA);
    }

    public function testPacksAreSortedByWeightBeforeSelectionRegardlessOfConfiguredOrder(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            // Настроены не по возрастанию веса: тяжёлая упаковка первой.
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 5, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'cm', 'add_weight' => 300, 'add_weight_unit' => 'g'],
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1],
        ]);

        // При правильной сортировке для веса 2 кг должна выбраться упаковка с порогом 1 кг (20 см),
        // а не 5 кг (40 см).
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testThresholdsInNonBaseWeightUnitAreConverted(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'g', 'packs' => [
                ['weight' => 500, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
                ['weight' => 2000, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'mm', 'add_weight' => 0.2, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 0.6],
        ]);

        $this->assertEqualsWithDelta(0.7, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testWeightExactlyEqualToThresholdSelectsThatPack(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'g', 'packs' => [
                ['weight' => 500, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
                ['weight' => 2000, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'mm', 'add_weight' => 0.2, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 2],
        ]);

        $this->assertEqualsWithDelta(2.2, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.04, $result['length'], self::DELTA);
    }

    public function testPackDimensionsAreConvertedToBaseLinearUnit(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'mm', 'add_weight' => 0, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(0.04, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.04, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.04, $result['height'], self::DELTA);
    }

    public function testAddWeightInGramsIsConvertedAndAddedToTotal(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(1.1, $result['weight'], self::DELTA);
    }

    public function testResultHasExactlyWeightLengthWidthHeightKeys(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertSame(['weight', 'length', 'width', 'height'], array_keys($result));
    }

    /**
     * README.md обещает: «Если в заказе есть товар, максимальный габарит которого превышает
     * максимальный габарит упаковки, ищется следующая упаковка, для большего веса, такая, чтобы
     * её габарит превышал габарит заказа». handlerShippingPackage() габариты товаров не смотрит
     * вообще — подбор только по весу. Этот тест фиксирует фактическое поведение кода; падение
     * теста означает либо реализовали обещанное в README (тест надо переписать), либо сломали
     * подбор.
     */
    public function testItemDimensionsDoNotAffectPackSelectionDespiteReadmeClaim(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            // Единственная упаковка заведомо меньше габаритов товара из заказа.
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 5, 'height' => 5, 'length' => 5, 'unit' => 'cm', 'add_weight' => 0, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            // Габарит товара (length) намного больше габарита выбранной упаковки — README ожидал
            // бы поиска упаковки покрупнее, но такого ключа handlerShippingPackage() даже не читает.
            ['quantity' => 1, 'weight' => 1, 'length' => 200],
        ]);

        $this->assertEqualsWithDelta(
            0.05,
            $result['length'],
            self::DELTA,
            'handlerShippingPackage() не учитывает габариты товаров вопреки описанию в README.md — ' .
            'см. секцию "Вне scope" в плане MYOTH-562'
        );
    }
}
